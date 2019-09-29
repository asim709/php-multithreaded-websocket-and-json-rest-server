<?php

class Util {

	// #https://gist.github.com/joashp/a1ae9cb30fa533f4ad94
	public static function encrypt_decrypt($action, $key, $string) {
	    $output = false;
	    $encrypt_method = "AES-256-CBC";
	    $secret_key = $key;
	    $secret_iv = 'SDAD@!$$Ee;lewiuoo328923@EW$###3__#_';
	    // hash
	    $key = hash('sha256', $secret_key);
	    
	    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
	    $iv = substr(hash('sha256', $secret_iv), 0, 16);
	    if ( $action == 'encrypt' ) {
	        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
	        $output = base64_encode($output);
	    } else if( $action == 'decrypt' ) {
	        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	    }
	    return $output;
	}

	public static function Encrypt($key,$data) {
		return self::encrypt_decrypt('encrypt',$key,$data);
	}

	public static function Decrypt($key,$data) {
		return self::encrypt_decrypt('decrypt',$key,$data);	
	}

	//Encrypt using ACCESS_TOKEN
	public static function Encrypt_Using_Access_Token($data) {
		return self::encrypt_decrypt('encrypt',ACCESS_TOKEN,$data);
	}

	public static function Decrypt_Using_Access_Token($data) {
		return self::encrypt_decrypt('decrypt',ACCESS_TOKEN,$data);
	}

	//Encrypt using ACCESS_TOKEN
	public static function Encrypt_Using_Session_Key($data) {
		global $SESSION;
		return self::encrypt_decrypt('encrypt',$SESSION['encryption_key'],$data);
	}

	public static function Decrypt_Using_Session_Key($data) {
		global $SESSION;
		return self::encrypt_decrypt('decrypt',$SESSION['encryption_key'],$data);
	}


	/*
		When detail = true then One hash index will contain an array of objects
		If value_columns is not an array and it is  just a string then hash indexex will have values of this column directly
			if null then include all columns
	*/
	public static function create_hash_array (	$data = array(), 
												$hash_columns = array(), 
												$value_columns = null/*null meaans all columns in dataset*/, 
												$detail = false /*true means values are an array of objects ortherwise just one object*/) {
		$hash_array = array();

		switch ($detail) {
			case true:
				foreach ($data as &$row) {
					// calculate hash value
					$hash_value = "";
					foreach ($hash_columns as $a_col_name)
						$hash_value .= $row[$a_col_name];

					// If detail is not already created
					if (!isset($hash_array[$hash_value]))
						$hash_array[$hash_value] = array();

					if ($value_columns === NULL)
						$hash_array[$hash_value][] = $row;
					else if (!is_array($value_columns)) 
						$hash_array[$hash_value][] = $row[$value_columns]; 
					else  {
						// If array then 
						$t = array();
						foreach ($value_columns as $vcol) {
							$t[$vcol] = $row[$vcol];
						}
						$hash_array[$hash_value][] = $t;
					} 
				}
			break;

			case false:
				foreach ($data as &$row) {
					// calculate hash value
					$hash_value = "";
					foreach ($hash_columns as $a_col_name)
						$hash_value .= $row[$a_col_name];

					if ($value_columns === NULL)
						$hash_array[$hash_value] = $row;
					else if (!is_array($value_columns)) 
						$hash_array[$hash_value] = $row[$value_columns]; 
					else  {
						// If array then 
						$t = array();
						foreach ($value_columns as $vcol) {
							$t[$vcol] = $row[$vcol];
						}
						$hash_array[$hash_value] = $t;
					}
				}
			break;
		}	

		return $hash_array;
	}

	// #http://php.net/manual/en/function.array-multisort.php#100534
	public static function array_orderby() {
	    $args = func_get_args();
	    $data = array_shift($args);
	    foreach ($args as $n => $field) {
	        if (is_string($field)) {
	            $tmp = array();
	            foreach ($data as $key => $row)
	                $tmp[$key] = $row[$field];
	            $args[$n] = $tmp;
	        }
	    }
	    $args[] = &$data;
	    call_user_func_array('array_multisort', $args);
	    return array_pop($args);
	}

	public static function oci_escape_string($string) {
  		return str_replace(array('"', "'", '\\'), array('\\"', '\\\'', '\\\\'), $string);
	}

	// Also send encrypted keys
	// $token is only required when $show_api_urls = 1
	public static function get_api_method_list($encr_key,$show_api_urls = 0,$token = null) {
		global $API_LIST;
		$arr = array();

		foreach ($API_LIST as $row) {
			$key = self::Encrypt($encr_key,$row[0]."::".$row[1]);
			$item = array("api"=>$row[0],"method"=>$row[1],"key"=>$key);
			if ($show_api_urls == 1) {
				$item['url'] = APP_WEB_ROOT.'/service?A='.$key.'&APPSESSIONID='.$token.'&ARGS=[]';
			}
			$arr[] = $item;
		}
		return $arr;
	}

