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
 * Leap instance config form.
 *
 * @package    block_leap
 * @copyright  2014, 2015 Paul Vaughan {@link http://commoodle.southdevon.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_leap_edit_form extends block_edit_form {

    protected function specific_definition( $mform ) {

        global $CFG, $PAGE;

        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin( 'tokeninput', 'block_leap' );
        $PAGE->requires->js( new moodle_url( '/blocks/leap/js.php' ) );

        $PAGE->requires->css( new moodle_url( '/blocks/leap/css/token-input.css' ) );

        // Section header for course codes for enrolment.
        $mform->addElement( 'header', 'configheader', get_string( 'course_codes_header', 'block_leap' ) );

        $mform->addElement( 'static', 'description',
            get_string( 'course_codes_label', 'block_leap' ),
            get_string( 'course_codes_desc', 'block_leap' ) );

        $attributes = array( 'size' => '20' );

        // Field for course codes, comma-separated.
        $mform->addElement( 'text', 'config_coursecodes', get_string( 'course_codes', 'block_leap' ), $attributes);
        $mform->setType( 'config_coursecodes', PARAM_NOTAGS );

        include( 'details.php' );

        // Section header title according to language file.
        $mform->addElement( 'header', 'configheader', get_string( 'grade_tracking', 'block_leap' ) );

        // Drop-down menu of tracker types.
        $mform->addElement( 'selectgroups', 'config_trackertype', get_string( 'tracker_type', 'block_leap' ), $trackertypes);
        $mform->setDefault( 'config_trackertype', 'none' );

        // Drop-down menu of course types.
        if ( get_config( 'block_leap', 'generate_mag' ) ) {
            $mform->addElement( 'selectgroups', 'config_coursetype', get_string( 'course_type', 'block_leap' ), $coursetypes );
            $mform->setDefault( 'config_coursetype', 'none' );
        }

    }
}
