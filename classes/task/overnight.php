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
 * Leap overnight script class.
 *
 * @package    block_leap
 * @copyright  2014, 2015 Paul Vaughan {@link http://commoodle.southdevon.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_leap\task;

defined('MOODLE_INTERNAL') || die();

include_once( $CFG->dirroot . '/blocks/leap/locallib.php' );
//include_once( $CFG->dirroot . '/lib/grade/grade_category.php' );
require_once( $CFG->libdir . '/gradelib.php' );


class overnight extends \core\task\scheduled_task {

    public function get_name() {
        // Shown in admin screens.
        return get_string( 'pluginname', 'block_leap' );
    }

    // A little function to make the db log look nice.
    // TODO: We now have the opportunity to record more detail (e.g. course and user seperate), but look into this later as it's not critical.
    private function tlog( $msg, $type = 'ok' ) {
        global $DB;
        if (!$msg || empty( $msg ) ) {
            $msg = '----';
        }
        $DB->insert_record( 'block_leap_log', array( 'type' => $type, 'content' => $msg, 'timelogged' => time() ) );
        //echo $type . ' ' . $msg;
    }

    /* Might have to can the whole following procedure and any calls to it. */
    /**
     * Process the L3VA score into a MAG.
     *
     * @param in        L3VA score (float)
     * @param course    Tagged course type
     * @param scale     Scale to use for this course
     * @param tag       If true, make the TAG instead of MAG
     */
    private function make_mag( $in, $course = 'default', $scale = 'BTEC', $tag = false ) {

        //if ( $in == '' || !is_numeric($in) || $in <= 0 || !$in ) {
        if ( $in == '' || !$in ) {
            return false;
        }
        if ( $course == '' ) {
            return false;
        }

        $course = strtolower( $course );

        global $l3va_data;

        // Make the score acording to formulas if the scales are usable.
        if ( $scale == 'BTEC' || $scale == 'A Level' ) {
            $adj_l3va = ( $l3va_data[$course]['m'] * $in ) - $l3va_data[$course]['c'];
        } else {
            $adj_l3va = $in;
        }

        // Return a grade based on whatever scale we're using.
        if ( $scale == 'BTEC' && !$tag ) {
            // Using BTEC scale.

            $score = 1; // Default grade of 'Refer'.
            if ( $adj_l3va >= 30 ) {
                $score = 2; // Pass
            }
            if ( $adj_l3va >= 60 ) {
                $score = 3; // Merit
            }
            if ( $adj_l3va >= 90 ) {
                $score = 4; // Distinction
            }

        } else if ( $scale == 'BTEC' && $tag ) {
            // We don't want to add in TAGs for BTEC, so return null.
            $score = null;

        } else if ( $scale == 'A Level' ) {
            // We're using an A Level scale.
            // AS Levels are exactly half of A (A2) Levels, if we need to know them in the future.

            // As A Level grades are precisely 30 apart, to get a TAG one grade up we just add 30 to the score.
            if ( $tag ) {
                $adj_l3va += 30;
            }

            $score = 1; // U
            if ( $adj_l3va >= 30 ) {
                $score = 2; // E
            }
            if ( $adj_l3va >= 60 ) {
                $score = 3; // D
            }
            if ( $adj_l3va >= 90 ) {
                $score = 4; // C
            }
            if ( $adj_l3va >= 120 ) {
                $score = 5; // B
            }
            if ( $adj_l3va >= 150 ) {
                $score = 6; // A
            }
            // If we ever use A*.
            //if ( $adj_l3va >= 180 ) {
            //    $score = 7; // A*
            //}

        } else if ( $scale == 'GCSE' ) {

            $score = 1; // U
            if ( $adj_l3va == 'U' ) {
                $score = 1;
            }
            if ( $adj_l3va == 'F' ) {
                $score = 2;
            }
            if ( $adj_l3va == 'E' ) {
                $score = 3;
            }
            if ( $adj_l3va == 'D' ) {
                $score = 4;
            }
            if ( $adj_l3va == 'C' ) {
                $score = 5;
            }
            if ( $adj_l3va == 'B' ) {
                $score = 6;
            }
            if ( $adj_l3va == 'A' ) {
                $score = 7;
            }

        } else if ( $scale == 'Refer and Pass' ) {

            // Always show a pass. Always.
            $score = 2; // Pass

        } else if ( $scale == 'noscale' ) {
            // Using no scale, simply return null.
            $score = null;

        } else {
            // Set a default score if none of the above criteria are met.
            $score = null;
        }

        return array( $score, $adj_l3va );

    }


    /**
     * This is the function which runs when cron calls.
     */
    public function execute() {
        global $CFG, $DB;

        echo "BEGIN >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n\n";

        /**
         * A script, to be run overnight, to pull L3VA scores from Leap and generate
         * the MAG, for each student on specifically-tagged courses, and add it into
         * our live Moodle.
         *
         * @copyright 2014-2015 Paul Vaughan
         * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
         */

        // Script start time.
        $time_start = microtime(true);

        // Null or an int (course's id): run the script only for this course. For testing or one-offs.
        // TODO: Consider changing $thiscourse as an array, not an integer.
        $thiscourse = null; // null or e.g. 1234

        // TODO: can we use *all* the details in version.php? It would make a lot more sense.
        $version    = '1.0.20';
        //$build      = '20150128';
        $build      = get_config( 'block_leap', 'version' );

        // Debugging.
        define( 'DEBUG', true );

        // Debugging.
        define( 'TRUNCATE_LOG', true );

        // Truncate the log table.
        if ( TRUNCATE_LOG ) {
            echo 'Truncating block_leap_log...';
            $DB->delete_records( 'block_leap_log', null );
            echo " done.\n";
        }

        overnight::tlog( 'GradeTracker script, v' . $version . ', ' . $build . '.', 'hiya' );
        overnight::tlog( 'Started at ' . date( 'c', $time_start ) . '.', ' go ' );
        if ( $thiscourse ) {
            overnight::tlog( 'IMPORTANT! Processing only course \'' . $thiscourse . '\'.', 'warn' );
        }
        overnight::tlog( '', '----' );

        // Before almost anything has the chance to fail, reset the fail delay setting back to 0.
        if ( DEBUG ) {
            if ( !$reset = $DB->set_field( 'task_scheduled', 'faildelay', 0, array( 'component' => 'block_leap', 'classname' => '\block_leap\task\overnight' ) ) ) {
                overnight::tlog( 'Scheduled task "fail delay" could not be reset.', 'warn' );
            } else {
                overnight::tlog( 'Scheduled task "fail delay" reset to 0.', 'dbug' );
            }
        }

        // Check for required config settings and fail gracefully if they're not available.
        if ( !$auth_token = get_auth_token() ) {
            overnight::tlog( 'Could not find a valid auth token.', 'EROR' );
            return false;
        }
        define( 'AUTH_TOKEN', $auth_token->token );
        overnight::tlog( 'Leap webservice auth hash: ' . AUTH_TOKEN, 'dbug' );

        // TODO: quick check to make sure the URL is real and pingable?
        if ( !$leap_url = get_config( 'block_leap', 'leap_url' ) ) {
            overnight::tlog( 'Setting "leap_url" not set.', 'EROR' );
            return false;
        }
        define( 'LEAP_API_URL', $leap_url . '/people/%s.json?token=%s' );
        overnight::tlog( 'Leap API URL: ' . LEAP_API_URL, 'dbug' );

        // Number of decimal places in the processed targets (and elsewhere).
        define( 'DECIMALS', 3 );

        // Search term to use when searching for courses to process.
        define( 'IDNUMBERLIKE', 'leapcore_%' );
        //define( 'IDNUMBERLIKE', 'leapcore_test' );

        // Category details for the above columns to go into.
        define( 'CATNAME', get_string( 'gradebook:category_title', 'block_leap' ) );

        // Include some details.
        require( dirname(__FILE__) . '/../../details.php' );

        // Logging array for the end-of-script summary.
        $logging = array(
            'courses'               => array(),     // For each course which has been processed (key is id).
            'students_processed'    => array(),     // For each student who has been processed.
            'students_unique'       => array(),     // For each unique student who has been processed.
            'no_l3va'               => array(),     // Students with no L3VA.
            'not_updated'           => array(),     // An entry for each student where the grade was purposefully not updated (e.g. 0 or null score).
            'grade_types'           => array(       // Can set these, but they'll get created automatically if they don't exist.
                'btec'                  => 0,       // +1 for each BTEC course.
                'a level'               => 0,       // +1 for each A Level course.
                'gcse'                  => 0,       // +1 for each GCSE course.
                // For the sake of not causing PHP Notices, added the following:
                'refer and pass'        => 0,
                'noscale'               => 0,
                'develop, pass'         => 0,
            ),
            'poor_grades'           => array(),     // An entry for each student with a E, F, U, Refer, etc.

            'num'                   => array(
                'courses'               => 0,       // Integer number of courses processed.
                'students_processed'    => 0,       // Integer number of students processed.
                'students_unique'       => 0,       // Integer number of unique students processed.
                'no_l3va'               => 0,       // Integer number of unique students with no L3VA score.
                'not_updated'           => 0,       // Integer number of purposefully not updated students.
                'grade_types'           => 0,       // Integer number of grades used.
                'grade_types_in_use'    => 0,       // Integer number of grade types.
                'poor_grades'           => 0,       // Integer number of poorly-graded students processed.
            ),
        );

        // Small array to store the GCSE English and maths grades from the JSON.
        $gcse = array(
            'english'   => null,
            'maths'     => null,
        );

        // Just for internal use, defines the grade type (int) and what it is (string).
        $gradetypes = array (
            0 => 'None',    // Scale ID: null
            1 => 'Value',   // Scale ID: null. Uses grademax and grademin instead.
            2 => 'Scale',   // Scale ID depends on whatever's available: IDs relate to mdl_scale.id.
            3 => 'Text',    // ...
        );

        // Define the wanted column names (will appear in this order in the Gradebook, initially).
        // These column names are an integral part of this plugin and should not be changed.
        $column_names = array(
            get_string( 'gradebook:tag', 'block_leap' )    => get_string( 'gradebook:tag_desc', 'block_leap' ),
            //get_string( 'gradebook:l3va', 'block_leap' )   => get_string( 'gradebook:l3va_desc', 'block_leap' ),
            //get_string( 'gradebook:mag', 'block_leap' )    => get_string( 'gradebook:mag_desc', 'block_leap' ),
        );

        // Make an array keyed to the column names to store the grades in.
        $targets = array();
        foreach ( $column_names as $name => $desc ) {
            $targets[strtolower($name)] = '';
        }


        /**
         * The next section looks through all courses for those with a properly configured Leap block
         * and adds it (and the tracking configuration) to the $courses array.
         */

        overnight::tlog( '', '----' );

        // An empty array to put suitable course objects into.
        $courses = array();

        // Get the courses we're going to process.
        if ( $thiscourse ) {
            $allcourses = $DB->get_records( 'course', array( 'id' => $thiscourse ), 'id ASC', 'id, shortname, fullname' );
        } else {
            $allcourses = $DB->get_records( 'course', null, 'id ASC', 'id, shortname, fullname' );
        }

        foreach ( $allcourses as $course ) {

            // Ignore the course with id = 1, as it's the front page.
            if ( $course->id == 1 ) {
                continue;
            } else {

                // First get course context.
                $coursecontext  = \context_course::instance( $course->id );
                $blockrecord    = $DB->get_record( 'block_instances', array( 'blockname' => 'leap', 'parentcontextid' => $coursecontext->id ) );
                $blockinstance  = block_instance( 'leap', $blockrecord );

                // Check and add trackertype and coursetype to the $course object.
                if (
                    isset( $blockinstance->config->trackertype ) &&
                    !empty( $blockinstance->config->trackertype ) )
                    //!empty( $blockinstance->config->trackertype ) &&
                    //isset( $blockinstance->config->coursetype ) &&
                    //!empty( $blockinstance->config->coursetype ) )
                {
                    $course->trackertype    = $blockinstance->config->trackertype;
                    //$course->coursetype     = $blockinstance->config->coursetype;

                    // Setting some more variables we'll need in due course.
                    $course->scalename      = null;
                    $course->scaleid        = null;
                    $course->gradeid        = null;

                    // All good, so...
                    $courses[] = $course;

                    overnight::tlog( 'Course \'' . $course->fullname . '\' (' . $course->shortname . ') [' . $course->id . '] added to process list.', 'info');
                    if ( DEBUG ) {
                        overnight::tlog( json_encode( $course ), 'dbug');
                    }

                }
            }
        }

//var_dump($courses); exit(0);

/* Example $courses array.
array(2) {
  [0]=>
  object(stdClass)#91 (7) {
    ["id"]=>
    string(1) "2"
    ["shortname"]=>
    string(5) "TC101"
    ["fullname"]=>
    string(15) "Test Course 101"
    ["trackertype"]=>
    string(7) "english"
    ["coursetype"]=>
    string(14) "a2_englishlang"
    ["scalename"]=>
    string(0) ""
    ["scaleid"]=>
    NULL
  }
  [1]=>
  object(stdClass)#92 (7) {
    ["id"]=>
    string(1) "3"
    ["shortname"]=>
    string(5) "TC201"
    ["fullname"]=>
    string(15) "Test course 201"
    ["trackertype"]=>
    string(7) "english"
    ["coursetype"]=>
    string(6) "a2_law"
    ["scalename"]=>
    string(0) ""
    ["scaleid"]=>
    NULL
  }
}

*/

        $num_courses = count( $courses );
        $cur_courses = 0;
        if ( $num_courses == 0 ) {
            overnight::tlog( 'No courses found to process, so halting.', 'EROR');
            // Returning false indicates failure. We didn't fail, just found no courses to process.
            return true;
        }

        overnight::tlog( '', '----' );


        /**
         * Sets up each configured course with a category and columns within it.
         */

        foreach ( $courses as $course ) {

            $cur_courses++;

            overnight::tlog('Processing course (' . $cur_courses . '/' . $num_courses . ') ' . $course->fullname . ' (' . $course->shortname . ') [' . $course->id . '] at ' . date( 'c', time() ) . '.', 'info');
            $logging['courses'][] = $course->fullname . ' (' . $course->shortname . ') [' . $course->id . '].';

//overnight::tlog( $course->coursetype, 'PVDB' );


/*
We need to give serious thought to NOT doing this, as it's basically impossible to set the correct scale at this point.
Grades and that:

Develop / Pass - would be used for all pass only type qualifications but develop will be used instead of refer, retake, fail etc.
U, G, F, E, D, C, B, A, A*  - to be used for GCSE / A-level plus any others that have letter grades.
Refer, Pass, Merit, Distinction.
Refer, PP, PM, MM, MD, DD
Refer, PPP, PPM, PMM, MMM, MMD, MDD, DDD
Numbers from 0 - 100 - in traffic light systems the numbers will be compared and anything lower than the target will be red, higher than target will be green.
*/

/*
            // Work out the scale from the course type.
            if ( stristr( $course->coursetype, 'as_' ) || stristr( $course->coursetype, 'a2_' ) ) {
                $course->scalename  = 'A Level';
            } else if ( stristr( $course->coursetype, 'gcse_' ) ) {
                $course->scalename  = 'GCSE';
            } else if ( stristr( $course->coursetype, 'btec_' ) ) {
                $course->scalename  = 'BTEC';
            }
            overnight::tlog( 'Course ' . $course->id . ' appears to be a ' . $course->scalename . ' course.', 'info' );

            // Get the scale ID.
            if ( !$moodlescale = $DB->get_record( 'scale', array( 'name' => $course->scalename ), 'id' ) ) {
                overnight::tlog( '- Could not find a scale called \'' . $course->scalename . '\' for course ' . $course->id . '.', 'warn' );

            } else {
                // Scale located.
                $course->scaleid = $moodlescale->id;
                overnight::tlog( '- Scale called \'' . $course->scalename . '\' found with ID ' . $moodlescale->id . '.', 'info' );
            }

            overnight::tlog( json_encode( $course ), '>dbg');
            //var_dump($courses); exit(0);
*/


            overnight::tlog( json_encode( $course ), '>dbg');



            // Figure out the grade type and scale here, pulled directly from the course's gradebook's course itemtype.
            $coursegradescale   = $DB->get_record( 'grade_items', array( 'courseid' => $course->id, 'itemtype' => 'course' ), 'gradetype, scaleid' );
            $course->gradeid    = $coursegradescale->gradetype;
            $course->scaleid    = $coursegradescale->scaleid;
            overnight::tlog( json_encode( $course ), 'PVDB' );

            if ( $course->gradeid == 2 ) {
                if ( $coursescale = $DB->get_record( 'scale', array( 'id' => $course->scaleid ) ) ) {
                    $course->scalename  = $coursescale->name;

                    $tolog = '- Scale \'' . $course->scaleid . '\' (' . $coursescale->name . ') found [' . $coursescale->scale . ']';
                    $tolog .= ( $coursescale->courseid ) ? ' (which is specific to course ' . $coursescale->courseid . ').' : ' (which is global).';
                    overnight::tlog( $tolog, 'info' );

                } else {
                    // If the scale doesn't exist that the course is using, this is a problem.
                    overnight::tlog( '- Gradetype \'2\' set, but no matching scale found.', 'warn' );
                }
            }

            overnight::tlog( json_encode( $course ), 'PVDB' );

/*
            if ( $coursegradescale = $DB->get_record( 'grade_items', array( 'courseid' => $course->id, 'itemtype' => 'course' ), 'gradetype, scaleid' ) ) {

                $course->gradeid = $coursegradescale->gradetype;
                $course->scaleid = $coursegradescale->scaleid;

                overnight::tlog( 'Gradetype \'' . $course->gradeid . '\' (' . $gradetypes[$course->gradeid] . ') found.', 'info' );

                // If the grade type is 2 / scale.
                if ( $course->gradeid == 2 ) {
                    if ( $coursescale = $DB->get_record( 'scale', array( 'id' => $course->scaleid ) ) ) {

                        $course->scalename  = $coursescale->name;
                        //$course->scaleid    = $scaleid;

                        //$course->coursetype = $coursescale->name;

                        $tolog = '- Scale \'' . $course->scaleid . '\' (' . $course->scalename . ') found';
                        $tolog .= ( $coursescale->courseid ) ? ' (which is specific to course ' . $coursescale->courseid . ').' : ' (which is global).';
                        overnight::tlog( $tolog, 'info' );

                    } else {

                        // If the scale doesn't exist that the course is using, this is a problem.
                        overnight::tlog( '- Gradetype \'2\' set, but no matching scale found.', 'warn' );

                    }

                } else if ( $course->gradeid == 1 ) {
                    // If the grade type is 1 / value.
                    $course->scalename  = 'noscale';
                    $course->scaleid    = 1;
                    // Already set, above.
                    //$course->coursetype = 'Value';

                    $tolog = ' Using \'' . $gradetypes[$gradeid] . '\' gradetype.';

                }


            } else {
                // Set it to default if no good scale could be found/used.
                $gradeid = 0;
                $scaleid = 0;
                overnight::tlog('No \'gradetype\' found, so using defaults instead.', 'info');
            }


            // You may get errors here (unknown index IIRC) if no scalename is generated because a scale (or anything) hasn't been set
            // for that course (e.g. 'cos it's a new course). Catch this earlier!
            $logging['grade_types'][strtolower($course->scalename)]++;

            /**
             * Category checking: create or skip.
             */
            if ( $DB->get_record( 'grade_categories', array( 'courseid' => $course->id, 'fullname' => CATNAME ) ) ) {
                // Category exists, so skip creation.
                overnight::tlog( 'Category \'' . CATNAME . '\' already exists for course ' . $course->id . '.', 'skip' );

            } else {
                $grade_category = new \grade_category();    // Create a category for this course.
                $grade_category->courseid = $course->id;    // Course id.
                $grade_category->fullname = CATNAME;        // Set the category name (no description).
                $grade_category->sortorder = 1;             // Need a better way of changing column order.
                $grade_category->hidden = 1;                // Attempting to hide the totals.

                // Save all that...
                if ( !$gc = $grade_category->insert() ) {
                    overnight::tlog( 'Category \'' . CATNAME . '\' could not be inserted for course '.$course->id.'.', 'EROR' );
                    return false;
                } else {
                    overnight::tlog( 'Category \'' . CATNAME . '\' (' . $gc . ') created for course '.$course->id.'.' );
                }
            }

            // We've either checked a category exists or created one, so this *should* always work.
            $cat_id = $DB->get_record( 'grade_categories', array(
                'courseid' => $course->id,
                'fullname' => CATNAME,
            ) );
            $cat_id = $cat_id->id;

            // One thing we need to do is set 'gradetype' to 0 on that newly created category, which prevents a category total showing
            // and the grades counting towards the total course grade.
            $DB->set_field_select('grade_items', 'gradetype', 0, "courseid = " . $course->id . " AND itemtype = 'category' AND iteminstance = " . $cat_id);


            /**
             * Column checking: create or update.
             */

            // Step through each column name.
            foreach ( $column_names as $col_name => $col_desc ) {

                // Need to check for previously-created columns and force an update if they already exist.
                //if ( $DB->get_record('grade_items', array( 'courseid' => $course->id, 'itemname' => $col_name, 'itemtype' => 'manual' ) ) ) {
                //    // Column exists, so update instead.
                //    overnight::tlog('- Column \'' . $col_name . '\' already exists for course ' . $course->id . '.', 'skip');






                //} else {
                    $grade_item = new \grade_item();        // Create a new item object.
                    $grade_item->courseid = $course->id;    // Course id.
                    $grade_item->itemtype = 'manual';       // Set the category name (no description).
                    $grade_item->itemname = $col_name;      // The item's name.
                    $grade_item->iteminfo = $col_desc;      // Description of the item.
                    $grade_item->categoryid = $cat_id;      // Set the immediate parent category.
                    $grade_item->hidden = 0;                // Don't want it hidden (by default).
                    $grade_item->locked = 1;                // Lock it (by default).

                    // Per-column specifics.
                    if ( $col_name == 'TAG' ) {
                        $grade_item->sortorder  = 1;        // In-category sort order.
                        //$grade_item->gradetype  = $course->gradeid;
                        $grade_item->gradetype  = 3;        // Text field.
                        //$grade_item->scaleid    = $course->scaleid;
                        //$grade_item->display    = 1;        // 'Real'. MIGHT need to seperate out options for BTEC and A Level.
                        $grade_item->display    = 0;        // No frills.
                    }
                    //if ( $col_name == 'L3VA' ) {
                    //    // Lock the L3VA col as it's calculated elsewhere.
                    //    $grade_item->sortorder  = 2;
                    //    $grade_item->locked     = 1;
                    //    $grade_item->decimals   = 0;
                    //    $grade_item->display    = 1; // 'Real'.
                    //}
                    //if ( $col_name == 'MAG' ) {
                    //    $grade_item->sortorder  = 3;
                    //    //$grade_item->locked     = 1;
                    //    $grade_item->gradetype  = $gradeid;
                    //    $grade_item->scaleid    = $scaleid;
                    //    $grade_item->display    = 1; // 'Real'.
                    //}

                    // Scale ID, generated earlier. An int, 0 or greater.
                    // TODO: Check if we need this any more!!
                    //$grade_item->scale = $course->scaleid;

                    // Check to see if this record already exists, determining if we insert or update.
                    if ( !$grade_items_exists = $DB->get_record( 'grade_items', array( 'courseid' => $course->id, 'itemname' => $col_name, 'itemtype' => 'manual' ) ) ) {

                        // INSERT a new record.
                        if ( !$gi = $grade_item->insert() ) {
                            overnight::tlog('- Column \'' . $col_name . '\' could not be inserted for course ' . $course->id . '.', 'EROR');
                            return false;
                        } else {
                            overnight::tlog('- Column \'' . $col_name . '\' created for course ' . $course->id . '.');
                        }

                    } else {
                        // UPDATE the existing record.
                        $grade_item->id = $grade_items_exists->id;
                        if ( !$gi = $grade_item->update() ) {
                            overnight::tlog('- Column \'' . $col_name . '\' could not be updated for course ' . $course->id . '.', 'EROR');
                            return false;
                        } else {
                            overnight::tlog('- Column \'' . $col_name . '\' updated for course ' . $course->id . '.');
                        }
                    }


                //} // END skip processing if manual column(s) already found in course.

            } // END while working through each rquired column.



// Good to here.



            /**
             * Move the category to the first location in the gradebook if it isn't already.
             */
            //$gtree = new grade_tree($course->id, false, false);
            //$temp = grade_edit_tree::move_elements(1, '')


            /**
             * Collect enrolments based on each of those courses
             */

            // EPIC 'get enrolled students' query from Stack Overflow:
            // http://stackoverflow.com/questions/22161606/sql-query-for-courses-enrolment-on-moodle
            // Only selects manually enrolled, not self-enrolled student roles (redacted!).
            $sql = "SELECT DISTINCT u.id AS userid, firstname, lastname, username
                FROM mdl_user u
                    JOIN mdl_user_enrolments ue ON ue.userid = u.id
                    JOIN mdl_enrol e ON e.id = ue.enrolid
                        -- AND e.enrol = 'manual'
                    JOIN mdl_role_assignments ra ON ra.userid = u.id
                    JOIN mdl_context ct ON ct.id = ra.contextid
                        AND ct.contextlevel = 50
                    JOIN mdl_course c ON c.id = ct.instanceid
                        AND e.courseid = c.id
                    JOIN mdl_role r ON r.id = ra.roleid
                        AND r.shortname = 'student'
                WHERE courseid = " . $course->id . "
                    AND e.status = 0
                    AND u.suspended = 0
                    AND u.deleted = 0
                    AND (
                        ue.timeend = 0
                        OR ue.timeend > NOW()
                    )
                    AND ue.status = 0
                ORDER BY userid ASC;";

            if ( !$enrollees = $DB->get_records_sql( $sql ) ) {
                overnight::tlog('No manually enrolled students found for course ' . $course->id . '.', 'warn');

            } else {

                $num_enrollees = count($enrollees);
                overnight::tlog('Found ' . $num_enrollees . ' students manually enrolled onto course ' . $course->id . '.', 'info');

                // A variable to store which enrollee we're processing.
                $cur_enrollees = 0;
                foreach ($enrollees as $enrollee) {
                    $cur_enrollees++;

                    // Attempt to extract the student ID from the username.
                    $tmp = explode('@', $enrollee->username);
                    $enrollee->studentid = $tmp[0];

                    // A proper student, hopefully.
                    overnight::tlog('- Processing user (' . $cur_enrollees . '/' . $num_enrollees . ') ' . $enrollee->firstname . ' ' . $enrollee->lastname . ' (' . $enrollee->userid . ') [' . $enrollee->studentid . '] on course ' . $course->id . '.', 'info');
                    $logging['students_processed'][] = $enrollee->firstname . ' ' . $enrollee->lastname . ' (' . $enrollee->studentid . ') [' . $enrollee->userid . '] on course ' . $course->id . '.';
                    $logging['students_unique'][$enrollee->userid] = $enrollee->firstname . ' ' . $enrollee->lastname . ' (' . $enrollee->studentid . ') [' . $enrollee->userid . '].';

                    // Assemble the URL with the correct data.
                    $leapdataurl = sprintf( LEAP_API_URL, $enrollee->studentid, AUTH_TOKEN );
                    //if ( DEBUG ) {
                        overnight::tlog('-- Leap URL: ' . $leapdataurl, 'dbug');
                    //}

                    // Use fopen to read from the API.
                    if ( !$handle = fopen($leapdataurl, 'r') ) {
                        // If the API can't be reached for some reason.
                        overnight::tlog('- Cannot open ' . $leapdataurl . '.', 'EROR');

                    } else {
                        // API reachable, get the data.
                        $leapdata = fgets($handle);
                        fclose($handle);

                        if ( DEBUG ) {
                            overnight::tlog('-- Returned JSON: ' . $leapdata, 'dbug');
                        }

                        // Handle an empty result from the API.
                        if ( strlen($leapdata) == 0 ) {
                            overnight::tlog('-- API returned 0 bytes.', 'EROR');

                        } else {
                            // Decode the JSON into an object.
                            $leapdata = json_decode($leapdata);

                            // Checking for JSON decoding errors, seems only right.
                            if ( json_last_error() ) {
                                overnight::tlog('-- JSON decoding returned error code ' . json_last_error() . ' for user ' . $enrollee->studentid . '.', 'EROR');
                            } else {

                                // We have a L3VA score! And possibly GCSE English and maths grades too.
                                //$targets['l3va']    = number_format( $leapdata->person->l3va, DECIMALS );
                                //$gcse['english']    = $leapdata->person->gcse_english;
                                //$gcse['maths']      = $leapdata->person->gcse_maths;

                                //if ( $targets['l3va'] == '' || !is_numeric( $targets['l3va'] ) || $targets['l3va'] <= 0 ) {
                                //    // If the L3VA isn't good.
                                //    overnight::tlog('-- L3VA is not good: \'' . $targets['l3va'] . '\'.', 'warn');
                                //    $logging['no_l3va'][$enrollee->userid] = $enrollee->firstname . ' ' . $enrollee->lastname . ' (' . $enrollee->studentid . ') [' . $enrollee->userid . '].';
                                //
                                //} else {

                                    overnight::tlog('-- ' . $enrollee->firstname . ' ' . $enrollee->lastname . ' (' . $enrollee->userid . ') [' . $enrollee->studentid . '] L3VA score: ' . $targets['l3va'] . '.', 'info');

                                    // If this course is tagged as a GCSE English or maths course, use the grades supplied in the JSON.
/*
                                    if ( $course->coursetype == 'leapcore_gcse_english' ) {
                                        $magtemp        = overnight::make_mag( $gcse['english'], $course->coursetype, $course->scalename );
                                        $tagtemp        = array( null, null );

                                    } else if ( $course->coursetype == 'leapcore_gcse_maths' ) {
                                        $magtemp        = overnight::make_mag( $gcse['maths'], $course->coursetype, $course->scalename );
                                        $tagtemp        = array( null, null );

                                    } else {
                                        // Make the MAG from the L3VA.
                                        $magtemp        = overnight::make_mag( $targets['l3va'], $course->coursetype, $course->scalename );
                                        // Make the TAG in the same way, setting 'true' at the end for the next grade up.
                                        $tagtemp        = overnight::make_mag( $targets['l3va'], $course->coursetype, $course->scalename, true );

                                    }
                                    $targets['mag'] = $magtemp[0];
                                    $targets['tag'] = $tagtemp[0];
*/

/*
                                    if ( $course->coursetype == 'leapcore_gcse_english' || $course->coursetype == 'leapcore_gcse_maths' ) {
                                        overnight::tlog('--- GCSEs passed through from Leap JSON: MAG: \'' . $targets['mag'] . '\' ['. $magtemp[1] .']. TAG: \'' . $targets['tag'] . '\' ['. $tagtemp[1] .'].', 'info');
                                    } else {
                                        overnight::tlog('--- Generated data: MAG: \'' . $targets['mag'] . '\' ['. $magtemp[1] .']. TAG: \'' . $targets['tag'] . '\' ['. $tagtemp[1] .'].', 'info');
                                    }

                                    if ( $targets['mag'] == '0' || $targets['mag'] == '1' ) {
                                        $logging['poor_grades'][] = 'MAG ' . $targets['mag'] . ' assigned to ' . $enrollee->firstname . ' ' . $enrollee->lastname . ' (' . $enrollee->studentid . ') [' . $enrollee->userid . '] on course ' . $course->id . '.';
                                    }
                                    if ( $targets['tag'] == '0' || $targets['tag'] == '1' ) {
                                        $logging['poor_grades'][] = 'TAG ' . $targets['tag'] . ' assigned to ' . $enrollee->firstname . ' ' . $enrollee->lastname . ' (' . $enrollee->studentid . ') [' . $enrollee->userid . '] on course ' . $course->id . '.';
                                    }
*/
                                    // Loop through all settable, updateable grades.
                                    foreach ( $targets as $target => $score ) {

                                        // Need the grade_items.id for grade_grades.itemid.
                                        $gradeitem = $DB->get_record('grade_items', array(
                                            'courseid' => $course->id,
                                            'itemname' => strtoupper( $target ),
                                        ), 'id, categoryid');

                                        // Check to see if this data already exists in the database, so we can insert or update.
                                        $gradegrade = $DB->get_record('grade_grades', array(
                                            'itemid' => $gradeitem->id,
                                            'userid' => $enrollee->userid,
                                        ), 'id');


                                        // New grade_grade object.
                                        $grade = new grade_grade();
                                        $grade->userid          = $enrollee->userid;
                                        $grade->itemid          = $gradeitem->id;
                                        $grade->categoryid      = $gradeitem->categoryid;
                                        $grade->rawgrade        = $score; // Will stay as set.
                                        $grade->finalgrade      = $score; // Will change with the grade, e.g. 3.
                                        $grade->timecreated     = time();
                                        $grade->timemodified    = $grade->timecreated;

                                        // TODO: "excluded" is a thing and prevents a grade being aggregated.
                                        $grade->excluded    = true;

                                        // If no id exists, INSERT.
                                        if ( !$gradegrade ) {

                                            if ( !$gl = $grade->insert() ) {
                                                overnight::tlog('--- ' . strtoupper( $target ) . ' insert failed for user ' . $enrollee->userid . ' on course ' . $course->id . '.', 'EROR' );
                                            } else {
                                                overnight::tlog('--- ' . strtoupper( $target ) . ' (' . $score . ') inserted for user ' . $enrollee->userid . ' on course ' . $course->id . '.' );
                                            }

                                        } else {
                                            // If the row already exists, UPDATE, but don't ever *update* the TAG.

                                            //if ( $target == 'mag' && !$score ) {
                                            //    // For MAGs, we don't want to update to a zero or null score as that may overwrite a manually-entered MAG.
                                            //    overnight::tlog('--- ' . strtoupper( $target ) . ' of 0 or null (' . $score . ') purposefully not updated for user ' . $enrollee->userid . ' on course ' . $course->id . '.' );
                                            //    $logging['not_updated'][] = $enrollee->firstname . ' ' . $enrollee->lastname . ' (' . $enrollee->studentid . ') [' . $enrollee->userid . '] on course ' . $course->id . ': ' . strtoupper( $target ) . ' of \'' . $score . '\'.';

                                            //} else if ( $target != 'tag' ) {
                                                $grade->id = $gradegrade->id;

                                                // We don't want to set this again, but we do want the modified time set.
                                                unset( $grade->timecreated );
                                                $grade->timemodified = time();

                                                if ( !$gl = $grade->update() ) {
                                                    overnight::tlog('--- ' . strtoupper( $target ) . ' update failed for user ' . $enrollee->userid . ' on course ' . $course->id . '.', 'EROR' );
                                                } else {
                                                    overnight::tlog('--- ' . strtoupper( $target ) . ' (' . $score . ') update for user ' . $enrollee->userid . ' on course ' . $course->id . '.' );
                                                }

                                            //} else {
                                            //    overnight::tlog('--- ' . strtoupper( $target ) . ' purposefully not updated for user ' . $enrollee->userid . ' on course ' . $course->id . '.', 'skip' );
                                            //    $logging['not_updated'][] = $enrollee->firstname . ' ' . $enrollee->lastname . ' (' . $enrollee->studentid . ') [' . $enrollee->userid . '] on course ' . $course->id . ': ' . strtoupper( $target ) . ' of \'' . $score . '\'.';
                                            //} // END ignore updating the TAG.

                                        } // END insert or update check.

                                    } // END foreach loop.

                                //} // END L3VA check.

                            } // END any json_decode errors.

                        } // END empty API result.

                    } // END open leap API for reading.

                } // END cycle through each course enrollee.

            }  // END enrollee query.

            // Final blank-ish log entry to separate out one course from another.
            overnight::tlog('', '----');

        } // END foreach course tagged 'leapcore_*'.

        // Sort and dump the summary log.
        overnight::tlog('Summary of all performed operations.', 'smry');
        asort($logging['courses']);
        asort($logging['students_processed']);
        asort($logging['students_unique']);
        asort($logging['no_l3va']);
        asort($logging['not_updated']);
        arsort($logging['grade_types']);
        asort($logging['poor_grades']);

        // Processing.
        $logging['num']['courses']  = count($logging['courses']);
        $logging['num']['students_processed'] = count($logging['students_processed']);
        $logging['num']['students_unique'] = count($logging['students_unique']);
        $logging['num']['no_l3va'] = count($logging['no_l3va']);
        $logging['num']['not_updated'] = count($logging['not_updated']);
        $logging['num']['grade_types'] = count($logging['grade_types']);
        foreach ( $logging['grade_types'] as $value ) {
            $logging['num']['grade_types_in_use'] += $value;
        }
        $logging['num']['poor_grades'] = count($logging['poor_grades']);

        if ( $logging['num']['courses'] ) {
            overnight::tlog( $logging['num']['courses'] . ' courses:', 'smry' );
            $count = 0;
            foreach ( $logging['courses'] as $course ) {
                overnight::tlog( '- ' . sprintf( '%4s', ++$count ) . ': ' . $course, 'smry' );
            }
        } else {
            overnight::tlog( 'No courses processed.', 'warn' );
        }

        if ( $logging['num']['students_processed'] ) {
            overnight::tlog( $logging['num']['students_processed'] . ' student-courses processed:', 'smry' );
            $count = 0;
            foreach ( $logging['students_processed'] as $student ) {
                overnight::tlog( '- ' . sprintf( '%4s', ++$count ) . ': ' . $student, 'smry' );
            }
        } else {
            overnight::tlog( 'No student-courses processed.', 'warn' );
        }

        if ( $logging['num']['students_unique'] ) {
            overnight::tlog( $logging['num']['students_unique'] . ' unique students:', 'smry' );
            $count = 0;
            foreach ( $logging['students_unique'] as $student ) {
                echo sprintf( '%4s', ++$count ) . ': ' . $student . "\n";
                overnight::tlog( '- ' . sprintf( '%4s', ++$count ) . ': ' . $student, 'smry' );
            }
        } else {
            overnight::tlog( 'No unique students processed.', 'warn' );
        }

        if ( $logging['num']['no_l3va'] ) {
            overnight::tlog( $logging['num']['no_l3va'] . ' students with no L3VA:', 'smry' );
            $count = 0;
            foreach ( $logging['no_l3va'] as $no_l3va ) {
                echo sprintf( '%4s', ++$count ) . ': ' . $no_l3va . "\n";
                overnight::tlog( '- ' . sprintf( '%4s', ++$count ) . ': ' . $no_l3va, 'smry' );
            }
        } else {
            overnight::tlog( 'No missing L3VAs.', 'warn' );
        }

        if ( $logging['num']['not_updated'] ) {
            overnight::tlog( $logging['num']['not_updated'] . ' students purposefully not updated (0 or null grade):', 'smry' );
            $count = 0;
            foreach ( $logging['not_updated'] as $not_updated ) {
                overnight::tlog( '- ' . sprintf( '%4s', ++$count ) . ': ' . $not_updated, 'smry' );
            }
        } else {
            overnight::tlog( 'No students purposefully not updated.', 'warn' );
        }

        if ( $logging['num']['grade_types'] ) {
            overnight::tlog( $logging['num']['grade_types'] . ' grade types with ' . $logging['num']['grade_types_in_use']  . ' grades set:', 'smry' );
            $count = 0;
            foreach ( $logging['grade_types'] as $grade_type => $num_grades ) {
                overnight::tlog( '- ' . sprintf( '%4s', ++$count ) . ': ' . $grade_type . ': ' . $num_grades, 'smry' );
            }
        } else {
            overnight::tlog( 'No grade_types found.', 'warn' );
        }

        if ( $logging['num']['poor_grades'] ) {
            overnight::tlog( $logging['num']['poor_grades'] . ' poor grades:', 'smry');
            $count = 0;
            foreach ( $logging['poor_grades'] as $poorgrade ) {
                overnight::tlog( '- ' . sprintf( '%4s', ++$count ) . ': ' . $poorgrade, 'smry' );
            }
        } else {
            overnight::tlog( 'No poor grades found. Good!', 'smry' );
        }

        // Finish time.
        $time_end = microtime(true);
        $duration = $time_end - $time_start;
        $mins = ( floor( $duration / 60 ) == 0 ) ? '' : floor( $duration / 60 ) . ' minutes';
        $secs = ( ( $duration % 60 ) == 0 ) ? '' : ( $duration % 60 ) . ' seconds';
        $secs = ( $mins == '' ) ? $secs : ' ' . $secs;
        overnight::tlog('', '----');
        overnight::tlog('Finished at ' . date( 'c', $time_end ) . ', took ' . $mins . $secs . ' (' . number_format( $duration, DECIMALS ) . ' seconds).', 'byby');

        //exit(0);
        return true;

        echo "\nEND   >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n";

    } // END method execute.

}
