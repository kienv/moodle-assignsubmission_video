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

/**
 * Strings for component 'assignsubmission_video', language 'en'
 *
 * @package   assignsubmission_video
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allowvideosubmissions'] = 'Enabled';
$string['default'] = 'Enabled by default';
$string['default_help'] = 'If set, this submission method will be enabled by default for all new assignments.';
$string['enabled'] = 'Mobile video assignment';
$string['enabled_help'] = 'If enabled, students are able submit video from their mobile.';
$string['nosubmission'] = 'Nothing has been submitted for this assignment';
$string['video'] = 'Mobile video assignment';
$string['videofilename'] = 'video.html';
$string['videosubmission'] = 'Allow mobile video submission';
$string['pluginname'] = 'Mobile video assignment submissions';
$string['numwords'] = '({$a} words)';
$string['numwordsforlog'] = 'Submission word count: {$a} words';
$string['s3bucket'] = 'S3 bucket name';
$string['s3bucket_help'] = 'S3 bucket name storing uploaded video';
$string['s3accesskey'] = 'S3 access key';
$string['s3accesskey_help'] = 'S3 access key of Amazon S3 bucket storing uploaded video';
$string['s3seretkey'] = 'S3 secret key';
$string['s3seretkey_help'] = 'S3 secret key of Amazon S3 bucket storing uploaded video';
$string['s3maxupload'] = 'Max size upload(Mb)';
$string['s3maxupload_help'] = 'Maximum video upload size';
$string['s3maxvideolength'] = 'Maximum video length(seconds)';
$string['s3maxvideolength_help'] = 'Limitation of video record in seconds. Please note that maximum upload video size will be calculated based on maximum video length with highest quality(480x640). 10 seconds video will take about 5Mb.';
$string['maxvideosubmitlength_desciption'] = 'Please note that maximum upload video size will be calculated based on maximum video length with highest quality(480x640).<br/>10 seconds video will take about 5Mb.';
$string['maxvideosubmitlength'] = 'Max video recording length(seconds)';
$string['maxvideosizembs'] = 'Max video uploas size(Mb)';