<?php

namespace local_leapgradetracking\task;

defined('MOODLE_INTERNAL') || die();

class overnight extends \core\task\scheduled_task {

    // Null or an int (course's id): run the script only for this course. For testing or one-offs.
    const THIS_COURSE = null; // null or e.g. 1234

    // Debugging.
    const DEBUG         = false;

    const VERSION       = '1.0.19';
    const BUILD         = '20150129';
    // Sample Leap Tracker API URL. TODO: Change this to a user-configurable setting.
    const LEAP_TRACKER_API = 'http://leap.southdevon.ac.uk/people/%s.json?token=%s';
    // Number of decimal places in the processed targets (and elsewhere).
    const DECIMALS      = 3;
    // Search term to use when searching for courses to process.
    const IDNUMBERLIKE  = 'leapcore_%';
    //const IDNUMBERLIKE = 'leapcore_test';
    // Category details for the above columns to go into.
    const CATNAME       = 'Targets';
    // If set, truncate the log table.
    const TRUNCATE_LOG  = true;


    // Logging array for the end-of-script summary.
    // TODO: can we do this better?
    private $logging = array(
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

    // Modifiers for calculating the L3VA score.
    // TODO: consider moving this to a settings page also, eventually.
    private $l3va_data = array(
        'leapcore_a2_artdes'        => array( 'm' => 4.4727, 'c' => 98.056 ),
        'leapcore_a2_artdesphoto'   => array( 'm' => 4.1855, 'c' => 79.949 ),
        'leapcore_a2_artdestext'    => array( 'm' => 3.9430, 'c' => 66.967 ),
        'leapcore_a2_biology'       => array( 'm' => 5.2471, 'c' => 166.67 ),
        'leapcore_a2_busstud'       => array( 'm' => 4.8372, 'c' => 123.41 ),
        'leapcore_a2_chemistry'     => array( 'm' => 4.5169, 'c' => 129.00 ),
        'leapcore_a2_englishlang'   => array( 'm' => 4.5773, 'c' => 112.14 ),
        'leapcore_a2_englishlit'    => array( 'm' => 5.0872, 'c' => 137.31 ),
        'leapcore_a2_envsci'        => array( 'm' => 6.1058, 'c' => 196.66 ),
        'leapcore_a2_envstud'       => array( 'm' => 6.1058, 'c' => 196.66 ),
        'leapcore_a2_filmstud'      => array( 'm' => 4.0471, 'c' => 76.470 ),
        'leapcore_a2_geography'     => array( 'm' => 5.4727, 'c' => 156.16 ),
        'leapcore_a2_govpoli'       => array( 'm' => 5.3215, 'c' => 145.38 ),
        'leapcore_a2_history'       => array( 'm' => 4.6593, 'c' => 118.98 ),
        'leapcore_a2_humanbiology'  => array( 'm' => 5.2471, 'c' => 166.67 ), // Copied from biology.
        'leapcore_a2_law'           => array( 'm' => 5.1047, 'c' => 140.69 ),
        'leapcore_a2_maths'         => array( 'm' => 4.5738, 'c' => 119.43 ),
        'leapcore_a2_mathsfurther'  => array( 'm' => 4.4709, 'c' => 106.40 ),
        'leapcore_a2_media'         => array( 'm' => 4.2884, 'c' => 90.279 ),
        'leapcore_a2_philosophy'    => array( 'm' => 4.7645, 'c' => 128.95 ),
        'leapcore_a2_physics'       => array( 'm' => 5.0965, 'c' => 159.08 ),
        'leapcore_a2_psychology'    => array( 'm' => 5.3872, 'c' => 158.71 ),
        'leapcore_a2_sociology'     => array( 'm' => 4.9645, 'c' => 122.95 ),

        'leapcore_btecex_applsci'   => array( 'm' => 10.606, 'c' => 269.15 ),

        'leapcore_default'          => array( 'm' => 4.8008, 'c' => 126.18 ),

        'btec'                      => array( 'm' => 3.9, 'c' => 90 ),

    );

    // Small array to store the GCSE English and maths grades from the JSON.
    private $gcse = array(
        'english'   => null,
        'maths'     => null,
    );

    // Just for internal use, defines the grade type (int) and what it is (string).
    private $gradetypes = array (
        0 => 'None',    // Scale ID: null
        1 => 'Value',   // Scale ID: null. Uses grademax and grademin instead.
        2 => 'Scale',   // Scale ID depends on whatever's available: IDs relate to mdl_scale.id.
        3 => 'Text',    // ...
    );

    // Define the wanted column names (will appear in this order in the Gradebook, initially).
    private $column_names = array(
        'TAG'   => 'Target Achievable Grade.',
        'L3VA'  => 'Level 3 Value Added.',
        'MAG'   => 'Indicative Minimum Achievable Grade.',
    );

    // Make an array keyed to the column names to store the grades in.
    //private $targets = array();
    //foreach ( $column_names as $name => $desc ) {
    //    $targets[strtolower($name)] = '';
    //}
    private $targets = array();
    private function make_targets() {
        foreach ( $this->column_names as $name => $desc ) {
            $this->targets[strtolower($name)] = '';
        }
    }

    public function dump($in) {
        var_dump($in);
    }

    // If $thiscourse is set, query only that course.
    private $thiscoursestring = '';
    //if ( overnight::THIS_COURSE ) {
    //    $thiscoursestring = ' AND id = ' . overnight::THIS_COURSE;
    //}

    /**
     * End of variables and constants, time for functions.
     */

    public function get_name() {
        // Shown in admin screens.
        return get_string( 'pluginname', 'local_leapgradetracking' );
    }

    // A little function to make the db log look nice.
    // TODO: We now have the opportunity to record more detail (e.g. course and user seperate), but look into this later as it's not critical.
    private function tlog( $msg, $type = 'ok' ) {
        global $DB;
        if (!$msg || empty( $msg ) ) {
            $msg = '----';
        }
        $DB->insert_record( 'leapgradetracking_log', array( 'type' => $type, 'content' => $msg, 'timelogged' => time() ) );
    }

    /**
     * Process the L3VA score into a MAG.
     *
     * @param in        L3VA score (float)
     * @param course    Tagged course type
     * @param scale     Scale to use for this course
     * @param tag       If true, make the TAG instead of MAG
     */
    private function make_mag( $in, $course = 'leapcore_default', $scale = 'BTEC', $tag = false ) {

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

        // Script start time.
        $time_start = microtime( true );

        echo "BEGIN >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n\n";

        // Call the nice functions which make some of the variable structures.
        $this->targets = overnight::make_targets();

        // Debugging.
        overnight::dump($this->targets);

        // Truncate the log table.
        if ( overnight::TRUNCATE_LOG ) {
            echo "Truncating leapgradetracking_log\n";
            $DB->delete_records( 'leapgradetracking_log', null );   
            echo "...done.\n";
        }

        overnight::tlog( 'GradeTracker script, v' . overnight::VERSION . ', ' . overnight::BUILD . '.', 'hiya' );
        overnight::tlog( 'Started at ' . date( 'c', $time_start ) . '.', ' go ' );
        if ( overnight::THIS_COURSE ) {
            overnight::tlog( 'IMPORTANT! Processing only course \'' . overnight::THIS_COURSE . '\'.', 'warn' );
        }
        //overnight::tlog( '', '----' );

        // Check for the required config setting in config.php.
        // TODO: Settings page for this settting.
        // TODO: change the name of this setting to include 'leap', e.g. leapgradetracking_hash.
        // TODO: Ensure there's details in the eventual readme for how to go about adding this in.
        if ( !isset( $CFG->trackerhash ) ) {
            overnight::tlog( '$CFG->trackerhash not set in config.php.', 'EROR' );
            echo '>>>> $CFG->trackerhash not set in config.php.'."\n";
            return false;
        }

        // All courses which are appropriately tagged.
        $courses = $DB->get_records_select(
            'course',
            "idnumber LIKE '%|" . overnight::IDNUMBERLIKE . "|%'" . $this->thiscoursestring,
            null,
            "id ASC",
            'id, shortname, fullname, idnumber'
        );
        if ( !$courses && overnight::THIS_COURSE ) {
            overnight::tlog('No courses tagged \'' . overnight::IDNUMBERLIKE . '\' with ID \'' . overnight::THIS_COURSE . '\' found, so halting.', 'EROR');
            return false;
        } else if ( !$courses ) {
            overnight::tlog('No courses tagged \'' . overnight::IDNUMBERLIKE . '\' found, so halting.', 'EROR');
            return false;
        }


































        echo "\nEND   >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n";

    } // END method execute.

}
