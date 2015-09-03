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
 * Leap block definition.
 *
 * @package    block_leap
 * @copyright  2014, 2015 Paul Vaughan {@link http://commoodle.southdevon.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined( 'MOODLE_INTERNAL' ) || die();

include_once( 'locallib.php' );

class block_leap extends block_base {

    public function init() {
        $this->title = get_string( 'pluginname', 'block_leap' );
    }

    // More than one of these blocks per course will cause problems.
    public function instance_allow_multiple() {
        return false;
    }

    // Global config.
    function has_config() {
        return true;
    }

    // Hide the textual leader in the future?
    public function hide_header() {
        return false;
    }

    // Show only in courses.
    // Unsure how many of these options we should disallow? More than one will break things.
    // TODO: research and disallow inappropriate locations for this block.
    public function applicable_formats() {
        return array(
            'course-view'   => true,    // All main course pages.
            'mod'           => false,   // Module pages.
            'site'          => false,   // Front page.
        );
    }

    // Using the Task API instead.
    public function cron() {
        // ...
    }

    public function get_content() {
        global $CFG, $COURSE, $DB, $PAGE;

        // The define() statements were being defined twice, apparently, when not in edit mode. So:
        $tick   = '<span style="color: #3b3;">&#10003;</span>';
        $cross  = '<span style="color: #f00;">&#10007;</span>';

        if ( $this->content !== null ) {
            return $this->content;
        }

        // Removing the trailing slash from the supplied URL if present.
        // TODO: This is fine, but only (and always) runs when the block is loaded on a course.
        $tmp_leap_url = get_config( 'block_leap', 'leap_url' );
        if ( !empty( $tmp_leap_url ) ) {
            if ( substr( $tmp_leap_url, -1) == '/' ) {
                $tmp_leap_url = substr( $tmp_leap_url, 0, -1);
                get_config( 'leap_url', $tmp_leap_url, 'block_leap' );
            }
        }

        $this->content          =  new stdClass;
        $this->content->text    = '';
        $this->content->footer  = '';

        // https://docs.moodle.org/dev/Roles
        $coursecontext = context_course::instance( $COURSE->id );

        // Require a user to be logged in for this block to be of any use.
        // Even a link to Leap's not that useful as a guest wouldn't be able to log in...
        if ( is_guest( $coursecontext ) ) {
           return $this->content;
           exit;
        }

        $this->content->text   = '<p style="text-align:center;"><a target="_blank" href="' . get_config( 'block_leap', 'leap_url' ) . '"><img src="'.$CFG->wwwroot.'/blocks/leap/pix/logo.png"></a></p>';

/*
        // TODO: Apparently the best way forward is to roll our own capabilities. Maybe one day.
        //if( has_capability( 'block/leap:editconfig', $coursecontext ) ) {

        // If the user is an admin AND editing mode is turned on, do sanity checks.
        if( has_capability( 'moodle/site:config', $coursecontext ) && $PAGE->user_is_editing() ) {

            $this->content->text .= '<p><strong>' . get_string( 'settings:global', 'block_leap' ) . '</strong></p>';

            // TODO: pre-save processing to check for 'http(s)://' and potentially remove '/'?
            // TODO: quick check to make sure the URL, if exists, is real and pingable?
            $leap_url = get_config( 'block_leap', 'leap_url' );
            if ( empty( $leap_url ) ) {
                // 'leap_url' config field empty.
                $this->content->text .= '<p><em>Global setting "leap_url" not set!</em> ' . $cross . ' [<a href="' . $CFG->wwwroot . '/admin/settings.php?section=blocksettingleap">settings</a>]</p>';
            } else {
                // 'leap_url' config field populated with something.
                $this->content->text .= '<p>"leap_url" set [' . $leap_url . ']. ' . $tick . '</p>';
            }


            $auth_username = get_config( 'block_leap', 'auth_username' );
            if ( empty( $auth_username ) ) {
                // 'auth_username' config field empty.
                $this->content->text .= '<p><em>Global setting "auth_username" not set!</em> ' . $cross . ' [<a href="' . $CFG->wwwroot . '/admin/settings.php?section=blocksettingleap">settings</a>]</p>';

            } else {
                // 'auth_username' config field populated with something.
                $this->content->text .= '<p>"auth_username" set [' . $auth_username . ']. ' . $tick . '</p>';

                $auth_userid = $DB->get_record( 'user', array( 'username' => $auth_username ), 'id' );
                //var_dump($auth_userid->id); die();
                if ( empty( $auth_userid ) ) {
                    // 'auth_username' doesn't relate to a user in the database.
                    $this->content->text .= '<p><em>[' . $auth_username . '] not found in database!</em>' . $cross . '</p>';

                } else {
                    // 'auth_username' relates to a user in the database.
                    $this->content->text .= '<p>"' . $auth_username . '" equates to user id ' . $auth_userid->id . '. ' . $tick . '</p>';
                    //$auth_token = $DB->get_record( 'external_tokens', array( 'userid' => $auth_userid->id ) );

                    // Checking for a valid user for a specific, enabled component.
                    $auth_token = get_auth_token();

                    //$auth_token = $DB->get_record_sql('
                    //    SELECT token, validuntil, enabled
                    //    FROM {external_tokens}, {external_services}
                    //    WHERE {external_tokens}.externalserviceid = {external_services}.id
                    //        AND {external_services}.component = :component
                    //        AND {external_services}.enabled = :enabled
                    //        AND {external_tokens}.userid = :userid
                    //    ',
                    //    array( 'component' => 'local_leapwebservices', 'enabled' => 1, 'userid' => $auth_userid->id )
                    //    );
                    //

                    if ( empty( $auth_token ) ) {
                        // No external token found in the database for that user.
                        $this->content->text .= '<p><em>No external token found in database for userid ' . $auth_userid->id . '!</em>' . $cross . '</p>';

                    } else {
                        // External token found in the database for that user.
                        $this->content->text .= '<p>External token [' . $auth_token->token . '] found in database for userid ' . $auth_userid->id . '. ' . $tick . '</p>';

                        // There are some checks surrounding external keys - is it worth abiding by them?
                        //
                        // * IP restriction - no, as it's a plugin on the same server, not an external system.
                        // * valid until - probably... If the key's set to expire, we shouldn't use it after it has.
                        if ( $auth_token->validuntil == 0 ) {
                            $this->content->text .= '<p>Token has no expiry date. ' . $tick . '</p>';

                        } else if ( $auth_token->validuntil > time() ) {
                            $this->content->text .= '<p>Token is in date. ' . $tick . '</p>';

                        } else {
                            $this->content->text .= '<p><em>Token has expired!</em> ' . $cross . '</p>';
                        }
                    }
                }
            }

            $this->content->text .= '<hr>';

        } // END block sanity checks.
*/

/*
        // Quick report on what the block has been set to, for admins and teachers.
        if( has_capability( 'moodle/site:config', $coursecontext ) || has_capability( 'moodle/course:update', $coursecontext ) ) {

            $build = '<p><strong>' . get_string( 'settings:course', 'block_leap' ) . '</strong></p><p>';

            if ( isset( $this->config->trackertype ) && !empty( $this->config->trackertype) ) {
                $build .= get_string( 'tracker_type', 'block_leap' ) . ': ' . get_string( 'tracker_type:' . $this->config->trackertype, 'block_leap' ) . '. ' . $tick;
            } else {
                $build .= get_string( 'tracker_type', 'block_leap' ) . ': ' . get_string( 'error:notconf', 'block_leap' ) . '. ' . $cross;
            }

            if ( get_config( 'block_leap', 'generate_mag' ) ) {

                $build .= '<br>';

                if ( isset( $this->config->coursetype ) && !empty( $this->config->coursetype) ) {
                    $build .= get_string( 'course_type', 'block_leap' ) . ': ' . get_string( 'course_type:' . $this->config->coursetype, 'block_leap' ) . '. ' . $tick;
                } else {
                    $build .= get_string( 'course_type', 'block_leap' ) . ': ' . get_string( 'error:notconf', 'block_leap' ) . '. ' . $cross;
                }

            }

            $build .= '</p><hr>';

            $this->content->text .= $build;

        } // END quick report.
*/

/*
        // Doing actual block stuff here.
        if( has_capability( 'moodle/site:config', $coursecontext ) ) {
            $this->content->text .= '<p>This user is a Site Admin.</p>';

        } else if( has_capability( 'moodle/course:update', $coursecontext ) ) {
            $this->content->text .= '<p>This user is a Teacher.</p>';

        } else {
            $this->content->text .= '<p>This user is a Student.</p>';
        }
*/

        return $this->content;
    }

} // END class
