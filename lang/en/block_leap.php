<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License; or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful;
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not; see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'block_leap'; language 'en'.
 *
 * @package    block_leap
 * @copyright  2014; 2015 Paul Vaughan {@link http://commoodle.southdevon.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname']           = 'Leap';
$string['addinstance']          = 'Add a new Leap block';

$string['leap_url']             = 'Leap installation URL';
$string['leap_url_desc']        = 'The URL (web address) of your Leap installation (e.g. <code>http://leap.southdevon.ac.uk</code>). Do not use a trailing slash.';

//$string['auth_username']        = '"Leap user" username';
//$string['auth_username_desc']   = 'The username of the webservice-privileged "Leap user" you set up when installing the <a href="https://github.com/sdc/moodle-local_leapwebservices" target="_blank">Leap Webservices plugin</a>.';

$string['auth_token']        	= 'Leap Web Services Token';
$string['auth_token_desc']   	= 'The token, from Leap\'s "Web Services" configuration settings, allowing a data request without authentication. Ensure it is kept secret.';

$string['generate_mag']         = 'MAG generation';
$string['generate_mag_desc']    = 'If Yes, will create gradebook columns for Minimum Achievable Grades and generate the grade from the LAT score stored in Leap. Otherwise, will not create and populate gradebook columns.';

$string['settings:global']      = 'Global settings';
$string['settings:course']      = 'This course\'s settings';

$string['grade_tracking']       = 'Grade Tracking';

$string['tracker_type']         = 'Tracker type';

$string['tracker_type_title:none']  = 'None';
$string['tracker_type_title:prod']  = 'Standard';
$string['tracker_type_title:dev']   = 'Development / Testing';

$string['tracker_type:none']    = 'None';
$string['tracker_type:core']    = 'Core course';
$string['tracker_type:english'] = 'English';
$string['tracker_type:maths']   = 'Maths';
$string['tracker_type:ppd']     = 'PPD';
$string['tracker_type:test']    = 'Test';

$string['course_type']          = 'Course type';

$string['course_type_title:none']   = 'None';
$string['course_type_title:asa2']   = 'AS/A2';
$string['course_type_title:btec']   = 'BTEC';
$string['course_type_title:gcse']   = 'GCSE';

$string['course_type:none']             = 'None';
$string['course_type:a2_generic']       = 'Generic AS/A2 Level';
$string['course_type:a2_artdes']        = 'Art &amp; Design';
$string['course_type:a2_artdesphoto']   = 'Art &amp; Design (Photography)';
$string['course_type:a2_artdestext']    = 'Art &amp; Design (Textiles)';
$string['course_type:a2_biology']       = 'Biology';
$string['course_type:a2_busstud']       = 'Business Studies';
$string['course_type:a2_chemistry']     = 'Chemistry';
$string['course_type:a2_englishlang']   = 'English Language';
$string['course_type:a2_englishlit']    = 'English Literature';
$string['course_type:a2_envsci']        = 'Environmental Science';
$string['course_type:a2_envstud']       = 'Environmental Studies';
$string['course_type:a2_filmstud']      = 'Film Studies';
$string['course_type:a2_geography']     = 'Geography';
$string['course_type:a2_govpoli']       = 'Government &amp; Politics';
$string['course_type:a2_history']       = 'History';
$string['course_type:a2_humanbiology']  = 'Human Biology';
$string['course_type:a2_law']           = 'Law';
$string['course_type:a2_maths']         = 'Maths';
$string['course_type:a2_mathsfurther']  = 'Further Maths';
$string['course_type:a2_media']         = 'Media';
$string['course_type:a2_philosophy']    = 'Philosophy';
$string['course_type:a2_physics']       = 'Physics';
$string['course_type:a2_psychology']    = 'Psychology';
$string['course_type:a2_sociology']     = 'Sociology';
$string['course_type:btec_generic']     = 'Generic BTEC';
$string['course_type:btec_ed_applsci']  = 'Extended Diploma in Applied Science';
$string['course_type:gcse_generic']     = 'Generic GCSE';
$string['course_type:gcse_english']     = 'English';
$string['course_type:gcse_maths']       = 'Maths';

$string['error:notconf']    = 'not configured';

$string['course_codes_header']          = 'Automatic Enrolment';
$string['course_codes_label']           = 'Adding course codes';
$string['course_codes_desc']            = 'Please create a comma-separated list of course codes (e.g. <code>AS3PSY1P,A23PSY1P,GC2PSY1P</code>) so that if that code is found against that user in Leap, they will be enrolled on this Moodle course.';
$string['course_codes']                 = 'Course Codes';

$string['gradebook:category_title']     = 'Targets';

// The following three lang strings are used to define the names of the three columns we create and populate. If they change,
// (either here or in the gradebook), then this script may not work as intended.
// * If you want to rename these columns BEFORE this script has already created columns with these names, do it.
// * If you want to rename these columns AFTER this script has already created columns with these names, you must change the
//   names here AND in the whole gradebook (for each course using the Leap plugin) AT THE SAME TIME and BEFORE
//   the script runs again.
$string['gradebook:tag']        = 'TAG';
$string['gradebook:l3va']       = 'L3VA';
$string['gradebook:mag']        = 'MAG';

$string['gradebook:tag_desc']   = 'Target Achievable Grade.';
$string['gradebook:l3va_desc']  = 'Level 3 Value Added.';
$string['gradebook:mag_desc']   = 'Indicative Minimum Achievable Grade.';
