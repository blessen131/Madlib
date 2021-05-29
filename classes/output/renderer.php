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
 * madlib block renderer
 *
 * @package    block_madlib
 */
namespace block_madlib\output;
defined('MOODLE_INTERNAL') || die;

use html_writer;
use moodle_url;
use plugin_renderer_base;

/**
 * madlib block renderer
 *
*/
class renderer extends plugin_renderer_base {

    /**
     * Return the managemadlibs tab content.
     *
     * @param managemadlibs $tab The managemadlibs tab renderable
     * @return string HTML string
     */
    public function render_managemadlibs(managemadlibs $tab) {
        return $this->render_from_template('block_madlib/managemadlibs', $tab->export_for_template($this));
    }

    /**
     * Displays the madlib delete confirmation page.
     *
     * @param stdClass $madlib the madlib record
     * @param string $yes
     * @param string $no
     * @return string
     */
    public function delete_confirmation_page($madlib, $yes, $no) {
        $html = $this->output->header();
        $message = get_string('madlibconfirmdelete', 'block_madlib', $madlib->name);
        $html .= $this->output->confirm($message, $yes, $no);
        $html .= $this->output->footer();
        return $html;
    }

    /**
     * Displays the madlib lock confirmation page.
     *
     * @param stdClass $madlib the madlib record
     * @param string $yes
     * @param string $no
     * @return string
     */
    public function lock_confirmation_page($madlib, $yes, $no) {
        $html = $this->output->header();
        $message = get_string('madlibconfirmlock', 'block_madlib', $madlib->name);
        $html .= $this->output->confirm($message, $yes, $no);
        $html .= $this->output->footer();
        return $html;
    }

    /**
     * Display the madlib block.
     *
     * @param madlib $madlib
     * @return string
     */
    public function render_madlib(madlib $madlib) {
        $html = '<table cellspacing="2" cellpadding="2">';     
         
        $func = 'madlib_results';
        if (!$madlib->response && $madlib->eligible) {
          // $html .= '<tr><th>' . $madlib->questiontext . '</th></tr>';
            $func = 'madlib_options';
        }
        $html .= $this->$func($madlib);

        $html .= '</table>';

        return $html;
    }

    /**
     * Render the content to display beneath the madlib options.
     *
     * @param \stdClass $madlib The madlib record.
     * @param int $instanceid The block instance id.
     * @param bool $canedit The user has madlib editing capabilities.
     * @return string
     */
    public function footertext($madlib, $instanceid, $canedit) {
        $html = '';

        if ($canedit) {
            $html .= $this->results_link($instanceid, $madlib);
        }

        $class = 'error';
        $strid = 'notanonymous';
        if (!empty($madlib->anonymous)) {
            $class = 'success';
            $strid = 'useranonymous';
        }
        $html .= \html_writer::div(get_string($strid, 'block_madlib'), "center alert alert-block fade in alert-{$class}");
        return $html;
    }

    /**
     * Link to view the madlib responses.
     *
     * @param int $instanceid The block instance id.
     * @param stdClass $madlib The madlib record.
     * @return string
     */
    public function results_link($instanceid, $madlib) {
        $url = new moodle_url('/blocks/madlib/tabs.php', ['action' => 'responses', 'pid' => $madlib->id, 'instanceid' => $instanceid]);
        $html = html_writer::empty_tag('hr');
       $html .= html_writer::link($url, get_string('responses', 'block_madlib'));
        return $html;
    }

    /**
     * Display the list of madlib options so the user can cast their vote.
     *
     * @param madlib $madlib The madlib renderable.
     * @return string
     */
    public function madlib_options(madlib $madlib) {
        $html = '';
        $url = new moodle_url('/blocks/madlib/madlib_action.php');
        $html = html_writer::start_tag('form', ['method' => 'get', 'action' => $url]);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'respond']);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'pid', 'value' => $madlib->id]);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $madlib->courseid]);
        

        foreach ($madlib->options as $option) {
             $label = html_writer::label($option->optiontext, "r_{$option->id}");
            $html .= "<tr><td>Enter {$label}</td>";
            $input = html_writer::empty_tag('input',
                ['type' => 'text', 'id' => "r_{$option->id}", 'name' => 'rid[]']);
            $html .= "<td>{$input}</td></tr>";
        }
        $html .= '<tr><td>';
        $html .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('submit', 'block_madlib')]);
        $html .= '</td></tr>';
        $html .= html_writer::end_tag('form');

        return $html;
    }

    /**
     * Display the madlib results. For after the user has cast their vote, or is ineligible to vote themselves.
     *
     * @param madlib $madlib
     * @return string
     */
    public function madlib_results(madlib $madlib) {
        $option_data=$madlib->response;
        $option_array=json_decode($option_data->optionid);
        $result_array=$madlib->results;
        foreach($result_array as $key=>$val)
        {
             $results_array[]=$key;
        }
        $storytext=$madlib->questiontext;
        $story = str_replace($results_array, $option_array, $storytext);
        $html = '';
        $html .= '<tr><th>Story:' .$story. '</th></tr>';
        $html .= "<tr><td>No new Mad lib available for you</td></tr>";
        
        return $html;
    }

    /**
     * Display a bar representing the ratio of votes for this option.
     *
     * @param string $img
     * @param string $width
     * @return string
     */
    public function madlib_graphbar($img = '0', $width = '100') {
        $html = $this->pix_icon("graph{$img}", '', 'block_madlib', array('style' => "width: {$width}px; height: 15px;"));
        $html .= html_writer::empty_tag('br');
        return $html;
    }

    /**
     * Get a list of checkmarks indicating which option a user chose.
     *
     * @param array $options List of options
     * @param int $selected option id the user selected
     * @return array
     */
    public function get_response_checks($options, $selected) {
        $arr = [];
        foreach ($options as $option) {
            $arr[] = html_writer::checkbox('', '', $option->id == $selected, '',
                ['onclick' => 'this.checked='.($option->id == $selected ? 'true' : 'false')]);
        }
        return $arr;
    }

    /**
     * Drop down selector of madlibs to choose from on the management tabs.
     *
     * @param moodle_url $url
     * @param array $menu List of madlibs.
     * @param int $pid The currently selected madlib (if any).
     * @return string
     */
    public function madlib_selector($url, $menu, $pid) {
        $html = $this->box_start();
        $html .= html_writer::tag('div', get_string('editmadlibname', 'block_madlib') . ': ', ['class' => 'field_title']);
        $html .= $this->single_select($url, 'pid', $menu, $pid);
        $html .= $this->box_end();
        return $html;
    }
}
