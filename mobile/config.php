<?php
/***************************************************************
*  Copyright Notice
*
*  This script is (c) 2014 Brightcookie.com Educational Technologies
*  <enquiries@brightookie.com> http://www.brightcookie.com - all rights reserved
*
*  You can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*  The GNU General Public License can be found here
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This module is based on assignsubmission_onlinetext module of Moodle http://moodle.org/
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
include_once("../../../../../config.php");
//print_r($CFG->s3bucket);
// Amazon S3 credentials

define('S3_ACCESS_KEY_ID', $CFG->s3accesskey);

define('S3_SECRET_ACCESS_KEY',  $CFG->s3seretkey);
define('S3_BUCKET', $CFG->s3bucket);
define('S3_EXP_TIME', 2592000); // 12 hours
define('S3_MAX_FILE_SIZE', 5242880000); // 500 MB
	
define('S3_EXP_TIME_20Y', 37843200000); // 12 hours

?>