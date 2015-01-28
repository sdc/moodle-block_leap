<?php

namespace local_leapgradetracking\task;

defined('MOODLE_INTERNAL') || die();

class overnight extends \core\task\scheduled_task {

    // Null or an int (course's id): run the script only for this course. For testing or one-offs.
    const THIS_COURSE = null; // null or e.g. 1234

    // Debugging.
    const DEBUG         = false;

    const VERSION       = '1.0.18';
    const BUILD         = '20141217';
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


    public function get_name() {
        // Shown in admin screens.
        return get_string('pluginname', 'local_leapgradetracking');
    }

    // A little function to make the db log look nice.
    public function tlog( $msg, $type = 'ok' ) {
        global $DB;
        $tmp = $DB->insert_record( 'leapgradetracking_log', array( 'type' => $type, 'content' => $msg, 'timelogged' => time() ) );
        //if ( !$DB->insert_record('leapgradetracking_log', array( $type, $msg ) ) ) {
        //    echo 'Failed to insert a log into the leapgradetracking_log table.';
        //}
    }

    public function execute() {
        global $CFG, $DB;

        // Script start time.
        $time_start = microtime( true );

        echo "BEGIN >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n\n";

        // Truncate the log table.
//        if ( overnight::TRUNCATE_LOG ) {
            //$tmp = $DB->delete_records( 'leapgradetracking_log', array( 'id' => '*' ) );
            echo "Truncating leapgradetracking_log\n";
            $DB->delete_records( 'leapgradetracking_log', null );   
            echo "...done.\n";
//        }

        overnight::tlog( 'GradeTracker script, v' . overnight::VERSION . ', ' . overnight::BUILD . '.', 'hiya' );
        overnight::tlog( 'Started at ' . date( 'c', $time_start ) . '.', ' go ' );
        if ( overnight::THIS_COURSE ) {
            overnight::tlog( 'IMPORTANT! Processing only course \'' . overnight::THIS_COURSE . '\'.', 'warn' );
        }
        overnight::tlog( '', '----' );


        echo "\nEND   >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n";

    } // END method execute.

}
