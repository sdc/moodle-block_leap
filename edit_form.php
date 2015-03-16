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

        include( 'details.php' );

        // Section header title according to language file.
        $mform->addElement( 'header', 'configheader', get_string( 'blocksettings', 'block' ) );

        // Drop-down menu of tracker types.
        $mform->addElement( 'selectgroups', 'config_trackertype', get_string( 'tracker_type', 'block_leap' ), $trackertypes);
        $mform->setDefault( 'config_trackertype', 'none' );

        // Drop-down menu of course types.
        $mform->addElement( 'selectgroups', 'config_coursetype', get_string( 'course_type', 'block_leap' ), $coursetypes );
        $mform->setDefault( 'config_coursetype', 'none' );
    }
}
