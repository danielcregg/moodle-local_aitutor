<?php
namespace local_aitutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Server-side AI client. Holds the provider config (incl. the API key) and calls the
 * external AI to produce ONE escalating Socratic hint. The Socratic system prompt mirrors
 * docs/js/tutor/tutor-ai.js so Moodle behaves like the decoupled web tutor.
 */
class ai_client {

    /** OpenAI-compatible / gemini / anthropic endpoints, keyed by provider id. */
    const PROVIDERS = [
        'zenmux'   => ['kind' => 'openai',    'endpoint' => 'https://zenmux.ai/api/v1/chat/completions'],
        'openai'   => ['kind' => 'openai',    'endpoint' => 'https://api.openai.com/v1/chat/completions'],
        'groq'     => ['kind' => 'openai',    'endpoint' => 'https://api.groq.com/openai/v1/chat/completions'],
        'deepseek' => ['kind' => 'openai',    'endpoint' => 'https://api.deepseek.com/chat/completions'],
        'mistral'  => ['kind' => 'openai',    'endpoint' => 'https://api.mistral.ai/v1/chat/completions'],
        'cerebras' => ['kind' => 'openai',    'endpoint' => 'https://api.cerebras.ai/v1/chat/completions'],
        'claude'   => ['kind' => 'anthropic', 'endpoint' => 'https://api.anthropic.com/v1/messages'],
        'gemini'   => ['kind' => 'gemini',    'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/'],
    ];

    const SYSTEM =
        "You are a patient Socratic mathematics tutor embedded in a STACK quiz.\n" .
        "Rules:\n" .
        "- NEVER state the final answer or give a full worked solution. Lead the student to it.\n" .
        "- Reply with exactly ONE hint, 1-3 sentences, about the student's specific mistake.\n" .
        "- Escalate by attempt: 1 = a gentle conceptual nudge; 2 = point to the specific step or " .
        "error; 3+ = name the method/rule to apply (but still NOT the final value).\n" .
        "- Ground the hint in the student's actual answer and the grader feedback.\n" .
        "- Be encouraging and concise. Plain text only - no markdown, no LaTeX delimiters.";

    /** Generate a hint. Returns the hint text; throws \moodle_exception on failure. */
    public static function hint(string $question, string $answer, string $feedback, int $attempt): string {
        $providerid = get_config('local_aitutor', 'provider') ?: 'zenmux';
        $model = get_config('local_aitutor', 'model') ?: 'z-ai/glm-5.2-free';
        $key = get_config('local_aitutor', 'apikey');
        if (empty($key)) {
            throw new \moodle_exception('No AI API key is configured for the AI Tutor plugin.');
        }
        $p = self::PROVIDERS[$providerid] ?? self::PROVIDERS['zenmux'];

        $user = "QUESTION: {$question}\n"
            . "STUDENT'S ANSWER: " . ($answer !== '' ? $answer : '(blank)') . "\n"
            . "GRADER FEEDBACK: " . ($feedback !== '' ? $feedback : '(none)') . "\n"
            . "ATTEMPT NUMBER: {$attempt}\n"
            . "Give one Socratic hint appropriate to this attempt number.";

        switch ($p['kind']) {
            case 'openai':    return self::call_openai($p['endpoint'], $key, $model, self::SYSTEM, $user);
            case 'gemini':    return self::call_gemini($p['endpoint'], $key, $model, self::SYSTEM, $user);
            case 'anthropic': return self::call_anthropic($p['endpoint'], $key, $model, self::SYSTEM, $user);
            default:          throw new \moodle_exception('Unsupported AI provider kind.');
        }
    }

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
            throw new \moodle_exception('AI request failed: ' . $err);
        }
        if ($code < 200 || $code >= 300) {
            throw new \moodle_exception('AI HTTP ' . $code . ': ' . substr((string) $resp, 0, 200));
        }
        $data = json_decode($resp, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('AI returned invalid JSON');
        }
        return is_array($data) ? $data : [];
    }

    private static function call_openai($endpoint, $key, $model, $system, $user): string {
        $j = self::http($endpoint, ['Content-Type: application/json', 'Authorization: Bearer ' . $key], [
            'model' => $model, 'temperature' => 0.7,
            'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $user]],
        ]);
        return self::nonempty(trim($j['choices'][0]['message']['content'] ?? ''));
    }

    private static function call_gemini($endpoint, $key, $model, $system, $user): string {
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

    private static function call_anthropic($endpoint, $key, $model, $system, $user): string {
        $j = self::http($endpoint, ['Content-Type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01'], [
            'model' => $model, 'max_tokens' => 1024, 'system' => $system,
            'messages' => [['role' => 'user', 'content' => $user]],
        ]);
        $blocks = $j['content'] ?? [];
        $text = '';
        foreach ($blocks as $b) {
            $text .= $b['text'] ?? '';
        }
        return self::nonempty(trim($text));
    }

    private static function nonempty(string $text): string {
        if ($text === '') {
            throw new \moodle_exception('AI returned an empty response');
        }
        return $text;
    }
}
