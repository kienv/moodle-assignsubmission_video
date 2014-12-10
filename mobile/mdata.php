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

include_once('./s3lib.php');
$u=$_REQUEST['username'];
$p=$_REQUEST['password'];
$t=$_REQUEST['task'];
$response= new stdClass();
$MUSER= new stdClass();
if(!($MUSER->id)&& ($u) && ($p))
{
	 //This is code when connect from standalone broadcaster version
	 $user = authenticate_user_login($u, $p);
	 if($user)
	 {
	
        //complete_user_login($user);
		
	 	$MUSER->id=$user->id;
		$MUSER->firstname=$user->firstname;
		$MUSER->lastname=$user->lastname;
		
	 }
}


if($t=='checkLogin' && !empty($u) && !empty($p))
{

	//session_get_instance()->terminate_current();
	unset($MUSER);
	unset($user);
	$user = authenticate_user_login($u, $p);
	
	if($user)
	{
		add_to_log(SITEID, 'user', 'login', "Video assigment mobile login",$user->id, 0, $user->id);
		//complete_user_login($user);

		$MUSER->id=$user->id;
		$MUSER->firstname=$user->firstname;
		$MUSER->lastname=$user->lastname;
		$response->result=true;
		$response->s3info=get_upload_info();
		$response->user=$MUSER;
	}
	else
	{
		$response->result=false;
		$response->message="Invalid login detail";
	}
}
if($t=='getCourses' && ($MUSER->id))
{
	$courses = enrol_get_users_courses($MUSER->id,true,NULL, 'visible DESC, fullname ASC');
	$response->result=true;
	$response->courses=$courses;
}
if($t=='getAssignments' && ($MUSER->id))
{
	//define('MOODLE_INTERNAL',true);
	//require_once($CFG->dirroot . '/mod/assign/submission/video/locallib.php');
	$cid=$_REQUEST['cid'];
	if(!$cid) $cid=-1;
	global $DB,$CFG;
	//$sql="SELECT * FROM {$CFG->prefix}assignment WHERE course={$cid} AND assignmenttype='video'";
	$sql="SELECT DISTINCT a.* FROM {$CFG->prefix}assign a,{$CFG->prefix}assign_plugin_config b
WHERE a.id=b.assignment AND a.course= {$cid} AND b.plugin='video' AND b.subtype='assignsubmission' AND b.name='enabled' AND b.value=1";
	$assignments=$DB->get_records_sql($sql);
	
	
	foreach($assignments as $assignment){
		$sql="SELECT * FROM {$CFG->prefix}assign_plugin_config b WHERE  b.plugin='video' AND b.subtype='assignsubmission' AND b.name='maxvideosubmitlength' AND b.assignment='{$assignment->id}'";
		$maxvideosubmitlength=$DB->get_record_sql($sql);
		if($maxvideosubmitlength) $assignment->maxvideosubmitlength=$maxvideosubmitlength->value; 
		else $assignment->maxvideosubmitlength=$CFG->s3maxvideolength;
		$assignment->maxvideosizembs=($assignment->maxvideosubmitlength)*5/10;
		
		/*$sql="SELECT * FROM {$CFG->prefix}assign_plugin_config b WHERE  b.plugin='video' AND b.subtype='assignsubmission' AND b.name='maxvideosizembs' AND b.assignment='{$assignment->id}'";
		$maxvideosizembs=$DB->get_record_sql($sql);
		if($maxvideosizembs) $assignment->maxvideosizembs=$maxvideosizembs->value;
		else $assignment->maxvideosizembs=$CFG->s3maxupload;*/
		
		//print_r($assignment);
		$assignments->{$assignment->id}=$assignment;
	}
	$response->result=true;
	$response->timecurrent=time();
	$response->s3maxupload=$CFG->s3maxupload;
	$response->s3maxvideolength=$CFG->s3maxvideolength;
	
	$response->assignments=$assignments;
}
if($t=='getSubmission' && ($MUSER->id))
{
	$aid=$_REQUEST['aid'];
	
	if(!$aid) $aid=-1;
	$file="assignment_{$MUSER->id}_{$aid}_video.mp4";
	$vlink=mymodule_get_s3_auth_link(S3_BUCKET,$file,S3_EXP_TIME);
	
	global $DB,$CFG;
	//$sql="SELECT * FROM {$CFG->prefix}assignsubmission_video WHERE assignment={$aid} AND userid={$MUSER->id}";
	//$sql="SELECT a.id,a.video as data1,c.grade,d.commenttext as submissioncomment,c.timemodified as timemarked FROM mdl_assignsubmission_video a, mdl_assign_submission b,mdl_assign_grades c, mdl_assignfeedback_comments d WHERE a.assignment={$aid} AND b.userid={$MUSER->id} AND a.submission=b.id AND c.assignment={$aid} AND c.userid={$MUSER->id} AND d.assignment={$aid} AND d.grade=c.id";
	$sql="SELECT t1.*,c.grade,d.commenttext as submissioncomment,c.timemodified as timemarked 
			FROM (SELECT a.id,a.video as data1,a.assignment,b.userid
			FROM {$CFG->prefix}assignsubmission_video a, {$CFG->prefix}assign_submission b
			WHERE a.submission=b.id AND b.userid={$MUSER->id} AND a.assignment = {$aid}) t1
			LEFT JOIN {$CFG->prefix}assign_grades c
			ON c.assignment =t1.assignment AND c.userid=t1.userid
			LEFT JOIN  {$CFG->prefix}assignfeedback_comments d
			ON d.assignment=t1.assignment";
	
	$submissions=$DB->get_records_sql($sql);
	
	$response->result=true;
	$response->assignmentid=$aid;
	$response->timecurrent=time();
	$response->vfile=$file;
	$response->vlink=$vlink;
	$response->submissions=$submissions;
}
if($t=='submitAssignment' && ($MUSER->id))
{
	global $DB,$CFG;
	$data=str_replace('<!-- video -->',"",$_REQUEST["des"]).'<!-- video -->';
	
	if(!$_REQUEST['aid']) $aid=-1;
	else $aid=$_REQUEST['aid'];
	
	$submission= new stdClass();
	$submission->assignment=$aid;
	$submission->userid=$MUSER->id;
	$submission->timecreated=time();
	$submission->timemodified=time();
	$submission->status='submitted';
	
	
	
	
	$sql="SELECT * FROM {$CFG->prefix}assign_submission WHERE assignment={$aid} AND userid={$MUSER->id}";
	$submission1=$DB->get_record_sql($sql);
	$sid=0;
	if($submission1)
	{
		$submission->id=$submission1->id;
		$rs=$DB->update_record("assign_submission",$submission);
		$sid=$submission1->id;
	}
	else
	{
		$rs=$DB->insert_record("assign_submission",$submission);
		$sid=$rs;
	}
	$submissionvideo= new stdClass();
	$submissionvideo->assignment=$aid;
	
	$submissionvideo->submission=$sid;
	$submissionvideo->video=$data;
	$submissionvideo->onlineformat=1;
	
	$sql="SELECT * FROM {$CFG->prefix}assignsubmission_video WHERE assignment={$aid} AND submission={$sid}";
	$submissionv=$DB->get_record_sql($sql);
	if($submissionv)
	{
		$submissionvideo->id=$submissionv->id;
		$rs=$DB->update_record("assignsubmission_video",$submissionvideo);
		
	}
	else
	{
		$rs=$DB->insert_record("assignsubmission_video",$submissionvideo);
	}
	//********************/
	
	
	$response->assignment=$aid;
	if($rs) $response->result=true;
	else $response->result=false;
	
	
}

