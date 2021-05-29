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
 * madlib action controller.
 *
 * @package    block_madlib
 
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once('locallib.php');

$action = required_param('action', PARAM_ALPHA);
$pid = optional_param('pid', 0, PARAM_INT);
$cid = required_param('id', PARAM_INT);
$srcpage = optional_param('page', '', PARAM_TEXT);
if ($cid == 0) {
    if (!$cid = optional_param('course', 0, PARAM_INT)) {
        $cid = SITEID;
    }
}
$instanceid = optional_param('instanceid', 0, PARAM_INT);

require_login($cid);

$sesskey = $USER->sesskey;
$mymoodleref = strpos($_SERVER["HTTP_REFERER"], $CFG->wwwroot.'/my/') !== false
    || strpos($_SERVER["HTTP_REFERER"], $CFG->wwwroot.'/admin/stickyblocks.php') !== false;
$stickyblocksref = strpos($_SERVER["HTTP_REFERER"], $CFG->wwwroot.'/my/indexsys.php') !== false;
$context = context_course::instance($cid);
$pageurl = new moodle_url('/blocks/madlib/madlib_action.php',
    array('action' => $action, 'id' => $cid, 'pid' => $pid, 'instanceid' => $instanceid));
$PAGE->set_context($context);
$PAGE->set_url($pageurl);

if ($stickyblocksref) {
    $url = new moodle_url('/my/indexsys.php', array('pt' => 'my-index'));
} else if ($mymoodleref) {
    $url = new moodle_url('/my/index.php');
} else {
    $url = new moodle_url('/course/view.php', array('id' => $cid));
}

$tabs = array('create', 'lock', 'edit', 'delete');
if (in_array($action, $tabs)) {
    $url = new moodle_url('/blocks/madlib/tabs.php');
}

