<?php

/*
	Author 	: Asim Ishaq
*/

require_once APP_ROOT."/Util.php";
require_once APP_ROOT."/ServiceBase.php";

$OUTPUT_CONTENT_TYPE = "application/json";
function set_output_content_type($type) {
	global $OUTPUT_CONTENT_TYPE;
	$OUTPUT_CONTENT_TYPE = $type;
}

// ----------------------------------------------------------------------------------------------------- //
// -------------------------------------CLEAN TEMP FOLDER----------------------------------------------- //
// ----------------------------------------------------------------------------------------------------- //

// ## There is a 10% probability that cleanup will be called on each Service call
if (rand(0,100) <= 10) clean_temp_folder();

// ----------------------------------------------------------------------------------------------------- //
// --------------------------------API & METHOD INFORMATION--------------------------------------------- //
// ----------------------------------------------------------------------------------------------------- //
loge('======> POST ');
loge($_POST);
loge('<====== POST ');

loge('======> GET');
loge($_GET);
loge('<====== GET');

$api = "";
$method = "";
$arguments = "";

// API & METHOD info
if (isset($_GET['a'])) {
	$x = explode(":",$_GET['a']);
	$api = $x[0];
	$method = $x[1];
} else if (isset($_POST['a'])) {
	$x = explode(":",$_POST['a']);
	$api = $x[0];
	$method = $x[1];
}

// Parameters info
if (isset($_GET['p'])) {
	$arguments = $_GET['p'];
} else if (isset($_POST['p'])) {
	$arguments = $_POST['p'];
}

if ($api == "") {
	echo json_encode(array("status"=>false,"msg"=>"Specify valid Api & Method info in argument 'a' in either GET or POST request")); 
	exit;
}

if ($method == "") {
	echo json_encode(array("status"=>false,"msg"=>"Specify valid Api & Method info in argument 'a' in either GET or POST request")); 
	exit;
}

if (strlen($arguments) > 0) {
	$arguments = json_decode($arguments,true);
	if ($arguments === FALSE) {
		echo json_encode(array("status"=>false,"msg"=>"Invalid argument format. Specify valid JSON formatted parameters in argument 'p' in either GET or POST request")); 
		exit;		
	}	
}

$class_file = APP_ROOT."/".$api.".php";

if (!is_file($class_file)) {
	echo json_encode(array("status"=>false,"msg"=>"API class <$api> not found")); 
	exit;
}
	
// ----------------------------------------------------------------------------------------------------- //
// --------------------------------------INSTANTIATE SERVICE-------------------------------------------- //
// ----------------------------------------------------------------------------------------------------- //

require_once $class_file;

$rClass = new ReflectionClass($api);
$obj = $rClass->newInstanceArgs();

//Check Requested method exists
if (!method_exists ($obj,$method)) {
	echo json_encode(array("status"=>false,"msg"=>"$method in $api does not exist")); 
	exit;	
}

if (!is_array($arguments))
	$arguments = array($arguments);

// ----------------------------------------------------------------------------------------------------- //
// --------------------------------------METHOD CALL --------------------------------------------------- //
// ----------------------------------------------------------------------------------------------------- //

// Continue to Run process even if Client Call Disconnects
ignore_user_abort(true);

// >> Debugging:
$call_id = uniqid("CALL_");
loge(REQUEST_URL."?a=$api:$method&p=".json_encode($arguments));
loge("$call_id -----------------> [$api][$method][".json_encode($arguments)."]");

$start_time = microtime(true);
$response = call_user_func_array(array($obj, $method), $arguments);

if (is_array($response) || is_object($response)) {
	$response = json_encode($response);
}

$final_response = array("status"=>true,"data"=>$response);
$final_response = json_encode($final_response);

$end_time = microtime(true);
$seconds_consumed = $end_time-$start_time;

header("content-type: $OUTPUT_CONTENT_TYPE");
header("response-time: <".round($seconds_consumed,4)."> seconds");

echo $final_response;

ob_flush();
flush();

loge("$call_id <----------------- Response sent: ".strlen($response)." bytes");
loge($response);