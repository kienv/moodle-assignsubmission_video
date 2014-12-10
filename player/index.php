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
include_once('../../../../../config.php');
include_once('../mobile/s3lib.php');
$file=$_GET['file'];
if(!$file || !$USER->id || ($USER->id<1))
{
	echo '<p style="color:red; font-style: italic;">You do not have permission to play this content!</p>';
	die();
}


$parray=explode("_",$file);
$userid=$parray[1];// User ID
$id=$parray[2];// Course Module ID

//print_r($parray);
//die();


$PAGE->set_url('/mod/assign/submission/video/player/index.php', array('id'=>$id, 'userid'=>$userid));



if (! $assignment = $DB->get_record("assign", array("id"=>$id))) {
    print_error('invalidid', 'assignment');
}

if (! $course = $DB->get_record("course", array("id"=>$assignment->course))) {
    print_error('coursemisconf', 'assignment');
}

if (! $user = $DB->get_record("user", array("id"=>$userid))) {
    print_error('usermisconf', 'assignment');
}

require_login($course->id, false);

$context = context_course::instance($course->id);

require_capability('mod/assign:view', $context);


$vlink=mymodule_get_s3_auth_link(S3_BUCKET,$file,S3_EXP_TIME);
//print_r($vlink);
?>
<html><head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<!-- A minimal Flowplayer setup to get you started -->
 

	<!-- 
		include flowplayer JavaScript file that does  
		Flash embedding and provides the Flowplayer API.
	-->
	<script type="text/javascript" src="./flowplayer-3.2.11.min.js"></script>
	
	<!-- some minimal styling, can be removed -->
	
	
	<!-- page title -->
	<title>Video player</title>
	<style>
		body{
			font: 13px/1.231 arial,helvetica,clean,sans-serif;
		}
		a:link, a:visited {
			color: #3373B0;
			text-decoration: none;
		}
		a:hover {
			color: #3373B0;
			text-decoration: underline;
		}
	</style>

</head><body style="padding:0px; margin:0px;">
	
		<!-- this A tag is where your Flowplayer will be placed. it can be anywhere -->
		<div style="width: 460px; height: 320px; margin-top: 10px; margin-bottom: 5px; padding: 4px; border: 1px solid #999; background: #FEFEFE; box-shadow: 0 0 3px #888;">
		<a  
			 href="<?php echo urlencode($vlink);//"http://video-js.zencoder.com/oceans-clip.mp4";?>"
			 style="display:block;width:460px;height:320px;"  
			 id="player"> 
		</a>
		</div>
		<div><a href="<?php echo($vlink); ?>">Download video</a></div>
	
		<!-- this will install flowplayer inside previous A- tag. -->
		<script>
			flowplayer("player", "./flowplayer-3.2.15.swf", {clip: { scaling:'fit',bufferLength:1, autoPlay: false, autoBuffering: true // <- do not place a comma here
			}});
		</script>
		
</body></html>