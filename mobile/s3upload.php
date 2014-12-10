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
require_once('s3lib.php');

/*echo '<link href="type/video/uploader/default.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="skin/js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="type/video/uploader/swfupload.js"></script>
<script type="text/javascript" src="type/video/uploader/fileprogress.js"></script>
<script type="text/javascript" src="type/video/uploader/jquery.swfupload.js"></script>';
echo s3_swf_upload();*/
/*********FUNCTION****************/
function s3_swf_upload()
{
	$str='
<div>
	<label for="txtFileName"><strong>Choose a video file</strong></label><br/>
	<input type="text" id="txtFileName" disabled="true" class="text ui-widget-content ui-corner-all" style="width: 230px;"/>
	<div id="swfupload-control" style="display:inline;"><input type="button" id="mybutton" /></div>
	<div id="fsUploadProgress2" style="display:none;"></div>
</div>
<div class="flash" id="fsUploadProgress">
	<!-- This is where the file progress gets shown.  SWFUpload doesn"t update the UI directly.
				The Handlers (in handlers.js) process the upload events and make the UI updates -->
</div>                         
<input type="hidden" name="hidFileID" id="hidFileID" value="" />
<!-- This is where the file ID is stored after SWFUpload uploads the file and gets the ID back from upload.php -->

		

';
return $str;

}
function s3_upload_jsscript()
{
	$SUCCESS_REDIRECT = "";
	$SWFRoot = "type/video/uploader/";

	$isMacUser = (preg_match("/macintosh/",strtolower($_SERVER["HTTP_USER_AGENT"])) ? true : false);

	
	$expTime = time() + S3_EXP_TIME; //12 hours
	$expTimeStr = gmdate("Y-m-d\TH:i:s\Z", $expTime);
	$policyDoc = "{
	        \"expiration\": \"" . $expTimeStr . "\",
	        \"conditions\": [
	        {\"bucket\": \"" . S3_BUCKET . "\"},
	        [\"starts-with\", \"\$key\", \"\"],
	        {\"acl\": \"private\"},
	        [\"content-length-range\", 0, ". S3_MAX_FILE_SIZE ."],
	        {\"success_action_status\": \"201\"},
	        [\"starts-with\", \"\$Filename\", \"\"]
	       
	      ]
	}";
	$policyDoc = implode(explode("\r", $policyDoc));
	$policyDoc = implode(explode("\n", $policyDoc));
	$policyDoc64 = base64_encode($policyDoc);
	$sigPolicyDoc = base64_encode(hash_hmac("sha1", $policyDoc64, S3_SECRET_ACCESS_KEY, TRUE));
	$key=(microtime(true)*10000);
	
	
	$str='
		<script type="text/javascript">
		var trackFiles = [];
		var trackFilesCount = 0;
		var trackSentURL = false;
		var forceDone = false;
		var forceFile = null;
		var master = null;
		var MacMinSizeUpload = 150000; // 150k, this is not cool :(
		var MacDelay = 10000; // 10 secs.
		var isMacUser = '.($isMacUser ? "true" : "false").';
		var successURL = "";';
	$str.='
$(function(){	

	$("#swfupload-control").swfupload({		
		upload_url: "http://'.S3_BUCKET.'.s3.amazonaws.com/",
		post_params: {"AWSAccessKeyId":"'.S3_ACCESS_KEY_ID.'", "key":"\${filename}", "acl":"private", "policy":"'.$policyDoc64.'", "signature":"'.$sigPolicyDoc.'","success_action_status":"201", "content-type":""},
		http_success : [201], 
		assume_success_timeout : '.($isMacUser ? 5 : 0).',

		// File Upload Settings
		file_post_name: "file",
		file_size_limit : "506000",    // 500 MB
		file_types : "*.mp4;*.avi;*.mwv;*.flv;*.gp3;*.mpg;*.mov",			// or you could use something like: "*.doc;*.wpd;*.pdf",
		file_types_description : "Video Files",
		file_upload_limit : "0",
		file_queue_limit : "1",			
		
		button_image_url : "type/video/uploader/button.gif",
		button_placeholder_id : "mybutton",
		button_placeholder : $("#mybutton"),
		button_width: 174,
		button_height: 30,		
		
		button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
		button_cursor: SWFUpload.CURSOR.HAND,
		moving_average_history_size: 10,
	   
		// Flash Settings
		flash_url : "type/video/uploader/swfupload.swf",
		custom_settings : {
		  progressTarget : "fsUploadProgress",
		 /* cancelButtonId : "btnCancel"*/
		  upload_successful : false
		},
		// Debug Settings
		debug: false
	})
	.bind("fileDialogStart", function(event, file){
		var swfu = $.swfupload.getInstance("#swfupload-control");
		var txtFileName = document.getElementById("txtFileName");
		txtFileName.value = "";
		swfu.cancelUpload();	
	})
	
	.bind("uploadError", function(event, file, errorCode, message){
		var swfu = $.swfupload.getInstance("#swfupload-control");
		try {
			
			if (errorCode === SWFUpload.UPLOAD_ERROR.FILE_CANCELLED) {
				// Don"t show cancelled error boxes
				return;
			}
			var txtFileName = document.getElementById("txtFileName");
			txtFileName.value = "";
			validateForm();		
			
			file.id = "singlefile";
			var progress = new FileProgress(file, swfu.customSettings.progressTarget);
			progress.setError();
			progress.toggleCancel(false);
			
			progress.setStatus("Upload Error: " + message);
			upload_s3_fail(errorCode);
			
		} catch (ex) {
			swfu.debug(ex);
		}		
	})
	
	.bind("fileQueued", function(event, file){
		try {
			var txtFileName = document.getElementById("txtFileName");
			txtFileName.value = file.name+" ["+file.size+"]";
			var swfu = $.swfupload.getInstance("#swfupload-control");
			//post_params: {"AWSAccessKeyId":"<?=AWS_ACCESS_KEY_ID?>", "key":"${filename}", "acl":"private", "policy":"<?=$policyDoc64?>", "signature":"<?=$sigPolicyDoc?>","success_action_status":"201", "content-type":"image/"},
			swfu.setPostParams({"AWSAccessKeyId":"'.S3_ACCESS_KEY_ID.'", "key":"assignment_"+($("#upload_id").val()), "acl":"private", "policy":"'.$policyDoc64.'", "signature":"'.$sigPolicyDoc.'","success_action_status":"201"})
			//up_video"+($("#upload_id").val())+file.name.substring(file.name.lastIndexOf(".")).toLowerCase()
		} catch (e) {
		}		
	})
	.bind("fileQueueError", function(event, file, errorCode, message){
		alert("Size of the file "+file.name+" is greater than limit");
	})
	.bind("fileDialogComplete", function(event, numFilesSelected, numFilesQueued){

	})
	
	.bind("uploadStart", function(event, file){
		var swfu = $.swfupload.getInstance("#swfupload-control");
		try {
			//var progress = new FileProgress(file, swfu.customSettings.progressTarget);
			//progress.setStatus("Uploading...");
			//progress.toggleCancel(true, this);
			trackFiles[trackFilesCount++] = file.name;
			updateDisplay.call(swfu,file);
			swfu.setButtonDisabled(true);
			//upload_s3_start();
		}
		catch (ex) {}
		return true;	
	})
	
	.bind("uploadProgress", function(event, file, bytesLoaded, bytesTotal){		
		var swfu = $.swfupload.getInstance("#swfupload-control");
		try {
			var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);
			file.id = "singlefile";	
			var progress = new FileProgress(file, swfu.customSettings.progressTarget);
			var animPic = document.getElementById("loadanim");
			if (animPic != null) {
			  animPic.style.display = "block";
			}			
			progress.setStatus("Uploading...[ "+Math.round(bytesLoaded/1024)+" Kb /"+Math.round(bytesTotal/1024)+" Kb ]"+(isMacUser && file.size < MacMinSizeUpload ? " ...Finishing up, 10 second delay" : ""));
			progress.setProgress(percent);
			$("#fsUploadProgress2").text(percent+"%");
			updateDisplay.call(swfu, file);
		} catch (ex) {
			swfu.debug(ex);
		}
	})	
	.bind("uploadSuccess", function(event, file, serverData){
		var swfu = $.swfupload.getInstance("#swfupload-control");
		try {
			file.id = "singlefile";
			var progress = new FileProgress(file, swfu.customSettings.progressTarget);
			progress.setComplete();
			progress.setStatus("Complete.");
			progress.toggleCancel(false);
	 
			if (serverData === " ") {
				swfu.customSettings.upload_successful = false;
			} else {
				swfu.customSettings.upload_successful = true;
				document.getElementById("hidFileID").value = serverData;
			}
		} catch (ex) {
			swfu.debug(ex);
		}
	})
	
	.bind("uploadComplete", function(event, file){
		// upload has completed, try the next one in the queue
		//$(this).swfupload("startUpload");
		var swfu = $.swfupload.getInstance("#swfupload-control");
		try {
			if (swfu.customSettings.upload_successful) {
				swfu.setButtonDisabled(true);
				//CALL BACK uploadDone(); OR
				//FORM SUBMIT document.forms[0].submit();
				upload_s3_success(file.name);
				//alert("DONE!!");
			} else {
				file.id = "singlefile";	// This makes it so FileProgress only makes a single UI element, instead of one for each file
				var progress = new FileProgress(file, swfu.customSettings.progress_target);
				progress.setError();
				progress.setStatus("File rejected");
				progress.toggleCancel(false);
				
				var txtFileName = document.getElementById("txtFileName");
				txtFileName.value = "";
				validateForm();
				upload_s3_fail("There was a problem with the upload.\nThe server did not accept it.");
				
			}
		} catch (e) {
		}	
	})

/// END	
});

function updateDisplay(swfu,file) {
  // isMacUser Patch Begin
  if ( isMacUser ) {
	if (file == null && forceDone) {
      master.cancelUpload(forceFile.id,false);
      pauseProcess(500); // allow flash? to update itself
      master.uploadSuccess(forceFile,null);
      master.uploadComplete(forceFile);
      forceDone = false;
      return; 
    }
    // check for small files less < 150k
    // note: dialup users will get bad results.
    if (file.size < MacMinSizeUpload && !forceDone) {
      master = swfu;
      if (!forceDone) {
        forceFile = file;
        // wait <n> seconds before enforcing upload done!
        setTimeout("updateDisplay("+null+","+null+")",MacDelay);
        forceDone = true;
      }
    }
  } // isMacUser Patch End
}

// this should *not* be needed, just testing an idea 
function pauseProcess(millis) {
  var sDate = new Date();
  var cDate = null;

  do { 
    cDate = new Date(); 
  } while(cDate-sDate < millis);
}
function validateForm() {}
</script>
';
	return $str;
}
?>