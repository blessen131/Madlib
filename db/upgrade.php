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
 * madlib block upgrade steps.
 *
 * @package    block_madlib
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Perform block_madlib's upgrade steps.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_block_madlib_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Add a new column for anonymous madlibs.
    if ($oldversion < 2011041400) {
        $table = new xmldb_table('block_madlib');
        $field = new xmldb_field('anonymous');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'created');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2011041400, 'madlib');
    }

    // Add a new column for lockint madlibs.
    if ($oldversion < 2017031600) {
        $table = new xmldb_table('block_madlib');
        $field = new xmldb_field('locked');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'anonymous');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2017031600, 'madlib');
    }

    return true;
}
