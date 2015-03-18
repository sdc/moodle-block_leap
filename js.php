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
 * This file is part of the Database module for Moodle
 *
 * @copyright 2005 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod_data
 */

require_once( '../../config.php' );

$coursecodes = new moodle_url( '/blocks/leap/coursecodes.php' );

$out = '$(document).ready(function () {

    var data = $("#id_config_coursecodes").val();
    var array = data.split(",");
    var out = [];
    for ( x in array ) {
        if (array[x] !== "") {
            out.push({"id":array[x],"name":array[x]});
        }
    }

    if (out.length > 0) {
        $("#id_config_coursecodes").tokenInput("' . $coursecodes . '", { prePopulate: out });
    } else {
        $("#id_config_coursecodes").tokenInput("' . $coursecodes . '");
    }

});';

echo $out;
