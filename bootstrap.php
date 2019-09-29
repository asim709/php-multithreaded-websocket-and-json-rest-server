<?php
/*
	Created by: Asim Ishaq

	Purpose: Intercept all web requests, identify api requests and load relevant processes to handle the request.
*/

error_reporting ( E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_WARNING ^ E_ERROR ^ E_STRICT );
//error_reporting (E_ALL); ini_set('display_errors', 1);

if (isset($_SERVER['HTTP_ORIGIN'])) {
  	header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

// =======================================================================//
// COMMONLY USED PATHS
// =======================================================================//

$protocol = "http://";

// The folder of your project
define ( 'APP_ROOT', str_replace ( '\\', '/', getcwd () ) );
if($_SERVER['SERVER_PORT'] != 80){
	// Web address (url) fro your project folder
    define ( 'APP_WEB_ROOT', dirname ( $protocol . $_SERVER ['SERVER_NAME'] .':'.$_SERVER['SERVER_PORT']. $_SERVER ['SCRIPT_NAME'] ) );
    define ( 'APP_SERVER_WEB_ROOT', $protocol. $_SERVER ['SERVER_NAME'].':'.$_SERVER['SERVER_PORT']);
} else{
    define ( 'APP_WEB_ROOT', dirname ( $protocol . $_SERVER ['SERVER_NAME'] . $_SERVER ['SCRIPT_NAME'] ) );
    define ( 'APP_SERVER_WEB_ROOT', $protocol . $_SERVER ['SERVER_NAME']);
}

define ( 'APP_TEMP_ROOT', APP_ROOT."/temp" );
define ( 'APP_TEMP_WEB_ROOT', APP_WEB_ROOT."/temp" );
define ( 'REQUEST_TYPE', strtoupper ( $_SERVER ['REQUEST_METHOD'] ) );

if($_SERVER['SERVER_PORT'] != 80){
	define ( 'REQUEST_URL', $protocol . $_SERVER ['SERVER_NAME'].':'.$_SERVER['SERVER_PORT']. $_SERVER ['REQUEST_URI'] );
} else {
    define ( 'REQUEST_URL', $protocol . $_SERVER ['SERVER_NAME'] . $_SERVER ['REQUEST_URI'] );
}
define ( 'COMMAND_URL', str_replace ( APP_WEB_ROOT, '', REQUEST_URL ) );
$commandSplit = explode ( '?', COMMAND_URL );


// LOAD CONFIG FILE
$config = parse_ini_file(APP_ROOT."/config.ini",true);

// =======================================================================//
// IDENTIFY REQUESTED RESOURCE NAME & LOCATION
// =======================================================================//

$RESOURCE_NAME = preg_replace ( '/^\/|\/$/', '', $commandSplit [0] );
$RESOURCE_PATH = APP_ROOT . '/' . $RESOURCE_NAME;
$RESOURCE_WEB_PATH = APP_WEB_ROOT . '/' . $RESOURCE_NAME;

$fileType = getFileType ( $RESOURCE_PATH );
if (is_file ( $RESOURCE_PATH )) {
	if ($fileType == 'php') {
		include $RESOURCE_PATH;
	} else {
		header ( 'content-type: ' . getMimeType ( $fileType ) );
		header ( 'content-length: ' . filesize( $RESOURCE_PATH ) );
		echo file_get_contents ( $RESOURCE_PATH );
	}
	exit ();
}

// =======================================================================//
// INTERPRET THE REQUEST
// =======================================================================//

$RESOURCE_PATH .= ".php";
if (!is_file($RESOURCE_PATH))
	SHOW_ERROR(404,"The requested resource cannot be found");

require_once $RESOURCE_PATH;

exit ();

// ====================================================================//
// UTILITY FUNCTIONS
// ====================================================================//

function getMimeType($extension) {
	$mime_types = array ("mp4"=>"video/mp4","csv"=>"text/csv", "pdf" => "application/pdf", "exe" => "application/octet-stream", "zip" => "application/zip", "docx" => "application/msword", "doc" => "application/msword", "xls" => "application/vnd.ms-excel", "ppt" => "application/vnd.ms-powerpoint", "gif" => "image/gif", "png" => "image/png", "jpeg" => "image/jpg", "jpg" => "image/jpg", "mp3" => "audio/mpeg", "wav" => "audio/x-wav", "mpeg" => "video/mpeg", "mpg" => "video/mpeg", "mpe" => "video/mpeg", "mov" => "video/quicktime", "avi" => "video/x-msvideo", "3gp" => "video/3gpp", "css" => "text/css", "jsc" => "application/javascript", "js" => "application/javascript", "php" => "text/html", "htm" => "text/html", "html" => "text/html", "swf" => "application/x-shockwave-flash", "xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" );
	return $mime_types [$extension];
}

function getFileType($filename) {
	return strtolower ( pathinfo ( $filename, PATHINFO_EXTENSION ) );
}

function SHOW_ERROR($code, $message, $detail = "") {
	
	http_response_code($code);

	echo "<h1>Error: ".$code."</h1>";
	echo "<h3>".$message."</h3>";
	echo "<p>".$detail."</p>";
	
	ob_flush ();
	flush ();
	
	exit;
}

// Create log file in temp folder withing the project folder
function loge($varient) {
	$type = gettype($varient);

	$str = "";
	if ($type == 'array')
		$str = json_encode($varient);
	else if ($type == 'string' || $type == 'integer' || $type =='double') 
		$str = $varient;
	else if ($type == 'object') {
		$str = json_encode($varient);
	}

	$str = date("D M d, Y G:i:s")." [".debug_backtrace()[1]['function']."]".PHP_EOL.$str . PHP_EOL .PHP_EOL;
	file_put_contents(APP_ROOT.'/temp/Log_'.date("d-M-Y").'.txt', $str, FILE_APPEND);
}

function sendHTTPCacheHeaders($cache_file_name, $check_request = false)
  {
    $mtime = @filemtime($cache_file_name);

    if($mtime > 0)
    {
      $gmt_mtime = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
      $etag = sprintf('%08x-%08x', crc32($cache_file_name), $mtime);

      header('ETag: "' . $etag . '"');
      header('Last-Modified: ' . $gmt_mtime);
      header('Cache-Control: private');
      // we don't send an "Expires:" header to make clients/browsers use if-modified-since and/or if-none-match

      if($check_request)
      {
        if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && !empty($_SERVER['HTTP_IF_NONE_MATCH']))
        {
          $tmp = explode(';', $_SERVER['HTTP_IF_NONE_MATCH']); // IE fix!
          if(!empty($tmp[0]) && strtotime($tmp[0]) == strtotime($gmt_mtime))
          {
            header('HTTP/1.1 304 Not Modified');
            return false;
          }
        }

        if(isset($_SERVER['HTTP_IF_NONE_MATCH']))
        {
          if(str_replace(array('\"', '"'), '', $_SERVER['HTTP_IF_NONE_MATCH']) == $etag)
          {
            header('HTTP/1.1 304 Not Modified');
            return false;
          }
        }
      }
    }

    return true;
  }

function clean_temp_folder() {
	// Delete all files less than or equal to 1 days old
	$files = array();
	scanDirectories(APP_TEMP_ROOT,$files);

	$today_date = date_create_from_format("d-M-Y",date('d-M-Y'));
	foreach ($files as $file) {
		
		$file_date = date_create_from_format("d-M-Y",date("d-M-Y", filemtime($file)));
		$interval = date_diff($today_date, $file_date);
		$days = intval($interval->format('%a'));
		if ($days > 1) {
			unlink($file);
		}
	}

	loge($files);
}
