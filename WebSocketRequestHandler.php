<?php
/*
	Author 	: Asim Ishaq
	Created on: 28.Sep.2019

	https://tools.ietf.org/html/rfc6455
*/

require_once APP_ROOT.'/WebSocketAPIRequestHandler.php';

class WebSocketRequestHandler extends Thread {

	protected $_socket;
	protected $_config;

	public function __construct($socket,$config) {
		$this->_socket = $socket;
		$this->_config = $config;
		$this->start();
	}

	public function run() {
		
		$MAGIC = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

		echo 'New web socket thread is created. Thread Id <'.$this->getThreadId().'>'.PHP_EOL;

		while (true) {
			
			// Check if a handshake is requested
			$data = stream_socket_recvfrom($this->_socket, 3, STREAM_PEEK);
			if (strlen($data) == 0) {
				usleep(200);
				if (feof($this->_socket)) {
					echo $this->getThreadId().'> Connection closed by client'.PHP_EOL;
					break;
				}
			 	continue;
			}

			if ($data == 'GET') {

	     		/*Handshake Request*/
	     		$data = stream_get_line($this->_socket,65535,"\r\n\r\n");

	     		// Terminate if connection closes OR wait more while data is received
				if (strlen($data) == 0) {
					usleep(200);
					if (feof($this->_socket)) {
						echo $this->getThreadId().'> Connection closed by client'.PHP_EOL;
						break;
					}
				 	continue;
				}

				// WebSocket key is necessary for handshake, if not provided by client then terminate.
				if (!preg_match('#^Sec-WebSocket-Key: (\S+)#mi', $data, $matches)) {
					break;
				}

				echo $data.PHP_EOL.PHP_EOL;
				
				/*Handshake Response*/
				$response_text = "HTTP/1.1 101 Switching Protocols\r\n"
				. "Upgrade: websocket\r\n"
				. "Connection: Upgrade\r\n"
				. "Sec-WebSocket-Accept: " . base64_encode(sha1($matches[1] . $MAGIC, TRUE))
				. "\r\n\r\n";

				echo $response_text.PHP_EOL.PHP_EOL;

				fwrite($this->_socket, $response_text);
			}
			// Loop for bi-directional communication
			for(;;) {
				
				// Know when to read data
				$sockets_available_for_read = array($this->_socket); $x = null; $y=null;
				stream_select($sockets_available_for_read, $x, $y, 0,200000);
			    
			    // Assuming that large messages are distributed into 32K chuncks 
				// So 64K buffer will be sufficient for one time full packet receive
				$data = fread ($this->_socket,65535);
				if (feof($this->_socket)) {
					echo $this->getThreadId().'> Connection closed by client after handshake'.PHP_EOL;
					return 0;
				}

				if ($data === FALSE OR strlen($data) == 0) {
					usleep(200);
					continue;
				}

				$decoded_data = decode_ws_data($data);
				
				if (!is_array($decoded_data)) {
					echo $this->getThreadId().'> Unable to decode request data'.PHP_EOL;
					return 0;
				}

				switch ($decoded_data['type']) {
					case 'text':
						// Spawn a new thread to send response for this request
						$th = new WebSocketAPIRequestHandler($this->_socket, $this->_config, $decoded_data['payload']);
					break;

					case 'ping':
						$response = encode_ws_data($decoded_data['payload'], 'pong', false);
						fwrite($this->_socket, $response);
					break;
				}
			}


		}

		exit(0);
	}
}