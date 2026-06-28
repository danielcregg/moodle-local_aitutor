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

    return true;
}
