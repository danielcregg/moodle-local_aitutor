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
 * Library callbacks for local_aitutor.
 *
 * Adds a per-quiz "enable AI Tutor" checkbox (off by default) to the quiz settings form, so a teacher
 * turns the tutor on only for the quizzes they choose. The hint button therefore never appears on a
 * quiz nobody opted in — including graded exams. The field is shown only when an administrator has
 * enabled the plugin site-wide (no dead control), and only on quiz modules.
 *
 * @package    local_aitutor
 * @copyright  2026 Daniel Cregg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add the per-quiz opt-in checkbox to the quiz settings form.
 *
 * @param moodleform_mod $formwrapper The activity settings form wrapper.
 * @param MoodleQuickForm $mform The inner form.
 * @return void
 */
function local_aitutor_coursemodule_standard_elements($formwrapper, $mform) {
    $current = $formwrapper->get_current();
    if (empty($current->modulename) || $current->modulename !== 'quiz') {
        return; // Only quizzes carry STACK questions the tutor coaches.
    }
    if (!get_config('local_aitutor', 'enabled')) {
        return; // Only offer the option where it can take effect (the site must enable the tutor first).
    }

    $mform->addElement('header', 'local_aitutor_header', get_string('perquizheading', 'local_aitutor'));
    $mform->addElement(
        'advcheckbox',
        'local_aitutor_enabled',
        get_string('perquizenable', 'local_aitutor'),
        '',
        [],
        [0, 1]
    );
    $mform->addHelpButton('local_aitutor_enabled', 'perquizenable', 'local_aitutor');
    $mform->setDefault('local_aitutor_enabled', 0); // Off by default (covers newly created quizzes).
}

/**
 * Preload the stored value when editing an existing quiz, so a routine save cannot silently flip it.
 *
 * @param moodleform_mod $formwrapper The activity settings form wrapper.
 * @param MoodleQuickForm $mform The inner form.
 * @return void
 */
function local_aitutor_coursemodule_definition_after_data($formwrapper, $mform) {
    $current = $formwrapper->get_current();
    if (empty($current->modulename) || $current->modulename !== 'quiz') {
        return;
    }
    if (!$mform->elementExists('local_aitutor_enabled')) {
        return; // Field not shown (plugin disabled site-wide) — nothing to preload.
    }
    $cmid = (int) ($current->coursemodule ?? 0);
    if ($cmid > 0) {
        $mform->getElement('local_aitutor_enabled')
            ->setValue(\local_aitutor\quiz_settings::is_enabled($cmid) ? 1 : 0);
    }
}

/**
 * Persist the per-quiz opt-in when a quiz is created or updated.
 *
 * @param stdClass $data Submitted module info (includes coursemodule = cmid).
 * @param stdClass $course The course.
 * @return stdClass The (unmodified) module info — Moodle reassigns the return value.
 */
function local_aitutor_coursemodule_edit_post_actions($data, $course) {
    if (empty($data->modulename) || $data->modulename !== 'quiz') {
        return $data;
    }
    // Only act when the field was actually present on the form. When the plugin is disabled site-wide
    // the field is hidden and absent here — in that case we must NOT clear a teacher's saved choice.
    if (property_exists($data, 'local_aitutor_enabled') && !empty($data->coursemodule)) {
        \local_aitutor\quiz_settings::set_enabled((int) $data->coursemodule, !empty($data->local_aitutor_enabled));
    }
    return $data;
}
