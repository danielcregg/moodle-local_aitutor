<?php
// Phase 3 endpoint: measure this student's per-skill mastery from their real quiz attempts, ask
// the trained RL teaching policy what to practise next, and return it. Additive — it does not
// touch the hint flow. The policy lives in an external service (decoupled, as ever).
define('AJAX_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/questionlib.php');

$cmid = required_param('cmid', PARAM_INT);
list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
require_login($course, false, $cm);
require_sesskey();
require_capability('mod/quiz:attempt', context_module::instance($cm->id));

global $DB, $USER;
header('Content-Type: application/json; charset=utf-8');

if (!get_config('local_aitutor', 'enabled')) {
    echo json_encode(['skill' => null]);
    die();
}

// Canonical phase3 skill order + the question-name -> skill map (same as the exporter).
$skill_order = ['differentiate', 'integrate', 'expand', 'factor', 'simplify',
                'solve_linear', 'solve_quadratic', 'numerical'];
$name_to_skill = [
    'Differentiate'            => 'differentiate',
    'Find an antiderivative'   => 'integrate',
    'Expand'                   => 'expand',
    'Factorise'                => 'factor',
    'Simplify to lowest terms' => 'simplify',
    'Solve a linear equation'  => 'solve_linear',
    'Solve a quadratic'        => 'solve_quadratic',
    'Evaluate to a decimal'    => 'numerical',
];
$skill_label = [
    'differentiate' => 'Differentiation', 'integrate' => 'Integration', 'expand' => 'Expanding',
    'factor' => 'Factorising', 'simplify' => 'Simplifying', 'solve_linear' => 'Linear equations',
    'solve_quadratic' => 'Quadratics', 'numerical' => 'Numerical evaluation',
];

try {
    $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
    $correct = array_fill_keys($skill_order, 0);
    $total = array_fill_keys($skill_order, 0);

    foreach ($DB->get_records('quiz_attempts', ['quiz' => $quiz->id, 'userid' => $USER->id]) as $attempt) {
        $quba = question_engine::load_questions_usage_by_activity($attempt->uniqueid);
        foreach ($quba->get_slots() as $slot) {
            $qa = $quba->get_question_attempt($slot);
            $skill = null;
            foreach ($name_to_skill as $phrase => $s) {
                if (stripos($qa->get_question()->name, $phrase) !== false) { $skill = $s; break; }
            }
            if (!$skill || !$qa->get_state()->is_graded()) {
                continue;
            }
            $total[$skill]++;
            if ($qa->get_fraction() !== null && $qa->get_fraction() >= 0.999) {
                $correct[$skill]++;
            }
        }
    }

    // Mastery estimate: fraction correct; skills with no data default low so the policy steers
    // the student toward starting them.
    $mastery = [];
    foreach ($skill_order as $s) {
        $mastery[$s] = $total[$s] > 0 ? round($correct[$s] / $total[$s], 3) : 0.15;
    }

    $url = rtrim((string) get_config('local_aitutor', 'recommendurl'), '/');
    if ($url === '') { echo json_encode(['skill' => null]); die(); }

    // ignoresecurity: the recommend URL is an admin-configured, trusted endpoint (e.g. the
    // internal generate service); force IPv4 (no IPv6 egress in the Moodle container).
    $curl = new \curl(['ignoresecurity' => true]);
    $headers = ['Content-Type: application/json'];
    $token = (string) get_config('local_aitutor', 'recommendtoken');
    if ($token !== '') {                       // needed only if recommendurl is the public Caddy route
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    $curl->setHeader($headers);
    $resp = $curl->post($url . '/recommend', json_encode(['mastery' => $mastery]),
        ['CURLOPT_TIMEOUT' => 15, 'CURLOPT_IPRESOLVE' => CURL_IPRESOLVE_V4]);
    $rec = json_decode((string) $resp, true);
    if (!is_array($rec)) { echo json_encode(['skill' => null]); die(); }

    // Don't trust the service across the Moodle boundary: only return a known skill/difficulty
    // (the banner is built from these, so this also closes any XSS via a hostile response).
    $skill = $rec['skill'] ?? null;
    if ($skill !== null && !in_array($skill, $skill_order, true)) { $skill = null; }
    $difficulty = $rec['difficulty'] ?? null;
    if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) { $difficulty = null; }
    echo json_encode([
        'skill'      => $skill,
        'label'      => $skill ? ($skill_label[$skill] ?? $skill) : null,
        'difficulty' => $difficulty,
        'source'     => $rec['source'] ?? null,
        'attempted'  => array_sum($total),
    ]);
} catch (\Throwable $e) {
    debugging('local_aitutor recommend failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
    echo json_encode(['skill' => null]);
}
