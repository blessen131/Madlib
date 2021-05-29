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
 * Edit madlib tab
 *
 * @package    block_madlib
 */

defined('MOODLE_INTERNAL') || exit;

$id = optional_param('id', 0, PARAM_INT);
$pid = optional_param('pid', 0, PARAM_INT);

$menu = $DB->get_records_menu('block_madlib', ['courseid' => $cid], '', 'id, name');
$url->param('action', 'editmadlib');
echo $output->madlib_selector($url, $menu, $pid);

$madlib = $DB->get_record('block_madlib', array('id' => $pid));
$madliboptions = array();
if ($pid > 0) {
    $madliboptions = $DB->get_records('block_madlib_option', array('madlibid' => $pid));
}
$madliboptioncount = count($madliboptions);

echo html_writer::start_tag('form', array('method' => "post", 'action' => $CFG->wwwroot.'/blocks/madlib/madlib_action.php'));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'pid', 'value' => $pid));
$action = $pid == 0 ? 'create' : 'edit';
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'action', 'value' => $action));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'instanceid', 'value' => $instanceid));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $id));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => $USER->sesskey));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'blockaction', 'value' => 'config'));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'course', 'value' => $COURSE->id));

$eligible = array('all' => get_string('all'), 'students' => get_string('students'), 'teachers' => get_string('teachers'));
for ($i = 1; $i <= 10; $i++) {
    $options[$i] = $i;
}

$table = new html_table();
$table->head = array(get_string('config_param', 'block_madlib'), get_string('config_value', 'block_madlib'));
$table->attributes['class'] = 'generaltable boxalignleft';

$stranonresp = get_string('editanonymousresponses', 'block_madlib');
$anoncheck = isset($madlib->anonymous) && $madlib->anonymous == 1 ? 'checked="checked" disabled="disabled"' : '';

$table->data[] = array(get_string('editmadlibname', 'block_madlib'),
    '<input type="text" name="name" value="' . ((!isset($madlib) || !$madlib) ? '' : $madlib->name) . '" />');
$table->data[] = array(get_string('editmadlibquestion', 'block_madlib'),
    '<input type="text" name="questiontext" size="150" value="' . (!$madlib ? '' : $madlib->questiontext) . '" />');
$table->data[] = array($stranonresp, '<input type="checkbox" name="anonymous" alt="'.$stranonresp.'" value="1" '.$anoncheck.' />');
$selected = isset($madlib->eligible) ? $madlib->eligible : 'all';
$table->data[] = array(get_string('editmadlibeligible', 'block_madlib'), html_writer::select($eligible, 'eligible', $selected));
$selected = $pid > 0 ? $madliboptioncount : 5;
$table->data[] = array(get_string('editmadliboptions', 'block_madlib'), html_writer::select($options, 'optioncount', $selected));

$optioncount = 0;
if (is_array($madliboptions)) {
    foreach ($madliboptions as $option) {
        $optioncount++;
        $table->data[] = array(get_string('option', 'block_madlib') . " $optioncount",
            "<input type=\"text\" name=\"options[$option->id]\" value=\"$option->optiontext\" />");
    }
}
for ($i = $optioncount + 1; $i <= $madliboptioncount; $i++) {
    $table->data[] = array(get_string('option', 'block_madlib') . " $i", '<input type="text" name="newoptions[]" />');
}

$table->data[] = array('&nbsp;', '<input type="submit" value="' . get_string('savechanges') . '" />');

echo html_writer::table($table);
echo html_writer::end_tag('form');
