<?php

include_once( '../../config.php' );

defined( 'MOODLE_INTERNAL' ) || die();

$search = optional_param( 'q', '', PARAM_ALPHANUM );

if ( $search == '' ) {
    echo json_encode( array( null ) );
    exit(1);
}

$out = array();

if ( !$result = $DB->get_records_select( 'block_leap_coursecodes', 'code LIKE "%' . $search . '%"', null, 'code ASC', 'code,name' ) ) {
    echo json_encode( array( null ) );
    exit(1);

} else {
    // TODO: make the 'name' array element a concatenation of the course code and name, for clarity.
    foreach ( $result as $res ) {
        $out[] = array( 'id' => $res->code, 'name' => $res->code . ' (' . $res->name . ')' );
        error_log($res->code);
    }
}

// JSON content type. Either seems to work without problems (Debian 7/Apache 2).
header( 'Content-Type: application/json' );
//header( 'Content-Type: text/plain' );

echo json_encode( $out );
