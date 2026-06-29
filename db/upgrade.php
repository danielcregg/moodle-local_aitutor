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
 * Upgrade steps for local_aitutor.
 *
 * @package    local_aitutor
 * @copyright  2026 Daniel Cregg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute the local_aitutor upgrade from the given old version.
 *
 * @param int $oldversion The currently installed plugin version.
 * @return bool Always true on success.
 */
function xmldb_local_aitutor_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2026062800) {
        // The new "AI backend" setting defaults to 'auto'. To avoid silently switching a site that is
        // already using this plugin's own provider/key over to core AI, pin such sites to 'own'.
        $current = get_config('local_aitutor', 'aibackend');
        if ($current === false || $current === '') {
            $hasownkey = trim((string) get_config('local_aitutor', 'apikey')) !== '';
            set_config('aibackend', $hasownkey ? 'own' : 'auto', 'local_aitutor');
        }
        upgrade_plugin_savepoint(true, 2026062800, 'local', 'aitutor');
    }

    if ($oldversion < 2026062900) {
        // Per-quiz opt-in for the tutor (off by default): teachers enable it on individual quizzes,
        // so the hint button never appears on a quiz nobody opted in (e.g. graded exams).
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_aitutor_quiz');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('cmid', XMLDB_INDEX_UNIQUE, ['cmid']);
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026062900, 'local', 'aitutor');
    }

    if ($oldversion < 2026062901) {
        // The "practise next" RL banner was removed; drop its now-unused settings.
        unset_config('recommendurl', 'local_aitutor');
        unset_config('recommendtoken', 'local_aitutor');
        upgrade_plugin_savepoint(true, 2026062901, 'local', 'aitutor');
    }

    return true;
}