	public static function array_msort($array, $cols) {
	    $colarr = array();
	    foreach ($cols as $col => $order) {
	        $colarr[$col] = array();
	        foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
	    }
	    $eval = 'array_multisort(';
	    foreach ($cols as $col => $order) {
	        $eval .= '$colarr[\''.$col.'\'],'.$order.',';
	    }
	    $eval = substr($eval,0,-1).');';
	    eval($eval);
	    $ret = array();
	    foreach ($colarr as $col => $arr) {
	        foreach ($arr as $k => $v) {
	            $k = substr($k,1);
	            if (!isset($ret[$k])) $ret[$k] = $array[$k];
	            $ret[$k][$col] = $array[$k][$col];
	        }
	    }
	    return $ret;
	}

	public static function is_multi_dim_array($a) {
		$rv = array_filter($a,'is_array');
		if(count($rv)>0) return true;
			return false;
	}

	// from two dim assoc array
	public static function array_delete_col(&$array, $key) {
		return array_walk($array, function (&$v) use ($key) {
			unset($v[$key]);
		});
	}
}

//Proxy functions for easy access
function _E($d) {
	return Util::Encrypt_Using_Session_Key($d);
}

function _D($d) {
	return Util::Decrypt_Using_Session_Key($d);
}

function _E2($d,$k) {
	return Util::Encrypt($k,$d);
}

function _D2($d,$k) {
	return Util::Decrypt($k,$d);
}

 function getRealIpAddr() {
	return '::';
 }
 /*
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
}*/


// # https://github.com/bloatless/php-websocket/blob/master/src/Connection.php#L534
 function decode_ws_data($data) {
		$unmaskedPayload = '';
        $decodedData = [];
        // estimate frame type:
        $firstByteBinary = sprintf('%08b', ord($data[0]));
        $secondByteBinary = sprintf('%08b', ord($data[1]));
        $opcode = bindec(substr($firstByteBinary, 4, 4));
        $isMasked = ($secondByteBinary[0] == '1') ? true : false;
        $payloadLength = ord($data[1]) & 127;
        // close connection if unmasked frame is received:
        if ($isMasked === false) {
            return array();
        }

        switch ($opcode) {
            // text frame:
            case 1:
                $decodedData['type'] = 'text';
                break;
            case 2:
                $decodedData['type'] = 'binary';
                break;
            // connection close frame:
            case 8:
                $decodedData['type'] = 'close';
                break;
            // ping frame:
            case 9:
                $decodedData['type'] = 'ping';
                break;
            // pong frame:
            case 10:
                $decodedData['type'] = 'pong';
                break;
            default:
                // Close connection on unknown opcode:
                $this->close(1003);
                break;
        }
        if ($payloadLength === 126) {
            $mask = substr($data, 4, 4);
            $payloadOffset = 8;
            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
        } elseif ($payloadLength === 127) {
            $mask = substr($data, 10, 4);
            $payloadOffset = 14;
            $tmp = '';
            for ($i = 0; $i < 8; $i++) {
                $tmp .= sprintf('%08b', ord($data[$i + 2]));
            }
            $dataLength = bindec($tmp) + $payloadOffset;
            unset($tmp);
        } else {
            $mask = substr($data, 2, 4);
            $payloadOffset = 6;
            $dataLength = $payloadLength + $payloadOffset;
        }
        /**
         * We have to check for large frames here. socket_recv cuts at 1024 bytes
         * so if websocket-frame is > 1024 bytes we have to wait until whole
         * data is transferd.
         */
        //if (strlen($data) < $dataLength) {
        //    return [];
       // }

        if ($isMasked === true) {
            for ($i = $payloadOffset; $i < $dataLength; $i++) {
                $j = $i - $payloadOffset;
                if (isset($data[$i])) {
                    $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
                }
            }
            $decodedData['payload'] = $unmaskedPayload;
        } else {
            $payloadOffset = $payloadOffset - 4;
            $decodedData['payload'] = substr($data, $payloadOffset);
        }
        return $decodedData;
	}

	// # https://gist.github.com/dg/6205452
	function encode_ws_data(string $payload, $type = 'text', $masked = true)
    {
        $frameHead = [];
        $payloadLength = strlen($payload);
        switch ($type) {
            case 'text':
                // first byte indicates FIN, Text-Frame (10000001):
                $frameHead[0] = 129;
                break;
            case 'close':
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = 136;
                break;
            case 'ping':
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = 137;
                break;
            case 'pong':
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = 138;
                break;
        }
        // set mask and payload length (using 1, 3 or 9 bytes)
        if ($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for ($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }
            // most significant bit MUST be 0 (close connection if frame too big)
            if ($frameHead[2] > 127) {
                $this->close(1004);
                throw new \RuntimeException('Invalid payload. Could not encode frame.');
            }
        } elseif ($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }
        // convert frame-head to string:
        foreach (array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        if ($masked === true) {
            // generate a random mask:
            $mask = [];
            for ($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }
            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);
        // append payload to frame:
        for ($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }
        return $frame;
    }


    // Terminate data with CR LF
	function write_on_socket($socket,$data) {
		$str = "";
		if (is_object($data) OR is_array($data))
			$str = json_encode($data);
		else
			$str = $data;

		$str .= PHP_EOL;

		fwrite($socket,$str);
	}


	function write_on_websocket($socket,$data) {
		$str = "";
		if (is_object($data) OR is_array($data))
			$str = json_encode($data);
		else
			$str = $data;

		$str = encode_ws_data($str,'text',false);
		
		fwrite($socket,$str);
	}

