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
 * Hint endpoint. The browser posts the cmid + question + the student's current answer; we verify
 * the user may attempt that quiz, call the AI server-side (the key never leaves the server), log
 * the hint, and return it as JSON.
 *
 * @package    local_aitutor
 * @copyright  2026 Daniel Cregg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require(__DIR__ . '/../../config.php');

$cmid     = required_param('cmid', PARAM_INT);
$question = core_text::substr(required_param('question', PARAM_RAW), 0, 1500);
$answer   = core_text::substr(required_param('answer', PARAM_RAW), 0, 500);
$feedback = core_text::substr(optional_param('feedback', '', PARAM_RAW), 0, 1000);
$attempt  = max(1, optional_param('attempt', 1, PARAM_INT));

// Access control: real module context + the user must be allowed to attempt this quiz.
[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'quiz');
require_login($course, false, $cm);
require_sesskey();
$context = context_module::instance($cm->id);
require_capability('mod/quiz:attempt', $context);

global $DB, $USER;
header('Content-Type: application/json; charset=utf-8');

if (!get_config('local_aitutor', 'enabled')) {
    echo json_encode(['error' => get_string('tutortemporary', 'local_aitutor')]);
    die();
}

try {
    // Server-side abuse/cost cap: a hard ceiling on hints per user per quiz module.
    // (The per-question escalation cap in the JS is just UX.)
    $maxhints = (int) (get_config('local_aitutor', 'maxhints') ?: 3);
    $ceiling  = max(10, $maxhints * 20);
    if ($DB->count_records('local_aitutor_hints', ['userid' => $USER->id, 'cmid' => $cmid]) >= $ceiling) {
        echo json_encode(['hint' => get_string('hintlimitreached', 'local_aitutor')]);
        die();
    }

    $hint = \local_aitutor\ai_client::hint($question, $answer, $feedback, $attempt);

    // Log the interaction — the data substrate for teaching analytics.
    $DB->insert_record('local_aitutor_hints', (object) [
        'userid' => $USER->id, 'cmid' => $cmid, 'attempt' => $attempt,
        'question' => $question, 'answer' => $answer, 'feedback' => $feedback,
        'hint' => $hint, 'provider' => (string) get_config('local_aitutor', 'provider'),
        'timecreated' => time(),
    ]);

    echo json_encode(['hint' => $hint]);
} catch (\Throwable $e) {
    // Log the detail server-side; return a generic message (no upstream/provider leak).
    debugging('local_aitutor hint failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
    echo json_encode(['error' => get_string('tutortemporary', 'local_aitutor')]);
}
