# SDC Admin Tools

Moodle admin tool to do minor admin tasks and summarise key server/Moodle information at South Devon College. Currently it does:

Has been checked against the 'Code Checker' and 'Moodle PHPDoc Check' local plugins at various points for Moodle coding standards conformity and appropriate documentation of code.

## Requirements

Moodle 2.7 or newer. Tested exhaustively with 2.7.4.

## Installation

* Copy the 'sdcgradetracking' folder into /local/
* Visit your Moodle as admin and click on Notifications, follow prompts.

## Use 

After installation, you should see a new option 'SDC Tools' in the Site Administration &rarr; Development menu. There will also be a new option 'Empty email check' in the Site Administration &rarr; Reports menu (subject to change as the plugin develops).  Click on either of these to scan and report on any users without an email address.

## To do

* Make email domain check configurable.
* Make the absence activity checker configurable.
* More detailed breakdown of modules available in the course.
* Largest / smallest / busiest / quietest courses.
* Ability to toggle backups for individual courses.

## History

* 2015-xx-xx, version 0.1:      Initial release.

## Author / Contact

&copy; 2014, 2015 Paul Vaughan, South Devon College. paulvaughan@southdevon.ac.uk
