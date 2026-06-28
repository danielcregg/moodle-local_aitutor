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

$string['aiempty'] = 'The AI returned an empty response.';
$string['aifailed'] = 'The AI request failed: {$a}';
$string['apikey'] = 'AI API key';
$string['apikey_desc'] = 'Stored server-side and never sent to the browser; the plugin calls the provider from the server.';
$string['enabled'] = 'Enable the AI tutor';
$string['enabled_desc'] = 'Inject the Socratic AI tutor into STACK quiz attempt pages. Stays off until you also set a provider, model and API key below.';
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
$string['pluginname'] = 'AI Tutor';
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
$string['privacy:metadata:provider'] = 'To generate a hint, the question, the student answer and the grader feedback are sent to the configured external AI provider. For STACK questions a short qualitative diagnosis of the student answer (whether it is equivalent to a correct answer, differs by a constant, or differs structurally), computed by Maxima from the student answer, is also sent to ground the hint; the model answer and exact values are never sent. This plugin stores no data on the provider.';
$string['privacy:metadata:provider:diagnosis'] = 'A qualitative classification of the student answer (equivalent / off by a constant / structurally different), derived from the student answer by Maxima.';
$string['privacy:metadata:provider:answer'] = 'The answer the student has currently typed.';
$string['privacy:metadata:provider:feedback'] = 'The grader feedback for the student answer.';
$string['privacy:metadata:provider:question'] = 'The question text the student is working on.';
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
