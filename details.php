<?php

/**
 * Tracker and course type definitions.
 *
 * TODO: Potentially sort the nested arrays (e.g. in alphabetical order) instead of just adding them in in the required order.
 */

/**
 * Differrent types of tracker.
 */
$trackertypes = array (
    get_string( 'tracker_type_title:none', 'block_leapgradetracking' ) => array (
        ''          => get_string( 'tracker_type:none', 'block_leapgradetracking' ),
    ),

    get_string( 'tracker_type_title:prod', 'block_leapgradetracking' ) => array (
        'core'      => get_string( 'tracker_type:core', 'block_leapgradetracking' ),
        'english'   => get_string( 'tracker_type:english', 'block_leapgradetracking' ),
        'maths'     => get_string( 'tracker_type:maths', 'block_leapgradetracking' ),
        'ppd'       => get_string( 'tracker_type:ppd', 'block_leapgradetracking' ),
    ),

    get_string( 'tracker_type_title:dev', 'block_leapgradetracking' ) => array (
        'test'      => get_string( 'tracker_type:test', 'block_leapgradetracking' ),
    ),
);

/**
 * Differrent types of course for tracking.
 */
$coursetypes = array (
    get_string( 'course_type_title:none', 'block_leapgradetracking' ) => array (
        ''          => get_string( 'course_type:none', 'block_leapgradetracking' ),
    ),

    get_string( 'course_type_title:asa2', 'block_leapgradetracking' ) => array (
        'a2_artdes'         => get_string( 'course_type:a2_artdes', 'block_leapgradetracking' ),
        'a2_artdesphoto'    => get_string( 'course_type:a2_artdesphoto', 'block_leapgradetracking' ),
        'a2_artdestext'     => get_string( 'course_type:a2_artdestext', 'block_leapgradetracking' ),
        'a2_biology'        => get_string( 'course_type:a2_biology', 'block_leapgradetracking' ),
        'a2_busstud'        => get_string( 'course_type:a2_busstud', 'block_leapgradetracking' ),
        'a2_chemistry'      => get_string( 'course_type:a2_chemistry', 'block_leapgradetracking' ),
        'a2_englishlang'    => get_string( 'course_type:a2_englishlang', 'block_leapgradetracking' ),
        'a2_englishlit'     => get_string( 'course_type:a2_englishlit', 'block_leapgradetracking' ),
        'a2_envsci'         => get_string( 'course_type:a2_envsci', 'block_leapgradetracking' ),
        'a2_envstud'        => get_string( 'course_type:a2_envstud', 'block_leapgradetracking' ),
        'a2_filmstud'       => get_string( 'course_type:a2_filmstud', 'block_leapgradetracking' ),
        'a2_geography'      => get_string( 'course_type:a2_geography', 'block_leapgradetracking' ),
        'a2_govpoli'        => get_string( 'course_type:a2_govpoli', 'block_leapgradetracking' ),
        'a2_history'        => get_string( 'course_type:a2_history', 'block_leapgradetracking' ),
        'a2_humanbiology'   => get_string( 'course_type:a2_humanbiology', 'block_leapgradetracking' ),
        'a2_law'            => get_string( 'course_type:a2_law', 'block_leapgradetracking' ),
        'a2_maths'          => get_string( 'course_type:a2_maths', 'block_leapgradetracking' ),
        'a2_mathsfurther'   => get_string( 'course_type:a2_mathsfurther', 'block_leapgradetracking' ),
        'a2_media'          => get_string( 'course_type:a2_media', 'block_leapgradetracking' ),
        'a2_philosophy'     => get_string( 'course_type:a2_philosophy', 'block_leapgradetracking' ),
        'a2_physics'        => get_string( 'course_type:a2_physics', 'block_leapgradetracking' ),
        'a2_psychology'     => get_string( 'course_type:a2_psychology', 'block_leapgradetracking' ),
        'a2_sociology'      => get_string( 'course_type:a2_sociology', 'block_leapgradetracking' ),
    ),

    get_string( 'course_type_title:btec', 'block_leapgradetracking' ) => array (
        'btec_ed_applsci'   => get_string( 'course_type:btec_ed_applsci', 'block_leapgradetracking' ),
    ),

    get_string( 'course_type_title:gcse', 'block_leapgradetracking' ) => array (
        'gcse_english'      => get_string( 'course_type:gcse_english', 'block_leapgradetracking' ),
        'gcse_maths'        => get_string( 'course_type:gcse_maths', 'block_leapgradetracking' ),
    ),
);

/**
 * Numbers required for making a MAG from a L3VA.
 */
$l3va_data = array(
    'a2_artdes'         => array( 'm' => 4.4727, 'c' => 98.056 ),
    'a2_artdesphoto'    => array( 'm' => 4.1855, 'c' => 79.949 ),
    'a2_artdestext'     => array( 'm' => 3.9430, 'c' => 66.967 ),
    'a2_biology'        => array( 'm' => 5.2471, 'c' => 166.67 ),
    'a2_busstud'        => array( 'm' => 4.8372, 'c' => 123.41 ),
    'a2_chemistry'      => array( 'm' => 4.5169, 'c' => 129.00 ),
    'a2_englishlang'    => array( 'm' => 4.5773, 'c' => 112.14 ),
    'a2_englishlit'     => array( 'm' => 5.0872, 'c' => 137.31 ),
    'a2_envsci'         => array( 'm' => 6.1058, 'c' => 196.66 ),
    'a2_envstud'        => array( 'm' => 6.1058, 'c' => 196.66 ),
    'a2_filmstud'       => array( 'm' => 4.0471, 'c' => 76.470 ),
    'a2_geography'      => array( 'm' => 5.4727, 'c' => 156.16 ),
    'a2_govpoli'        => array( 'm' => 5.3215, 'c' => 145.38 ),
    'a2_history'        => array( 'm' => 4.6593, 'c' => 118.98 ),
    'a2_humanbiology'   => array( 'm' => 5.2471, 'c' => 166.67 ),
    'a2_law'            => array( 'm' => 5.1047, 'c' => 140.69 ),
    'a2_maths'          => array( 'm' => 4.5738, 'c' => 119.43 ),
    'a2_mathsfurther'   => array( 'm' => 4.4709, 'c' => 106.40 ),
    'a2_media'          => array( 'm' => 4.2884, 'c' => 90.279 ),
    'a2_philosophy'     => array( 'm' => 4.7645, 'c' => 128.95 ),
    'a2_physics'        => array( 'm' => 5.0965, 'c' => 159.08 ),
    'a2_psychology'     => array( 'm' => 5.3872, 'c' => 158.71 ),
    'a2_sociology'      => array( 'm' => 4.9645, 'c' => 122.95 ),

    'btecex_applsci'    => array( 'm' => 10.606, 'c' => 269.15 ),

    'default'           => array( 'm' => 4.8008, 'c' => 126.18 ),

    'btec'              => array( 'm' => 3.9, 'c' => 90 ),

);
