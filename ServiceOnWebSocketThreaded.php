<?php

/*
	Author 	: Asim Ishaq
	Created on: 26.Sep.2019
*/

error_reporting ( E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_WARNING ^ E_ERROR ^ E_STRICT );
// error_reporting (E_ALL); ini_set('display_errors', 1);

define ( 'APP_ROOT', str_replace ( '\\', '/', getcwd () ) );
define ( 'APP_TEMP_ROOT', APP_ROOT.'/temp' );

require_once APP_ROOT.'/Util.php';
require_once APP_ROOT.'/ServiceBase.php';
require_once APP_ROOT.'/WebSocketRequestHandler.php';

// LOAD CONFIG FILE
$config = parse_ini_file(APP_ROOT."/config.ini",true);

date_default_timezone_set($config['global']['timezone']);

// ----------------------------------------------------------------------------------------------------- //
// --------------------------------------LISTENER------------------------------------------------------- //
// ----------------------------------------------------------------------------------------------------- //

$MAIN_SOCKET = stream_socket_server("tcp://".$config['websocket']['listen_ip'].":".$config['websocket']['listen_port'], $error_no, $error_msg);
if (!$MAIN_SOCKET) {
	echo "Can't connect socket: [{$error_no}] {$error_msg}";
	exit(0);
}

stream_set_blocking($MAIN_SOCKET, 0);

echo 'Listening on: '.$config['websocket']['listen_ip'].':'.$config['websocket']['listen_port'].PHP_EOL;

$HANDLERS = array();

// Keep listening 
while (True) {
    $new_socket = stream_socket_accept($MAIN_SOCKET);
    if ($new_socket !== FALSE)
    	$HANDLERS[] = new WebSocketRequestHandler($new_socket,$config);

    foreach ($HANDLERS as $key=>$HANDLER) {
    	if ($HANDLER->isJoined() OR $HANDLER->isTerminated() ) {
    		echo 'Thread Terminated: '.$HANDLER->getThreadId().PHP_EOL;
		
		// Taking all possible measures to unload terminated thread data from list
    		foreach ($HANDLERS[$key] as $idx => $value) {
            		unset($HANDLERS[$idx]->$key);
        	}

    		$HANDLERS[$key] = null;
    		unset($HANDLERS[$key]);
    	}
    }
    
    // Below thread count may not be accurate 
    echo "Thread Count: ".sizeof($HANDLERS).PHP_EOL;
}