function get_upload_info()
{   
	require_once('s3lib.php');
	global $CFG;

	$SUCCESS_REDIRECT = $CFG->wwwroot.'/mod/assign/submission/video/mobile/s3response.php';
	$S3_FOLDER = ''; 
	

	$expTime = time() + S3_EXP_TIME; //12 hours
	$expTimeStr = gmdate('Y-m-d\TH:i:s\Z', $expTime); 


	$policyDoc = ' 
	{"expiration": "' . $expTimeStr . '", 
	  "conditions": [ 
		{"bucket": "' . S3_BUCKET . '"}, 
		["starts-with", "$key", "' . $S3_FOLDER . '"], 
		{"acl": "private"}, 
		{"success_action_redirect": "' . $SUCCESS_REDIRECT . '"}, 
		["content-length-range", 0, ' . S3_MAX_FILE_SIZE . '] 
	  ] 
	} 
	'; 

	$policyDoc = implode(explode('\r', $policyDoc)); 
	$policyDoc = implode(explode('\n', $policyDoc)); 
	$policyDoc64 = base64_encode($policyDoc); // encode to base 64 
	$sigPolicyDoc = base64_encode(hash_hmac("sha1", $policyDoc64, S3_SECRET_ACCESS_KEY, TRUE)); 
	$videoID=microtime(true)*10000;


	header('Content-type: application/json');   
	// create file transfer form 
	//echo '{"url":"http://' . S3_BUCKET . '.s3.amazonaws.com/","vid":"'.$videoID.'","udata":"test"}';
	$s3info= new stdClass();
	$s3info->url="http://".S3_BUCKET.".s3.amazonaws.com/";
	$udata=new stdClass();
	$udata->key="{$S3_FOLDER}video.mp4";
	$udata->AWSAccessKeyId=S3_ACCESS_KEY_ID;
	$udata->acl="private";
	$udata->success_action_redirect=$SUCCESS_REDIRECT;
	$udata->policy=$policyDoc64;
	$udata->signature=$sigPolicyDoc;
	$s3info->udata=$udata;
	return $s3info;
}

echo json_encode($response);
?>
