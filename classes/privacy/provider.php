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
 * Privacy Subsystem implementation for block_madlib.
 *
 * @package    block_madlib
 */

namespace block_madlib\privacy;

use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\helper;
use \core_privacy\local\request\writer;
use \core_privacy\local\metadata\collection;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for block_madlib implementing null_provider.
 *

 */
class provider implements
    // The block_comments block stores user provided data.
    \core_privacy\local\metadata\provider,

    // The block_madlib block provides data directly to core.
    \core_privacy\local\request\plugin\provider {

    // This trait must be included (to support M3.3).
    use \core_privacy\local\legacy_polyfill;

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function _get_metadata(collection $items) {
        $items->add_database_table(
            'block_madlib_response',
            [
                'madlibid' => 'privacy:metadata:block_madlib_response:madlibid',
                'userid' => 'privacy:metadata:block_madlib_response:userid',
                'optionid' => 'privacy:metadata:block_madlib_response:optionid',
                'submitted' => 'privacy:metadata:block_madlib_response:submitted',
            ],
            'privacy:metadata:block_madlib_response'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function _get_contexts_for_userid($userid) {
        // Fetch all madlib comments.
        $sql = "SELECT c.id
                FROM {context} c
                JOIN {block_madlib} b ON b.courseid = c.instanceid AND c.contextlevel = :contextlevel
                JOIN {block_madlib_response} r ON r.madlibid = b.id
                WHERE r.userid = :userid";

        $params = [
            'contextlevel'  => CONTEXT_COURSE,
            'userid'        => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function _export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT b.id as madlibid, b.questiontext, o.optiontext, r.submitted, b.courseid
                FROM {context} c
                JOIN {block_madlib} b ON b.courseid = c.instanceid AND c.contextlevel = :contextlevel
                JOIN {block_madlib_option} o ON o.madlibid = b.id
                JOIN {block_madlib_response} r ON r.madlibid = b.id AND r.optionid = o.id
                WHERE c.id {$contextsql} AND r.userid = :userid
                ORDER BY b.id ASC";
        $params = ['userid' => $user->id, 'contextlevel' => CONTEXT_COURSE] + $contextparams;

        $responses = $DB->get_recordset_sql($sql, $params);
        foreach ($responses as $response) {
            // Users can only make one response per-madlib.
            $data = [
                'madlibid' => $response->madlibid,
                'questiontext' => $response->questiontext,
                'optiontext' => $response->optiontext,
                'submitted' => \core_privacy\local\request\transform::datetime($response->submitted),
            ];
            self::export_madlib_data_for_user($data, \context_course::instance($response->courseid), $user);
        }
        $responses->close();
    }

    /**
     * Export the supplied personal data for a single madlib, along with any generic data or area files.
     *
     * @param array $data the personal data to export for the madlib.
     * @param \context_module $context the context of the madlib.
     * @param \stdClass $user the user record
     */
    protected static function export_madlib_data_for_user(array $data, \context_course $context, \stdClass $user) {
        // Fetch the generic module data for the madlib.
        $contextdata = helper::get_context_data($context, $user);

        // Merge with madlib data and write it.
        $contextdata = (object)array_merge((array)$contextdata, $data);
        $subcontext = [get_string('pluginname', 'block_madlib')];
        writer::with_context($context)->export_data($subcontext, $contextdata);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function _delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (empty($context)) {
            return;
        }

        if (!$context instanceof \context_course) {
            return;
        }

        $select = "madlibid IN (
            SELECT id
            FROM {block_madlib}
            WHERE courseid = :courseid
        )";
        $params = ['courseid' => $context->instanceid];
        $DB->delete_records_select('block_madlib_response', $select, $params);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function _delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            $select = "madlibid IN (
                SELECT id
                FROM {block_madlib}
                WHERE courseid = :courseid
            ) AND userid = :userid";
            $params = [
              'courseid' => $context->instanceid,
              'userid' => $userid,
            ];
            $DB->delete_records_select('block_madlib_response', $select, $params);
        }
    }
}
