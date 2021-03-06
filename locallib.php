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
 * madlib block helper functions
 *
 * @package    block_madlib
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Simple string comparison sorter.
 *
 * @param mixed $a
 * @param mixed $b
 * @return int
 */
function block_madlib_sort_callback($a, $b) {
    return ($a == $b ? 0 : ($a > $b ? -1 : 1));
}

/**
 * Compare the responsecount of each item. Sorts from high to low.
 *
 * @param stdClass $a
 * @param stdClass $b
 * @return int
 */
function block_madlib_custom_callback($a, $b) {
    $counta = $a->responsecount;
    $countb = $b->responsecount;
    return ($counta == $countb ? 0 : ($counta > $countb ? -1 : 1));
}

/**
 * Sort a set of madlib results with a given sorting method.
 *
 * @param array $options
 * @param string $callback
 * @return bool
 */
function block_madlib_sort_results(&$options, $callback = 'block_madlib_sort_callback') {
    return uasort($options, $callback);
}

/**
 * Check if the current user is allowed to edit madlibs in this course.
 *
 * @param int $cid the course id.
 * @return bool
 */
function block_madlib_allowed_to_update($cid = 0) {
    global $COURSE;
    $cid = $cid == 0 ? $COURSE->id : $cid;
    $context = context_course::instance($cid);

    if (has_capability('block/madlib:editmadlib', $context)) {
        return true;
    }
    print_error(get_string('madlibwarning', 'block_madlib'));
}

/**
 * Get a list of how many responses each option has been given.
 *
 * @param array $options
 * @return array list of response counts, keyed on optionid.
 */
function block_madlib_get_response_counts($options) {
    global $DB;

    if (empty($options)) {
        return;
    }

    list ($insql, $params) = $DB->get_in_or_equal(array_keys($options));
    $sql = "SELECT optionid, count(1) AS count
                FROM {block_madlib_response}
                WHERE optionid $insql
                GROUP BY optionid";

    $results = array_fill_keys(array_keys($options), 0);
    foreach ($DB->get_records_sql($sql, $params) as $count) {
        $results[$count->optionid] = $count->count;
    }
    return $results;
}

/**
 * Get a list of madlibs in this course.
 *
 * @param int $courseid the course id
 * @return array list of madlib names, keyed on the madlib id
 */
function block_madlib_course_madlib_list($courseid) {
    global $DB;

    $menu = [];
    $DB->get_records_menu('');
    foreach ($DB->get_records('block_madlib', ['courseid' => $courseid]) as $madlib) {
        $menu[$madlib->id] = $madlib->name;
    }
}