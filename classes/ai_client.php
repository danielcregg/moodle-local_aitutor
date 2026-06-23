<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Server-side AI client for the AI Tutor plugin.
 *
 * @package    local_aitutor
 * @copyright  2026 Daniel Cregg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aitutor;

/**
 * Holds the provider configuration (including the API key) and calls the configured external AI to
 * produce ONE escalating Socratic hint. The key never reaches the browser.
 */
class ai_client {
    /** @var array OpenAI-compatible / gemini / anthropic endpoints, keyed by provider id. */
    const PROVIDERS = [
        'openai'   => ['kind' => 'openai', 'endpoint' => 'https://api.openai.com/v1/chat/completions'],
        'groq'     => ['kind' => 'openai', 'endpoint' => 'https://api.groq.com/openai/v1/chat/completions'],
        'deepseek' => ['kind' => 'openai', 'endpoint' => 'https://api.deepseek.com/chat/completions'],
        'mistral'  => ['kind' => 'openai', 'endpoint' => 'https://api.mistral.ai/v1/chat/completions'],
        'cerebras' => ['kind' => 'openai', 'endpoint' => 'https://api.cerebras.ai/v1/chat/completions'],
        'zenmux'   => ['kind' => 'openai', 'endpoint' => 'https://zenmux.ai/api/v1/chat/completions'],
        'claude'   => ['kind' => 'anthropic', 'endpoint' => 'https://api.anthropic.com/v1/messages'],
        'gemini'   => ['kind' => 'gemini', 'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/'],
    ];

    /** @var string The Socratic system prompt (mirrors the decoupled web tutor). */
    const SYSTEM =
        "You are a patient Socratic mathematics tutor embedded in a STACK quiz.\n" .
        "Rules:\n" .
        "- NEVER state the final answer or give a full worked solution. Lead the student to it.\n" .
        "- Reply with exactly ONE hint, 1-3 sentences, about the student's specific mistake.\n" .
        "- Escalate by attempt: 1 = a gentle conceptual nudge; 2 = point to the specific step or " .
        "error; 3+ = name the method/rule to apply (but still NOT the final value).\n" .
        "- Ground the hint in the student's actual answer and the grader feedback.\n" .
        "- Be encouraging and concise. Plain text only - no markdown, no LaTeX delimiters.";

    /**
     * Generate a single Socratic hint.
     *
     * @param string $question The question text the student is working on.
     * @param string $answer The student's current answer.
     * @param string $feedback The grader feedback for that answer.
     * @param int $attempt The hint attempt number (drives escalation).
     * @return string The hint text.
     * @throws \moodle_exception If the plugin is not fully configured or the AI call fails.
     */
    public static function hint(string $question, string $answer, string $feedback, int $attempt): string {
        $providerid = (string) get_config('local_aitutor', 'provider');
        $model = (string) get_config('local_aitutor', 'model');
        $key = (string) get_config('local_aitutor', 'apikey');
        if ($providerid === '' || !isset(self::PROVIDERS[$providerid])) {
            throw new \moodle_exception('noprovider', 'local_aitutor');
        }
        if ($model === '') {
            throw new \moodle_exception('nomodel', 'local_aitutor');
        }
        if ($key === '') {
            throw new \moodle_exception('nokey', 'local_aitutor');
        }
        $p = self::PROVIDERS[$providerid];

        $user = "QUESTION: {$question}\n"
            . "STUDENT'S ANSWER: " . ($answer !== '' ? $answer : '(blank)') . "\n"
            . "GRADER FEEDBACK: " . ($feedback !== '' ? $feedback : '(none)') . "\n"
            . "ATTEMPT NUMBER: {$attempt}\n"
            . "Give one Socratic hint appropriate to this attempt number.";

        switch ($p['kind']) {
            case 'openai':
                return self::call_openai($p['endpoint'], $key, $model, self::SYSTEM, $user);
            case 'gemini':
                return self::call_gemini($p['endpoint'], $key, $model, self::SYSTEM, $user);
            case 'anthropic':
                return self::call_anthropic($p['endpoint'], $key, $model, self::SYSTEM, $user);
            default:
                throw new \moodle_exception('noprovider', 'local_aitutor');
        }
    }

