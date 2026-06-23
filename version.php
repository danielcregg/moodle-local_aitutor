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
 * Version metadata for the AI Tutor local plugin.
 *
 * Injects a Socratic AI tutor into STACK quiz-attempt pages. The AI key lives server-side; the
 * browser only calls this plugin's own endpoint. Optionally shows an RL "practise next" suggestion.
 *
 * @package    local_aitutor
 * @copyright  2026 Daniel Cregg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component    = 'local_aitutor';
$plugin->version      = 2026062400;
$plugin->requires     = 2024100700;             // Moodle 4.5 (LTS) — uses the Hooks API.
$plugin->supported    = [405, 405];             // Developed and tested on Moodle 4.5 LTS.
$plugin->maturity     = MATURITY_BETA;
$plugin->release      = '1.0.0-beta';
$plugin->dependencies = [
    'qtype_stack' => ANY_VERSION, // Tutors STACK questions specifically.
];
