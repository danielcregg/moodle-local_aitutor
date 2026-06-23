<?php
// Hint endpoint. The browser posts the cmid + question + the student's current answer; we
// verify the user may attempt that quiz, call the AI server-side (key never leaves the
// server), log the hint, and return it as JSON. The AI key is never sent to the browser.
define('AJAX_SCRIPT', true);
require(__DIR__ . '/../../config.php');

$cmid     = required_param('cmid', PARAM_INT);
$question = core_text::substr(required_param('question', PARAM_RAW), 0, 1500);
$answer   = core_text::substr(required_param('answer', PARAM_RAW), 0, 500);
$feedback = core_text::substr(optional_param('feedback', '', PARAM_RAW), 0, 1000);
$attempt  = max(1, optional_param('attempt', 1, PARAM_INT));

// Access control: real module context + the user must be allowed to attempt this quiz.
list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
require_login($course, false, $cm);
require_sesskey();
$context = context_module::instance($cm->id);
require_capability('mod/quiz:attempt', $context);

global $DB, $USER;
header('Content-Type: application/json; charset=utf-8');

try {
    if (!get_config('local_aitutor', 'enabled')) {
        throw new \moodle_exception('AI tutor disabled');
    }

    // Server-side abuse/cost cap: a hard ceiling on hints per user per quiz module.
    // (The per-question escalation cap in tutor.js is just UX.)
    $maxhints = (int) (get_config('local_aitutor', 'maxhints') ?: 3);
    $ceiling  = max(10, $maxhints * 20);
    if ($DB->count_records('local_aitutor_hints', ['userid' => $USER->id, 'cmid' => $cmid]) >= $ceiling) {
        echo json_encode(['hint' => 'You have reached the hint limit for this quiz. Keep going — you can do it!']);
        die();
    }

    $hint = \local_aitutor\ai_client::hint($question, $answer, $feedback, $attempt);

    // Log the interaction - the Phase 3 (RL teaching agent) data substrate.
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
    echo json_encode(['error' => 'The tutor is temporarily unavailable. Please try again in a moment.']);
}
