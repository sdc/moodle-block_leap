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
 * Leap library files.
 *
 * @package    block_leap
 * @copyright  2014, 2015 Paul Vaughan {@link http://commoodle.southdevon.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$settings->add( new admin_setting_configtext(
    'block_leap/leap_url',
    get_string( 'leap_url', 'block_leap' ),
    get_string( 'leap_url_desc', 'block_leap' ),
    '',
    PARAM_URL
));

//$settings->add( new admin_setting_configtext(
//    'block_leap/auth_username',
//    get_string( 'auth_username', 'block_leap' ),
//    get_string( 'auth_username_desc', 'block_leap' ),
//    '',
//    PARAM_USERNAME
//));

$settings->add( new admin_setting_configtext(
    'block_leap/auth_token',
    get_string( 'auth_token', 'block_leap' ),
    get_string( 'auth_token_desc', 'block_leap' ),
    '',
    PARAM_RAW
));

$choices = array( 0 => 'No', 1 => 'Yes' );
$settings->add( new admin_setting_configselect(
    'block_leap/generate_mag',
    get_string( 'generate_mag', 'block_leap' ),
    get_string( 'generate_mag_desc', 'block_leap' ),
    0,
    $choices
));
