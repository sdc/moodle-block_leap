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

class block_leapgradetracking extends block_base {
    
    public function init() {
        $this->title = get_string( 'pluginname', 'block_leapgradetracking' );
    }

    // More than one of these blocks per course will cause problems.
    public function instance_allow_multiple() {
        return false;
    }

    // Global config.
    function has_config() {
        return true;
    }

    public function get_content() {
        global $CFG, $COURSE, $DB;

        if ($this->content !== null) {
          return $this->content;
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

        $this->content->text   = '<p style="text-align:center;"><img src="'.$CFG->wwwroot.'/blocks/leapgradetracking/pix/logo.png"></p>';

        // Apparently the best way forward is to roll our own capabilities. Maybe one day.
        //if( has_capability( 'block/leapgradetracking:editconfig', $coursecontext ) ) {
        if( has_capability( 'moodle/site:config', $coursecontext ) ) {
            $this->content->text .= '<p>This user is a Site Admin.</p>';

            $this->content->text .= '<p><strong>Global Config Checks</strong></p>';

// get_config($plugin, $name);
// set_config($name, $value, $plugin);

            // TODO: pre-save processing to check for 'http(s)://' and potentially remove '/'?
            // TODO: quick check to make sure the URL, if exists, is real and pingable?
            $leap_url = get_config( 'block_leapgradetracking', 'leap_url' );
            if ( empty( $leap_url ) ) {
                // 'leap_url' config field empty.
                $this->content->text .= '<p><em>Global setting "leap_url" not set!</em> [<a href="' . $CFG->wwwroot . '/admin/settings.php?section=blocksettingleapgradetracking">settings</a>]</p>';
            } else {
                // 'leap_url' config field populated with something.
                $this->content->text .= '<p>"leap_url" set [' . $leap_url . '].</p>';
            }


            $auth_username = get_config( 'block_leapgradetracking', 'auth_username' );
            if ( empty( $auth_username ) ) {
                // 'auth_username' config field empty.
                $this->content->text .= '<p><em>Global setting "auth_username" not set!</em> [<a href="' . $CFG->wwwroot . '/admin/settings.php?section=blocksettingleapgradetracking">settings</a>]</p>';
            } else {
                // 'auth_username' config field populated with something.
                $this->content->text .= '<p>"auth_username" set [' . $auth_username . '].</p>';
                $auth_userid = $DB->get_record( 'user', array( 'username' => $auth_username ), 'id' );
                //var_dump($auth_userid->id); die();
                if ( empty( $auth_userid ) ) {
                    // 'auth_username' doesn't relate to a user in the database.
                    $this->content->text .= '<p><em>[' . $auth_username . '] not found in database!</em></p>';
                } else {
                    // 'auth_username' relates to a user in the database.
                    $this->content->text .= '<p>"' . $auth_username . '" equates to user id ' . $auth_userid->id . '.</p>';
                    $auth_token = $DB->get_record( 'external_tokens', array( 'userid' => $auth_userid->id ) );
                    if ( empty( $auth_token ) ) {
                        // No external token found in the database for that user.
                        $this->content->text .= '<p><em>No external token found in database for userid ' . $auth_userid->id . '!</em></p>';
                    } else {
                        // External token found in the database for that user.
                        $this->content->text .= '<p>External token [' . $auth_token->token . '] found in database for userid ' . $auth_userid->id . '!</p>';
                        // There are some checks surrounding external keys - is it worth abiding by them?
                        //
                        // mdl_external_tokens.externalserviceid == external_services.id WHERE component = 'local_leapwebservices'
                        //    AND external_services.enabled = 1
                        //
                        // * IP restriction - no, as it's a plugin on the same server, not an external system.
                        // * valid until - probably... If the key's set to expire, we shouldn't use it after it has.
                        if ( $auth_token->validuntil <= time() ) {
                            $this->content->text .= '<p><em>Token has expired!</em></p>';
                        } else {
                            $this->content->text .= '<p>Token is in date.</p>';
                        }
                    }
                }
            }

        } else if( has_capability( 'moodle/course:update', $coursecontext ) ) {
            $this->content->text .= '<p>This user is a Teacher.</p>';

        } else {
            $this->content->text .= '<p>This user is a Student.</p>';
        }

        return $this->content;
      }

} // END class
