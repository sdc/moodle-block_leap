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
 * Leap block local library functions.
 *
 * @package    block_leap
 * @copyright  2014, 2015 Paul Vaughan {@link http://commoodle.southdevon.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * Gets the auth token, based on the username specified in the block's global config.
 * Takes no parameters, returns a 32-character sting.
 */
function get_auth_token() {
    global $DB;

    // Fail gracefully if there's no appropriate config setting.
    if ( !$auth_username = get_config( 'block_leap', 'auth_username' ) ) {
        return false;
    }

    $auth_token = $DB->get_record_sql('
        SELECT token, validuntil, enabled
        FROM {external_tokens}, {external_services}, {user}
        WHERE {user}.username = :username
            AND {user}.id = {external_tokens}.userid
            AND {external_tokens}.externalserviceid = {external_services}.id
            AND {external_services}.component = :component
            AND {external_services}.enabled = :enabled
            AND
            (
                {external_tokens}.validuntil = 0
                    OR
                {external_tokens}.validuntil > :validuntil
            )
        ',
        array(
            'username'      => $auth_username,
            'component'     => 'local_leapwebservices',
            'enabled'       => 1,
            'validuntil'    => time(),
        )
    );

    return $auth_token;
}