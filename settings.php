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
 * This file defines the admin settings for this plugin
 *
 * @package   assignsubmission_onlinetext
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$settings->add(new admin_setting_configcheckbox('assignsubmission_onlinetext/default',
                   new lang_string('default', 'assignsubmission_onlinetext'),
                   new lang_string('default_help', 'assignsubmission_onlinetext'), 0));
$settings->add(new admin_setting_configtext('s3bucket', get_string('s3bucket', 'assignsubmission_video'),
                                                get_string('s3bucket_help', 'assignsubmission_video'), null,
                                                 PARAM_TEXT,60));
$settings->add(new admin_setting_configtext('s3accesskey',  get_string('s3accesskey', 'assignsubmission_video'),
                                                get_string('s3accesskey_help', 'assignsubmission_video'), null,
                                                 PARAM_TEXT,60));
$settings->add(new admin_setting_configpasswordunmask('s3seretkey', get_string('s3seretkey', 'assignsubmission_video'),
                                                get_string('s3seretkey_help', 'assignsubmission_video'), null,
                                                 PARAM_TEXT,60));
/*$settings->add(new admin_setting_configtext('s3maxupload',  get_string('s3maxupload', 'assignsubmission_video'),
                                                get_string('s3maxupload_help', 'assignsubmission_video'), 50,
                                                 PARAM_INT,15));												 
*/
$settings->add(new admin_setting_configtext('s3maxvideolength',  get_string('s3maxvideolength', 'assignsubmission_video'),
                                                get_string('s3maxvideolength_help', 'assignsubmission_video'), 300,
                                                 PARAM_INT,15));	