    /**
     * Perform a JSON HTTP POST and return the decoded response.
     *
     * @param string $url The endpoint URL.
     * @param array $headers HTTP headers.
     * @param array $body The request body (JSON-encoded before sending).
     * @return array The decoded JSON response.
     * @throws \moodle_exception On transport error, non-2xx status, or invalid JSON.
     */
    private static function http(string $url, array $headers, array $body): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 10,
            // Container DNS returns IPv6-first but has no IPv6 egress; force IPv4.
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false) {
            throw new \moodle_exception('aifailed', 'local_aitutor', '', $err);
        }
        if ($code < 200 || $code >= 300) {
            throw new \moodle_exception('aifailed', 'local_aitutor', '', $code . ': ' . substr((string) $resp, 0, 200));
        }
        $data = json_decode($resp, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('aifailed', 'local_aitutor', '', 'invalid JSON');
        }
        return is_array($data) ? $data : [];
    }

    /**
     * Call an OpenAI-compatible chat-completions endpoint.
     *
     * @param string $endpoint The endpoint URL.
     * @param string $key The API key.
     * @param string $model The model id.
     * @param string $system The system prompt.
     * @param string $user The user prompt.
     * @return string The hint text.
     */
    private static function call_openai(string $endpoint, string $key, string $model, string $system, string $user): string {
        $j = self::http($endpoint, ['Content-Type: application/json', 'Authorization: Bearer ' . $key], [
            'model' => $model, 'temperature' => 0.7,
            'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $user]],
        ]);
        return self::nonempty(trim($j['choices'][0]['message']['content'] ?? ''));
    }

    /**
     * Call the Google Gemini generateContent endpoint.
     *
     * @param string $endpoint The endpoint base URL.
     * @param string $key The API key.
     * @param string $model The model id.
     * @param string $system The system prompt.
     * @param string $user The user prompt.
     * @return string The hint text.
     */
    private static function call_gemini(string $endpoint, string $key, string $model, string $system, string $user): string {
        $url = $endpoint . rawurlencode($model) . ':generateContent?key=' . rawurlencode($key);
        $j = self::http($url, ['Content-Type: application/json'], [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $user]]]],
            'generationConfig' => ['temperature' => 0.7],
        ]);
        $parts = $j['candidates'][0]['content']['parts'] ?? [];
        $text = '';
        foreach ($parts as $part) {
            $text .= $part['text'] ?? '';
        }
        return self::nonempty(trim($text));
    }

    /**
     * Call the Anthropic Claude messages endpoint.
     *
     * @param string $endpoint The endpoint URL.
     * @param string $key The API key.
     * @param string $model The model id.
     * @param string $system The system prompt.
     * @param string $user The user prompt.
     * @return string The hint text.
     */
    private static function call_anthropic(string $endpoint, string $key, string $model, string $system, string $user): string {
        $j = self::http(
            $endpoint,
            ['Content-Type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01'],
            [
                'model' => $model,
                'max_tokens' => 1024,
                'system' => $system,
                'messages' => [['role' => 'user', 'content' => $user]],
            ]
        );
        $blocks = $j['content'] ?? [];
        $text = '';
        foreach ($blocks as $b) {
            $text .= $b['text'] ?? '';
        }
        return self::nonempty(trim($text));
    }

    /**
     * Guard against an empty AI response.
     *
     * @param string $text The candidate hint text.
     * @return string The non-empty hint text.
     * @throws \moodle_exception If the text is empty.
     */
    private static function nonempty(string $text): string {
        if ($text === '') {
            throw new \moodle_exception('aiempty', 'local_aitutor');
        }
        return $text;
    }
}
