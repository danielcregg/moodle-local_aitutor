<?php
// Register the footer hook that injects the tutor JS on quiz-attempt pages.
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook'     => \core\hook\output\before_standard_footer_html_generation::class,
        'callback' => [\local_aitutor\hook_callbacks::class, 'inject_tutor'],
    ],
];