switch ($action) {
    case 'create':
        block_madlib_allowed_to_update($cid);
        $madlib = new stdClass();
        $madlib->id = $pid;
        $madlib->name = required_param('name', PARAM_TEXT);
        $madlib->courseid = $cid;
        $madlib->questiontext = required_param('questiontext', PARAM_TEXT);
        $madlib->eligible = required_param('eligible', PARAM_ALPHA);
        $madlib->created = time();
        $madlib->anonymous = optional_param('anonymous', 0, PARAM_INT);
        $newid = $DB->insert_record('block_madlib', $madlib, true);
        $optioncount = optional_param('optioncount', 0, PARAM_INT);
        for ($i = 0; $i < $optioncount; $i++) {
            $madlibopt = new stdClass();
            $madlibopt->id = 0;
            $madlibopt->madlibid = $newid;
            $madlibopt->optiontext = '';
            $DB->insert_record('block_madlib_option', $madlibopt);
        }
        $url->params(array(
            'instanceid' => $instanceid,
            'sesskey' => $sesskey,
            'blockaction' => 'config',
            'action' => 'editmadlib',
            'pid' => $newid,
        ));
        break;
    case 'lock':
        block_madlib_allowed_to_update($cid);
        $step = optional_param('step', 'first', PARAM_TEXT);
        $urlno = clone $url;
        $urlno->params(array(
            'instanceid' => $instanceid,
            'sesskey' => $sesskey,
            'blockaction' => 'config',
            'action' => 'managemadlibs',
        ));
        if ($step == 'confirm') {
            $sql = 'UPDATE {block_madlib}
                    SET locked = 1
                    WHERE id = :pid';
            $DB->execute($sql, array('pid' => $pid));
            $url = $urlno;
        } else {
            $madlib = $DB->get_record('block_madlib', array('id' => $pid));
            $yesparams = array('id' => $cid, 'instanceid' => $instanceid, 'action' => 'lock', 'step' => 'confirm', 'pid' => $pid);
            $urlyes = new moodle_url('/blocks/madlib/madlib_action.php', array(
                'id' => $cid,
                'instanceid' => $instanceid,
                'action' => 'lock',
                'step' => 'confirm',
                'pid' => $pid,
            ));
            if ($srcpage != '') {
                $urlyes->param('page', $srcpage);
            }

            $renderer = $PAGE->get_renderer('block_madlib');
            echo $renderer->lock_confirmation_page($madlib, $urlyes, $urlno);
            exit;
        }
        break;
    case 'edit':
        block_madlib_allowed_to_update($cid);
        $madlib = $DB->get_record('block_madlib', array('id' => $pid));
        $madlib->name = required_param('name', PARAM_TEXT);
        $madlib->questiontext = required_param('questiontext', PARAM_TEXT);
        $madlib->eligible = required_param('eligible', PARAM_ALPHA);
        if ($madlib->anonymous == 0) { // Only allow one way setting of anonymous.
            $madlib->anonymous = optional_param('anonymous', 0, PARAM_INTEGER);
        }
        $DB->update_record('block_madlib', $madlib);
        $options = optional_param_array('options', array(), PARAM_RAW);
        foreach (array_keys($options) as $option) {
            $madlibopt = $DB->get_record('block_madlib_option', array('id' => $option));
            $madlibopt->optiontext = $options[$option];
            $DB->update_record('block_madlib_option', $madlibopt);
        }
        $optioncount = optional_param('optioncount', 0, PARAM_INTEGER);
        if (count($options) > $optioncount) {
            $temp = 1;
            foreach ($options as $optid => $optname) {
                if ($temp++ > $optioncount) {
                    break;
                }
                $safe[] = $optid;
            }

            list($insql, $params) = $DB->get_in_or_equal($safe, SQL_PARAMS_NAMED);
            $insql = count($params) > 1 ? "NOT $insql" : "!$insql";
            $params['pid'] = $pid;
            $DB->delete_records_select('block_madlib_option', "madlibid = :pid AND id $insql", $params);
        }
        for ($i = count($options); $i < $optioncount; $i++) {
            $madlibopt = new stdClass();
            $madlibopt->id = 0;
            $madlibopt->madlibid = $pid;
            $madlibopt->optiontext = '';
            $DB->insert_record('block_madlib_option', $madlibopt);
        }
        $url->params(array(
            'instanceid' => $instanceid,
            'sesskey' => $sesskey,
            'blockaction' => 'config',
            'action' => 'editmadlib',
            'pid' => $pid,
        ));
        break;
    case 'delete':
        block_madlib_allowed_to_update($cid);
        $step = optional_param('step', 'first', PARAM_TEXT);
        $urlno = clone $url;
        $urlno->params(array(
            'instanceid' => $instanceid,
            'sesskey' => $sesskey,
            'blockaction' => 'config',
            'action' => 'managemadlibs',
        ));
        if ($step == 'confirm') {
            $DB->delete_records('block_madlib', array('id' => $pid));
            $DB->delete_records('block_madlib_option', array('madlibid' => $pid));
            $DB->delete_records('block_madlib_response', array('madlibid' => $pid));
            $url = $urlno;
        } else {
            $madlib = $DB->get_record('block_madlib', array('id' => $pid));
            $yesparams = array('id' => $cid, 'instanceid' => $instanceid, 'action' => 'delete', 'step' => 'confirm', 'pid' => $pid);
            $urlyes = new moodle_url('/blocks/madlib/madlib_action.php', array(
                'id' => $cid,
                'instanceid' => $instanceid,
                'action' => 'delete',
                'step' => 'confirm',
                'pid' => $pid,
            ));
            if ($srcpage != '') {
                $urlyes->param('page', $srcpage);
            }

            $renderer = $PAGE->get_renderer('block_madlib');
            echo $renderer->delete_confirmation_page($madlib, $urlyes, $urlno);
            exit;
        }
        break;
    case 'respond':
        if (!$DB->get_record('block_madlib_response', array('madlibid' => $pid, 'userid' => $USER->id))) {
            $response = new stdClass();
            $response->id = 0;
            $response->madlibid = $pid;
            $inputfields=required_param('rid', PARAM_TEXT);              
            $response->optionid = json_encode($inputfields);
            $response->userid = $USER->id;
            $response->submitted = time();
            $DB->insert_record('block_madlib_response', $response);
        }
        break;
}

redirect($url);
