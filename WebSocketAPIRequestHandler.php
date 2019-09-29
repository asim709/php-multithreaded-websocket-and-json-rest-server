<?php
/*
	Author 	: Asim Ishaq
	Created on: 28.Sep.2019

	https://tools.ietf.org/html/rfc6455
*/

class WebSocketAPIRequestHandler extends Thread {

	protected $_socket;
	protected $_config;
	protected $_request_data;

	public function __construct($socket,$config,$request_data) {
		$this->_socket = $socket;
		$this->_config = $config;
		$this->_request_data = $request_data;
		$this->start();
	}

	public function run() {
		
		// Re-send this _id in response so that the client 
		// can identify which response is for which request
		$_id = $this->_extract_request_id_if_any($this->_request_data);

		echo $this->getThreadId().'> Request received: '.$this->_request_data.PHP_EOL;

		$request_obj = json_decode($this->_request_data,true);
		if ($request_obj === FALSE) {
			write_on_websocket($this->_socket,$this->_prepare_reponse(false,"Please provide API request info in JSON format",$_id));
			return 0;
		}

		$api_method = $request_obj['a'];
		$arguments = "";
		if (isset($request_obj['p']))
			$arguments = $request_obj['p'];
		 
		$api ="";
		$method = "";
		
		$arr = explode(":",$api_method);
		if (sizeof($arr) != 2) {
			write_on_websocket($this->_socket,$this->_prepare_reponse(false,"Invalid api/method information",$_id));
			return 0;
		}

		$api = $arr[0];
		$method = $arr[1];
		 				
		$class_file = APP_ROOT."/".$api.".php";
		if (!is_file($class_file)) {
			write_on_websocket($this->_socket,$this->_prepare_reponse(false,"$api Class File not found",$_id));
			return 0;
		}

		/*
		if (strlen($arguments) > 0) {
		 	$arguments = json_encode($arguments,true);
		 	if ($arguments === FALSE) {
		 		write_on_websocket($this->_socket,$this->_prepare_reponse(false,"Invalid arguments format",$_id));
				return 0;
		 	}
		}*/

		// ----------------------------------------------------------------------------------------------------- //
		// --------------------------------------INSTANTIATE SERVICE-------------------------------------------- //
		// ----------------------------------------------------------------------------------------------------- //

		require_once $class_file;

		$rClass = new ReflectionClass($api);
		$obj = $rClass->newInstanceArgs();

		//Check Requested method exists
		if (!method_exists ($obj,$method)) {
			write_on_websocket($this->_socket,$this->_prepare_reponse(false,"$method in $api does not exist",$_id));
			return 0;
		}

		if (!is_array($arguments))
			$arguments = array($arguments);
		
		echo $this->getThreadId().'> Serving: '. $api . ' > ' .$method.PHP_EOL;

		$api_call_response = call_user_func_array(array($obj, $method), $arguments);
		$final_response = $this->_prepare_reponse(true,$api_call_response,$_id);
		$final_response = json_encode($final_response);

		write_on_websocket($this->_socket,$final_response);
	}

	protected function _prepare_reponse($status,$data_or_msg,$_id = null) {
		
		// Always send text data in response
		if (is_object($data_or_msg) || is_array($data_or_msg)) {
			$data_or_msg = json_encode($data_or_msg);
		}

		if ($_id !== NULL) {
			if ($status) {
				return array("status"=>true,"data"=>$data_or_msg,"_id"=>$_id);
			} else {
				return array("status"=>true,"msg"=>$data_or_msg,"_id"=>$_id);
			}
		}

		// Else
		if ($status) {
			return array("status"=>true,"data"=>$data_or_msg);
		} else {
			return array("status"=>true,"msg"=>$data_or_msg);
		}
	}

	protected function _extract_request_id_if_any ($request_text) {
		$o = json_decode($request_text,true);
		if ($o === FALSE) {
			return null;
		}

		if (isset($o['_id']))
			return $o['_id'];

		return null;
	}
}