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

use context;
use renderable;
use renderer_base;
use templatable;

/**
 * Class containing data for managing madlibs.
 */
class madlib implements renderable {

    /**
     * @var bool Can the user edit this madlib?
     */
    public $canedit = false;

    /**
     * @var int The course id.
     */
    public $courseid;

    /**
     * @var bool Is the user eligible to vote in this madlib right now.
     */
    public $eligible;

    /**
     * @var int The madlib id.
     */
    public $id;

    /**
     * @var int The maximum width (in pixels) of the voting bars.
     */
    public $maxwidth;

    /**
     * @var string The madlib question.
     */
    public $questiontext;

    /**
     * @var array List of voting options.
     */
    public $options;

    /**
     * @var \stdClass The current users response to the madlib.
     */
    public $response;

    /**
     * @var array List of the counts each option got.
     */
    public $results = [];

    /**
     * madlib constructor.
     *
     * @param \context $context
     * @param int $courseid
     * @param \stdClass $user A user record (typically the current user).
     * @param \stdClass $madlib The madlib record.
     * @param array $options List of options records.
     * @param \stdClass|bool $response The users response to the madlib (or false).
     * @param int $maxwidth
     */
    public function __construct($context, $courseid, $user, $madlib, $options, $response, $maxwidth) {
        $this->courseid = $courseid;
        $this->questiontext = $madlib->questiontext;
        $this->maxwidth = $maxwidth;
        $this->options = $options;
        $this->id = $madlib->id;
        $this->response = $response;

        $this->canedit = has_capability('block/madlib:editmadlib', $context);

        $this->load_results();
        $this->eligible = $this->user_eligible($context, $user, $madlib);
    }

    /**
     * Load the list of results for the current madlib.
     *
     * @param bool $sort
     */
    protected function load_results($sort = true) {
        $counts = block_madlib_get_response_counts($this->options);
        foreach ($counts as $optionid => $count) {
            $text = $this->options[$optionid]->optiontext;
            $results[$text] = $count;
        }
        if ($sort) {
            block_madlib_sort_results($results);
        }
        $this->results = $results;
    }

    /**
     * Detemine if the user is elibible to vote in this madlib right now.
     *
     * @param \context $context
     * @param \stdClass $user
     * @param \stdClass $madlib
     * @return bool
     */
    protected function user_eligible($context, $user, $madlib) {
        $parents = $context->get_parent_context_ids();
        $parentctx = context::instance_by_id($parents[0]);

        $switched = false;
        if ($madlib->eligible == 'students') {
            $switched = is_role_switched($this->courseid);
            if ($switched && isset($user->access['rsw'][$parentctx->path])) {
                $switched = !role_context_capabilities($user->access['rsw'][$parentctx->path], $context, 'block/madlib:editmadlib');
            }
        }

        if ($madlib->locked != 0) {
            // No-one gets to vote in a locked madlib.
            return false;
        }

        $studentsonly = $madlib->eligible == 'students' && !$this->canedit;
        $teachersonly = $madlib->eligible == 'teachers' && $this->canedit;

        // A user is eligible to vote if:
        // - madlib is to 'all'.
        // - madlib is set to students and the user cant edit the madlib.
        // - madlib is set to teachers and the user *can* edit the madlib.
        // - madlib is set to students and the user has switched to a student role.
        return $madlib->eligible == 'all' || $studentsonly || $switched || $teachersonly;
    }
}