<?php

namespace local_sdcgradetracking\task;

defined('MOODLE_INTERNAL') || die();

class overnight extends \core\task\scheduled_task {

    // Null or an int (course's id): run the script only for this course. For testing or one-offs.
    const THIS_COURSE = null; // null or e.g. 1234

    // Debugging.
    const DEBUG         = false;

    const VERSION       = '1.0.18';
    const BUILD         = '20141217';
    // Sample Leap Tracker API URL.
    const LEAP_TRACKER_API = 'http://leap.southdevon.ac.uk/people/%s.json?token=%s';
    // Number of decimal places in the processed targets (and elsewhere).
    const DECIMALS      = 3;
    // Search term to use when searching for courses to process.
    const IDNUMBERLIKE  = 'leapcore_%';
    //const IDNUMBERLIKE = 'leapcore_test';
    // Category details for the above columns to go into.
    const CATNAME       = 'Targets';


    public function get_name() {
        // Shown in admin screens.
        return get_string('pluginname', 'local_sdcgradetracking');
    }

    // A little function to make the db log look nice.
    public function tlog( $msg, $type = 'ok' ) {
        global $DB;
        $tmp = $DB->insert_record( 'sdcgradetracking_log', array( $type, $msg ) );
        //if ( !$DB->insert_record('sdcgradetracking_log', array( $type, $msg ) ) ) {
        //    echo 'Failed to insert a log into the sdcgradetracking_log table.';
        //}
    }

    public function execute() {
        
        // Script start time.
        $time_start = microtime(true);

        overnight::tlog( 'GradeTracker script, v' . overnight::VERSION . ', ' . overnight::BUILD . '.', 'hiya' );
        overnight::tlog( 'Started at ' . date( 'c', overnight::TIME_START ) . '.', ' go ' );
        if ( overnight::THIS_COURSE ) {
            overnight::tlog( 'IMPORTANT! Processing only course \'' . overnight::THIS_COURSE . '\'.', 'warn' );
        }
        overnight::tlog( '', '----' );


    } // END method execute.

}
