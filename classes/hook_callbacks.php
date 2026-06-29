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
 * Hook callbacks for local_aitutor.
 *
 * @package    local_aitutor
 * @copyright  2026 Daniel Cregg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aitutor;

/**
 * Injects the tutor AMD module into quiz-attempt pages via the footer hook.
 */
class hook_callbacks {
    /**
     * Load the tutor JavaScript on STACK quiz-attempt pages.
     *
     * @param \core\hook\output\before_standard_footer_html_generation $hook The footer hook.
     * @return void
     */
    public static function inject_tutor(\core\hook\output\before_standard_footer_html_generation $hook): void {
        global $PAGE;

        if (!get_config('local_aitutor', 'enabled')) {
            return;
        }
        // Only on quiz attempt pages.
        if (strpos($PAGE->pagetype ?? '', 'mod-quiz-attempt') !== 0) {
            return;
        }

        // Per-quiz opt-in: the tutor appears only on quizzes a teacher has explicitly enabled (off by
        // default), so it never loads on a quiz nobody opted in — including graded exams.
        $cmid = isset($PAGE->cm->id) ? (int) $PAGE->cm->id : 0;
        if (!quiz_settings::is_enabled($cmid)) {
            return;
        }

        $config = [
            'ajaxurl'  => (new \moodle_url('/local/aitutor/ajax.php'))->out(false),
            'sesskey'  => sesskey(),
            'cmid'     => $cmid,
            'maxhints' => (int) (get_config('local_aitutor', 'maxhints') ?: 3),
            'label'    => get_string('hintbutton', 'local_aitutor'),
            'strings' => [
                'thinking'    => get_string('hintthinking', 'local_aitutor'),
                'done'        => get_string('hintsdone', 'local_aitutor'),
                'unavailable' => get_string('tutorunavailable', 'local_aitutor'),
            ],
        ];

        $PAGE->requires->js_call_amd('local_aitutor/tutor', 'init', [$config]);
    }
}
