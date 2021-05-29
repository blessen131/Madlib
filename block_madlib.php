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
 * madlib block
 *
 * @package    block_madlib
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/blocks/madlib/locallib.php");
//require_once("$CFG->dirroot/blocks/madlib/madlib.js");
global $PAGE;
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/blocks/madlib/madlib.js'));
/**
 * madlib block
 *
 */
class block_madlib extends block_base {
    
    /**
     * Whether the block has configuration (it does)
     *
     * @return  boolean     We do have configuration
     */
    public function has_config() {
        return true;
    }

    /**
     * Init.
     */
    public function init() {
        $this->title = get_string('formaltitle', 'block_madlib');
    }

    /**
     * Specify that we have instance specific configuration.
     *
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Set the title of our block if one is configured.
     */
    public function specialization() {
        if (!empty($this->config) && !empty($this->config->customtitle)) {
            $this->title = $this->config->customtitle;
        }
    }

    /**
     * Returns the contents.
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        global $COURSE, $DB, $USER;
        if ($this->content !== null) {
            return $this->content;
        }

        if (!isset($this->config->madlibid) || !is_numeric($this->config->madlibid)) {
            $this->content = new stdClass();
            $this->content->text = '';
            $this->content->footer = '';
            return $this->content;
        }

        $renderer = $this->page->get_renderer('block_madlib');

        $madlib = $DB->get_record('block_madlib', array('id' => $this->config->madlibid));
        $options = $DB->get_records('block_madlib_option', array('madlibid' => $madlib->id));
        $response = $DB->get_record('block_madlib_response', array('madlibid' => $madlib->id, 'userid' => $USER->id));
        $maxwidth = !empty($this->config->maxwidth) ? $this->config->maxwidth : 150;

        $this->content = new stdClass();
        $renderable = new block_madlib\output\madlib($this->context, $COURSE->id, $USER, $madlib, $options, $response, $maxwidth);
        $this->content->text = $renderer->render($renderable);
        $this->content->footer = $renderer->footertext($madlib, $this->instance->id, $renderable->canedit);

        return $this->content;
    }

}
