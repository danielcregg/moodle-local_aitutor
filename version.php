<?php
// AI Tutor - a thin Moodle client that injects a Socratic AI tutor into STACK quiz
// attempt pages. The AI key lives server-side; the browser only calls this plugin's
// endpoint. Part of Phase 2 of stack-question-forge.
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_aitutor';
$plugin->version   = 2026062301;
$plugin->requires  = 2024042200;   // Moodle 4.4+ (Hooks API)
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1.0';
