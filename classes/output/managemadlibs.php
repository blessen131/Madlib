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
 * Class containing data for madlib block.
 *
 * @package    block_madlib
 */
namespace block_madlib\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Class containing data for managing madlibs.
 
 */
class managemadlibs implements renderable, templatable {

    /** @var int $courseid The course we're managing. */
    protected $courseid;

    /** @var int $instanceid The block instance id */
    protected $instanceid;

    /** @var array $madlibs The list of madlibs in this course. */
    protected $madlibs = [];

    /** @var \moodle_url $url URL of the current page */
    protected $url;

    /**
     * The managemadlibs constructor.
     *
     * @param int $courseid a course id.
     * @param int $instanceid the block instance id
     * @param \moodle_url $url current page url
     * @param array $madlibs the list of madlibs for this course
     */
    public function __construct($courseid, $instanceid, \moodle_url $url, $madlibs = []) {
        $this->courseid = $courseid;
        $this->instanceid = $instanceid;
        $this->madlibs = $madlibs;
        $this->url = $url;
        $this->url->remove_params(['action', 'id']);
        $this->url->param('instanceid', $instanceid);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;
        $rows = [];

        foreach ($this->madlibs as $madlib) {
            $options = $DB->get_records('block_madlib_option', array('madlibid' => $madlib->id));
            $responses = $DB->get_records('block_madlib_response', array('madlibid' => $madlib->id));

            $urlpreview = clone $this->url;
            $urlpreview->params(array('action' => 'responses', 'pid' => $madlib->id));
            $urledit = clone $this->url;
            $urledit->params(array('action' => 'editmadlib', 'pid' => $madlib->id));
            $urldelete = new \moodle_url('/blocks/madlib/madlib_action.php',
                array('action' => 'delete', 'id' => $this->courseid, 'pid' => $madlib->id, 'instanceid' => $this->instanceid));
            $urllock = new \moodle_url('/blocks/madlib/madlib_action.php',
                array('action' => 'lock', 'id' => $this->courseid, 'pid' => $madlib->id, 'instanceid' => $this->instanceid));

            $rows[] = [
                'id' => $madlib->id,
                'title' => $madlib->name,
                'optioncount' => (!$options ? '0' : count($options)),
                'responsecount' => (!$responses ? '0' : count($responses)),
                'urlpreview' => $urlpreview->out(false),
                'urllock' => $urllock->out(false),
                'urledit' => $urledit->out(false),
                'urldelete' => $urldelete->out(false),
            ];
        }

        return [
            'baseurl' => $this->url->out(false),
            'courseid' => $this->courseid,
            'instanceid' => $this->instanceid,
            'rows' => $rows,
        ];
    }
}
