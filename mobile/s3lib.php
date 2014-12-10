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
// grab this with "pear install --onlyreqdeps Crypt_HMAC"
require_once('HMAC.php');
require_once('S3.php');
require_once('config.php');

	
//echo mymodule_get_s3_auth_link('edupov','Kien_test_613.flv',2592000);
// DELETE object function
function deleteObj($oname)
{
	$s3 = new S3(S3_ACCESS_KEY_ID, S3_SECRET_ACCESS_KEY);
	return $s3->deleteObject(S3_BUCKET, $oname);
}
//getBucket

// get bucket files function
//print_r(getBucket());
function getBucket()
{
	$s3 = new S3(S3_ACCESS_KEY_ID, S3_SECRET_ACCESS_KEY);
	$bucket=$s3->getBucket(S3_BUCKET);
	//print_r($bucket);
	//echo "test again";
	$files=array();
	$i=0;
	foreach($bucket as $key=>$val)
	{
		$files[$i]=substr($key,0,strpos($key,"."));
		$i++;
	}
	return $files;
}

//echo deleteObj("video1283hfghgf301827051056.mp4");
/**
 * Generate a link to download a file from Amazon S3 using query string
 * authentication. This link is only valid for a limited amount of time.
 *
 * @param $bucket The name of the bucket in which the file is stored.
 * @param $filekey The key of the file, excluding the leading slash.
 * @param $expires The amount of time the link is valid (in seconds).
 * @param $operation The type of HTTP operation. Either GET or HEAD.
 */
function mymodule_get_s3_auth_link($bucket, $filekey, $expires = 2592000, $operation = 'GET') 
{
	$expire_time = time() + $expires;
	$filekey = rawurlencode($filekey);
	$filekey = str_replace('%2F', '/', $filekey);
	$path = $bucket .'/'. $filekey;
	/**
	  * StringToSign = HTTP-VERB + "\n" +
	  * Content-MD5 + "\n" +
	  * Content-Type + "\n" +
	  * Expires + "\n" +
	  * CanonicalizedAmzHeaders +
	  * CanonicalizedResource;
	  */
	$stringtosign =
	$operation ."\n". // type of HTTP request (GET/HEAD)
	"\n". // Content-MD5 is meaningless for GET
	"\n". // Content-Type is meaningless for GET

	$expire_time ."\n". // set the expire date of this link
	"/$path"; // full path (incl bucket), starting with a /

	$signature = urlencode(mymodule_constructSig($stringtosign));

	$url = sprintf('http://%s.s3.amazonaws.com/%s?AWSAccessKeyId=%s&Expires=%u&Signature=%s',$bucket,$filekey,S3_ACCESS_KEY_ID,$expire_time,$signature);

	return $url;
}

//getUploadSign();

function getUploadSign($succ_direct)
{
	$expTime = time() + S3_EXP_TIME;
	$expTimeStr = gmdate('Y-m-d\TH:i:s\Z', $expTime); 
	// create policy document 
	$policyDoc = ' 
	{"expiration": "' . $expTimeStr . '", 
	  "conditions": [ 
	    {"bucket": "' . S3_BUCKET . '"}, 
	    ["starts-with", "$key", ""], 
	    {"acl": "private"}, 
	    {"success_action_redirect": "'.$succ_direct.'"}, 
	    ["content-length-range", 0, ' . S3_MAX_FILE_SIZE . '] 
	  ] 
	} 
	'; 

	//echo "policyDoc: " . $policyDoc . '<BR/>'; 
	// remove CRLFs from policy document 
	$policyDoc = implode(explode('\r', $policyDoc)); 
	$policyDoc = implode(explode('\n', $policyDoc)); 
	$policyDoc64 = base64_encode($policyDoc); // encode to base 64 
	// create policy document signature 
	$sigPolicyDoc = base64_encode(hash_hmac("sha1", $policyDoc64, $AWS_SECRET_KEY, TRUE/*raw_output*/));  
	 
	$re['bucket']=  S3_BUCKET;
	$re['access_key']=  S3_ACCESS_KEY_ID;
	$re['success_direct']=  $succ_direct;
	$re['policyDoc64']=  $policyDoc64;
	$re['sigPolicyDoc']=  $sigPolicyDoc;
	$re['id']=  (microtime(true)*10000);
	//$re['maxfile']=  S3_MAX_FILE_SIZE ;
	return json_encode($re);
}

function mymodule_hex2b64($str) {
	$raw = '';
	for ($i=0; $i < strlen($str); $i+=2) {
		$raw .= chr(hexdec(substr($str, $i, 2)));
	}
	return base64_encode($raw);
}
 
function mymodule_constructSig($str) {
$hasher = new Crypt_HMAC(S3_SECRET_ACCESS_KEY, 'sha1');
$signature = mymodule_hex2b64($hasher->hash($str));
return $signature;
}
?>