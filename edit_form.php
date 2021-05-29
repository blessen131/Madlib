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
 * Form for editing the madlib block.
 *
 * @package    block_madlib
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class for defining the madlib blocks edit form.
 *
 */
class block_madlib_edit_form extends block_edit_form {

    /**
     * Define the custom fields to display when editing a madlib block.
     *
     * @param moodleform $mform
     */
    protected function specific_definition($mform) {
        global $COURSE, $DB;
        // Fields for editing madlib block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_customtitle', get_string('configtitle', 'block_html'));
        $mform->setType('config_customtitle', PARAM_TEXT);

        if ($madlibs = $DB->get_records('block_madlib', array('courseid' => $COURSE->id), '', 'id, name')) {
            $list = array(0 => get_string('choose', 'block_madlib'));
            foreach ($madlibs as $madlib) {
                $list[$madlib->id] = $madlib->name;
            }
            $mform->addElement('select', 'config_madlibid', get_string('editmadlibname', 'block_madlib'), $list);
        } else {
            $mform->addElement('static', 'nomadlibs', get_string('editmadlibname', 'block_madlib'),
                get_string('nomadlibsavailable', 'block_madlib'));
        }

        $mform->setType('config_maxwidth', PARAM_INT);
        $mform->addElement('text', 'config_maxwidth', get_string('editmaxbarwidth', 'block_madlib'));

        $tabs = array('editmadlib', 'managemadlibs', 'responses');
        foreach ($tabs as $tab) {
            $params = array('action' => $tab, 'cid' => $COURSE->id, 'instanceid' => $this->block->instance->id);
            $link = html_writer::link(new moodle_url('/blocks/madlib/tabs.php', $params), get_string("tab$tab", 'block_madlib'));
            $mform->addElement('static', "linki_$tab", '', $link);
        }
    }
}
