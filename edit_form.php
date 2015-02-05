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
 * Leap Grade Tracking block definition.
 *
 * @package    block_leapgradetracking
 * @copyright  2014, 2015 Paul Vaughan {@link http://commoodle.southdevon.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_leapgradetracking_edit_form extends block_edit_form {
 
    protected function specific_definition( $mform ) {
        
        include( 'details.php' );

        // Section header title according to language file.
        $mform->addElement( 'header', 'configheader', get_string( 'blocksettings', 'block' ) );
 
        // A sample string variable with a default value.
        //$mform->addElement('text', 'config_text', get_string('blockstring', 'block_simplehtml'));
        //$mform->setDefault('config_text', 'default value');
        //$mform->setType('config_text', PARAM_RAW);        
 
        // Drop-dopwn menu of tracker types.
        $mform->addElement( 'select', 'trackertype', get_string( 'tracker_type', 'block_leapgradetracking' ), $trackertypes);
        $mform->setDefault( 'trackertype', 'none' );


    }
}