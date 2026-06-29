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
 * English language strings for local_aitutor.
 *
 * @package    local_aitutor
 * @copyright  2026 Daniel Cregg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['aibackend'] = 'AI backend';
$string['aibackend_auto'] = 'Auto — Moodle\'s built-in AI if a provider is configured, otherwise this plugin\'s own provider';
$string['aibackend_core'] = 'Moodle\'s built-in AI (reuses the site\'s configured provider and key)';
$string['aibackend_desc'] = 'Where hints are generated. "Moodle\'s built-in AI" reuses a provider configured under Site administration > AI, so no separate key is needed and Moodle\'s AI policy and logging apply. "This plugin\'s own provider" uses the provider, model and key set below. "Auto" prefers Moodle\'s AI when a core provider is available, otherwise uses this plugin\'s own provider.';
$string['aibackend_own'] = 'This plugin\'s own provider and key (set below)';
$string['aiempty'] = 'The AI returned an empty response.';
$string['aifailed'] = 'The AI request failed: {$a}';
$string['aipolicyrequired'] = 'Please accept this site\'s AI usage policy to use the tutor.';
$string['apikey'] = 'AI API key';
$string['apikey_desc'] = 'Stored server-side and never sent to the browser; the plugin calls the provider from the server.';
$string['enabled'] = 'Enable the AI tutor';
$string['enabled_desc'] = 'Make the AI tutor available on this site. When on, teachers can switch the hint button on per quiz (off by default) in each quiz\'s settings — so it never appears on a quiz nobody opted in, including exams. Stays off until you also configure an AI backend (a provider, model and API key below, or Moodle\'s built-in AI).';
$string['hintbutton'] = 'Hint';
$string['hintlimitreached'] = 'You have reached the hint limit for this quiz. Keep going — you can do it!';
$string['hintsdone'] = 'You have used all available hints for this question. Give it another try!';
$string['hintthinking'] = 'Tutor is thinking…';
$string['maxhints'] = 'Max hints per question';
$string['maxhints_desc'] = 'How many escalating hints a student may request per question.';
$string['model'] = 'Model';
$string['model_desc'] = 'Model id for the chosen provider, e.g. gpt-4o-mini, gemini-2.5-flash, claude-3-5-haiku.';
$string['nokey'] = 'No AI API key is configured for the AI Tutor plugin.';
$string['nomodel'] = 'No AI model is configured for the AI Tutor plugin.';
$string['noprovider'] = 'No valid AI provider is configured for the AI Tutor plugin.';
$string['ownheading'] = 'This plugin\'s own AI provider';
$string['ownheading_desc'] = 'Used when the AI backend is "This plugin\'s own provider", or "Auto" with no core AI provider configured. Ignored when Moodle\'s built-in AI is used.';
$string['perquizenable'] = 'Enable the AI Tutor on this quiz';
$string['perquizenable_help'] = 'When ticked, students see the AI hint button on this quiz\'s STACK questions (and, if the site provides one, the "practise next" suggestion). It is off by default — leave it off for graded tests and exams. This only has an effect if a site administrator has enabled the AI Tutor for the whole site.';
$string['perquizheading'] = 'AI Tutor';
$string['pluginname'] = 'AI Tutor';
$string['policyaccept'] = 'Accept and continue';
$string['policyintro'] = 'Hints on this site are generated through Moodle\'s AI. Please review and accept the AI usage policy to continue.';
$string['privacy:metadata:hints'] = 'A log of AI tutor hints shown to students, kept to improve teaching.';
$string['privacy:metadata:hints:answer'] = 'The answer the student had typed when requesting the hint.';
$string['privacy:metadata:hints:attempt'] = 'The hint attempt number for the question.';
$string['privacy:metadata:hints:cmid'] = 'The course module (quiz) in which the hint was shown.';
$string['privacy:metadata:hints:feedback'] = 'The grader feedback shown for the student answer.';
$string['privacy:metadata:hints:hint'] = 'The hint text generated and shown to the student.';
$string['privacy:metadata:hints:provider'] = 'The AI provider used to generate the hint.';
$string['privacy:metadata:hints:question'] = 'The question text the student was working on.';
$string['privacy:metadata:hints:timecreated'] = 'The time the hint was generated.';
$string['privacy:metadata:hints:userid'] = 'The user who received the hint.';
$string['privacy:metadata:provider'] = 'To generate a hint, the question, the student answer and the grader feedback are sent to an external AI provider. For STACK questions a short qualitative diagnosis of the student answer (whether it is equivalent to a correct answer, differs by a constant, or differs structurally), computed by Maxima from the student answer, is also sent to ground the hint; the model answer and exact values are never sent. When the AI backend is set to Moodle\'s built-in AI, the request is handled by Moodle\'s core AI subsystem (which governs that disclosure) instead of this plugin\'s own provider. This plugin stores no data on the provider.';
$string['privacy:metadata:provider:answer'] = 'The answer the student has currently typed.';
$string['privacy:metadata:provider:diagnosis'] = 'A qualitative classification of the student answer (equivalent / off by a constant / structurally different), derived from the student answer by Maxima.';
$string['privacy:metadata:provider:feedback'] = 'The grader feedback for the student answer.';
$string['privacy:metadata:provider:question'] = 'The question text the student is working on.';
$string['privacy:metadata:recommend'] = 'If the site configures the optional reinforcement-learning "practise next" service, a per-skill mastery profile derived from your quiz attempts is sent to it to suggest what to practise next. No directly identifying information is included.';
$string['privacy:metadata:recommend:mastery'] = 'A per-skill mastery estimate derived from your graded answers in the quiz.';
$string['provider'] = 'AI provider';
$string['provider_desc'] = 'Which external AI service generates the hints. You must also set a model and API key.';
$string['recommendnext'] = 'Practise next';
$string['recommendtail'] = 'suggested by the RL teaching policy';
$string['recommendtoken'] = 'RL service token';
$string['recommendtoken_desc'] = 'Optional. Only needed if the RL teaching-policy URL above is a public route that requires a bearer token. Leave blank for an internal service.';
$string['recommendurl'] = 'RL teaching-policy URL';
$string['recommendurl_desc'] = 'Optional. The /recommend service URL (for example an internal generate service). If set, students see a "what to practise next" suggestion from the trained RL policy, based on their measured per-skill mastery. Leave blank to disable.';
$string['tutortemporary'] = 'The tutor is temporarily unavailable. Please try again in a moment.';
$string['tutorunavailable'] = 'The tutor is unavailable right now. Please try again.';
