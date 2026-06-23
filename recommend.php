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
    // Default BKT params per skill [p_init, p_transit, p_slip, p_guess] (= phase3 DEFAULT_SKILLS).
    $bkt = [
        'differentiate'   => [0.20, 0.18, 0.10, 0.15], 'integrate'    => [0.10, 0.10, 0.12, 0.12],
        'expand'          => [0.30, 0.25, 0.08, 0.20], 'factor'       => [0.18, 0.15, 0.12, 0.15],
        'simplify'        => [0.22, 0.20, 0.10, 0.18], 'solve_linear' => [0.35, 0.28, 0.07, 0.22],
        'solve_quadratic' => [0.12, 0.12, 0.13, 0.13], 'numerical'    => [0.25, 0.20, 0.10, 0.20],
    ];

    $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
    $seq = array_fill_keys($skill_order, []);   // skill => [[attempt-start, slot, correct], ...]
    $attemptcount = 0;
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
            $ok = ($qa->get_fraction() !== null && $qa->get_fraction() >= 0.999) ? 1 : 0;
            $seq[$skill][] = [(int) $attempt->timestart, (int) $slot, $ok];
            $attemptcount++;
        }
    }

    // Infer per-skill mastery by a BKT forward filter over the student's answer stream: a Bayes
    // update (bkt_posterior) on each observed answer, then a learning step (bkt_learn) — the
    // principled latent-mastery estimate, not just a hit-rate. No data -> the prior p_init.
    $mastery = [];
    foreach ($skill_order as $s) {
        list($pi, $pt, $ps, $pg) = $bkt[$s];
        $answers = $seq[$s];
        if (!$answers) { $mastery[$s] = round($pi, 3); continue; }
        usort($answers, function ($a, $b) { return ($a[0] <=> $b[0]) ?: ($a[1] <=> $b[1]); });
        $b = $pi;
        foreach ($answers as $a) {
            if ($a[2]) { $num = $b * (1 - $ps); $den = $num + (1 - $b) * $pg; }
            else       { $num = $b * $ps;       $den = $num + (1 - $b) * (1 - $pg); }
            $b = $den > 0 ? $num / $den : $b;          // bkt_posterior — infer from the answer
            $b = $b + (1 - $b) * $pt;                   // bkt_learn — they practised
        }
        $mastery[$s] = round(min(0.999, max(0.0, $b)), 3);
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
        'attempted'  => $attemptcount,
    ]);
} catch (\Throwable $e) {
    debugging('local_aitutor recommend failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
    echo json_encode(['skill' => null]);
}
