<?php
/**
 *
 * (c) var_null 
 *
 */

//namespace Longman\TelegramBot;

//define('BASE_PATH', __DIR__);


class Encode
{
	protected $SIGNATURE_KEY;
	protected $uniq_id;
	protected $method = "GET";
	protected $args = array();
	protected $public_key_from_args_from_b;
	protected $packet_key_from_args_from_b;
	protected $sinc_key_a;

    public function __construct($BOT_API_KEY)
    {
		$this->setSIGNATURE_KEY($BOT_API_KEY);//Устанавливаем сигнатуру. Точно такая же должна установиться на другой машине
    } 
	
	public function setSincKeyA($sinc_key_a)
	{		
		$this->sinc_key_a = $sinc_key_a;
	}		
	
	public function getSincKeyA()
	{		
		return $this->sinc_key_a;
	}	
	
	public function getPublicKeyFromArgsFromB()
	{
		
		return $this->public_key_from_args_from_b;
	}
	
	public function getPacketKeyFromArgsFromB()
	{
		
		return $this->packet_key_from_args_from_b;
	}
	
	public function setMethod($method)
	{		
		$this->method = $method;
	}		
	
	public function getMethod()
	{		
		return $this->method;
	}	
		
	
	public function parseArgs($get, $post)
	{
		$this->setMethod("GET");
		
		if($post['method'] == "POST")
			$this->setMethod("POST");
		
		$method = $this->getMethod();		
		
		$_mas = $get;
		
		if($method == "POST")
			$_mas = $post;
		
		$public_key = urldecode($_mas['public_key']);
		
		$public_key = str_replace("@@p@@", "+", $public_key);
		$public_key = str_replace("@@pr@@", " ", $public_key);	
		
		$this->public_key_from_args_from_b = $public_key;
		$this->packet_key_from_args_from_b = $_mas['packet_key'];
		$this->args = json_decode(urldecode($_mas['params']), true);		
		
		
	
	}

	public function parseArgsFromB($get, $post)
	{
		$this->parseArgs($get, $post);
		
		return $this->args;
	
	}	
	
	public function setSIGNATURE_KEY($SIGNATURE_KEY)
	{
		$this->SIGNATURE_KEY = md5($SIGNATURE_KEY);
	
	}

	public function getSIGNATURE_KEY()
	{
		return $this->SIGNATURE_KEY;
	
	}
	
	public function getConnectionId()
	{
		return $this->uniq_id;
	
	}		
	
	public function genConnectionId()
	{
		$this->uniq_id = uniqid();
		return $this->uniq_id;
	
	}	
	
	public function echoEncodeDataToB($notice)
	{
		//Получим шашифрованные публичным ключем от машины B данные, в которых синхронный ключ и кусок для доп проверки
		$data_with_encode_sinc = $this->genSincKeyA();
		
		//Теперь шифруем синхронным ключем основные данные для шифрования
		
		$sinc_key_a = $this->getSincKeyA();
		
		$encrypted_data = urlencode(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $sinc_key_a, $notice, MCRYPT_MODE_ECB)));
		
		return $data_with_encode_sinc . "@@@" . $encrypted_data;	
		
	}
	
	
	public function genSincKey()
	{	
		$sinc_key = md5(uniqid() . '_' . time() . '_' . uniqid()); //Этим ключем уже будем шифровать большой объем
			
		return $sinc_key;
	}
	
	public function genSincKeyA()
	{
		//Машина а, получив публичный ключ от машины B может зашифровать им синхронный ключ, который машина сможет расшифровать своим приватным ключем
		
		$SIGNATURE_KEY = $this->getSIGNATURE_KEY();
		$public_key_from_args_from_b = $this->getPublicKeyFromArgsFromB();
		$packet_key_from_args_from_b = $this->getPacketKeyFromArgsFromB();
		
		$digital_signature = md5($SIGNATURE_KEY . '_' . date('Y') . '_' . $packet_key_from_args_from_b);//Эти данные машина B сможет воссоздать для доп проверки
				
		$pub = <<<SOMEDATA777
$public_key_from_args_from_b
SOMEDATA777;
		
		$sinc_key = $this->genSincKey(); //Этим ключем уже будем шифровать большой объем
		
		$this->setSincKeyA($sinc_key);
		
		$data = $digital_signature . "@@@" . $sinc_key;

		
		//echo 'SIGNATURE_KEY ' . $SIGNATURE_KEY . '<br>';
		//echo 'public_key_from_args_from_b ' . $public_key_from_args_from_b . '<br>';
		//echo 'packet_key_from_args_from_b ' . $packet_key_from_args_from_b . '<br>';
		//echo 'data ' . $data . '<br>';
		
		$pk  = openssl_get_publickey($pub);
		openssl_public_encrypt($data, $encrypted, $pk);
		$data_key_crypt = chunk_split(base64_encode($encrypted));		

		return $data_key_crypt;
		
		//echo 'data_key_crypt=' . $data_key_crypt . '<br>';
		
		/*
		$data_key_crypt = str_replace("+", "@@p@@", $data_key_crypt);
		$data_key_crypt = str_replace(" ", "@@pr@@", $data_key_crypt);
		
		$key_crypt_urlencode = urlencode($data_key_crypt);
		
		return $key_crypt_urlencode;
		*/
		
	}	
	
	public function genPublicKey()
	{
		//Машана B генерирует публичный ключ чтбы машина A могла отослать ей данные
		//Только с помошью второй пары машина B (мы) сможем расшифровать данные (а получим мы зашифрованным синхронный ключ)
		
		$uniq_id = $this->getConnectionId();
		
		$bait = 1024;
		
		$com = "openssl genrsa -out keys/private_" . $uniq_id . ".pem " . $bait;		
		$res = exec( $com, $output);

		$com = "openssl rsa -in keys/private_" . $uniq_id . ".pem -out keys/public_" . $uniq_id . ".pem -outform PEM -pubout";		
		$res = exec( $com, $output);	

		$f = fopen("keys/public_" . $uniq_id . ".pem", "r");

		$public_key = "";
		
		// Читать построчно до конца файла
		while(!feof($f)) { 
			$public_key .= fgets($f);
		}

		fclose($f);	
		
		
		return $public_key;		
		
		
	}
	
	public function parseDataWithSincFromA($data_mas)
	{
		$data_with_sinc = $data_mas[0];
		$encrypted_data = $data_mas[1];
		
		$SIGNATURE_KEY = $this->getSIGNATURE_KEY();		
		$uniq_id = $this->getConnectionId();
		
		$digital_signature = md5($SIGNATURE_KEY . '_' . date('Y') . '_' . $uniq_id);	
		
		$f = fopen("keys/private_" . $uniq_id . ".pem", "r");
		
		$private_key = "";
		
		// Читать построчно до конца файла
		while(!feof($f)) { 
			$private_key .= fgets($f);
		}

		fclose($f);	
		
		$key = <<<SOMEDATA777
$private_key
SOMEDATA777;

		$pk  = openssl_get_privatekey($key);
		openssl_private_decrypt(base64_decode($data_with_sinc), $out, $pk);	
		
		
		$out_mas = explode('@@@', $out);
		
		$signature_correct_str = $out_mas[0];
		$sinc_key = $out_mas[1];	
			
		
		if($signature_correct_str == $digital_signature)
		{
			
			//Синхронный ключ корректен ибо мы извлекли нашу хитрую подпись

			
			/* decode */
			$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256,$sinc_key, base64_decode(urldecode($encrypted_data)),MCRYPT_MODE_ECB);

			
			unlink("keys/private_" . $uniq_id . ".pem");
			unlink("keys/public_" . $uniq_id . ".pem");
			

			return $decrypted;
			
			
		}
		else
		{
			return 'ERROR: digital_signature_incorrect';
		}		
			
		
	}
	
	public function reqDataSincFromA($data_mas)
	{
		$path_a = $data_mas['path_a'];
		$method = $data_mas['method'];
		$packet_key = $data_mas['packet_key'];
		
		$public_key = str_replace("+", "@@p@@", $data_mas['public_key']);
		$public_key = str_replace(" ", "@@pr@@", $public_key);		
		
		
		$params = $data_mas['params'];
		
		
		if($method == "GET")
		{
			$public_key = urlencode($public_key);
			$params_str = urlencode(json_encode($params));
			
			$resp = file_get_contents($path_a . "?method=" . $method . "&packet_key=" . $packet_key . "&public_key=" . $public_key . "&params=" . $params_str);
			
		}			
		else
		{
			$params_str = json_encode($params);
			
			$postvars_mas = array();
			$postvars_mas['method'] = $method;
			$postvars_mas['packet_key'] = $packet_key;
			$postvars_mas['public_key'] = $public_key;
			$postvars_mas['params'] = $params_str;
						
			$ch = curl_init();	
			curl_setopt($ch, CURLOPT_URL, $path_a);
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars_mas);
			$resp = curl_exec($ch);	
			curl_close($ch);	

			
	
		}
			
		$resp_mas = explode('@@@' , $resp);	
			
		return $this->parseDataWithSincFromA($resp_mas);//Разбираем ответ с машины A. В этом ответе синхронный ключ
	}
	

	
	
	public function getDataFromA($data_mas)
	{
		//Запросим удаленный php на получение данных
		
		$packet_key = $this->genConnectionId();//Ключ для текущего экземпляра коннекции
		$public_key = $this->genPublicKey();//Машана B генерирует публичный ключ чтбы машина A могла отослать ей данные	

		$data_mas['packet_key'] = $packet_key;//Уникальный ключ коннекции
		$data_mas['public_key'] = $public_key;//Отсылаем публичный ключ		
		
		return $this->reqDataSincFromA($data_mas);
		
	}	
	
	public function parseDataFromA($get, $post)
	{	
		$return_key_error = md5(time() + 2);
		
		$return_data = array();
	
		$this->setMethod("GET");
		
		if($post['method'] == "POST")
			$this->setMethod("POST");
		
		$method = $this->getMethod();		
		
		
		$_mas = $get;
		
		if($method == "POST")
			$_mas = $post;	
		
		//Извлекаем данные:
		
		$encrypted_data_urlencode = $_mas['encrypted_data_urlencode'];
		
		$encrypted_data_urlencode = str_replace("@@p@@", "+", $encrypted_data_urlencode);
		$encrypted_data_urlencode = str_replace("@@pr@@", " ", $encrypted_data_urlencode);	

		//+++
		
		$data_urldecode = urldecode($_mas['key_crypt_urlencode']);
		
		$data_urldecode = str_replace("@@p@@", "+", $data_urldecode);
		$data_urldecode = str_replace("@@pr@@", " ", $data_urldecode);	
		
		$get_digital_signature = $_mas['digital_signature'];
		$packet_key = $_mas['packet_key'];	

		$return_data['params'] = json_decode(urldecode($_mas['params']), true);
		
		
		$SIGNATURE_KEY = $this->getSIGNATURE_KEY();
		
		$digital_signature = md5($SIGNATURE_KEY . '_' . date('Y') . '_' . $packet_key);
		
		if($digital_signature == $get_digital_signature && $data_urldecode != "")
		{
			
			
			$f = fopen("keys/private_" . $packet_key . ".pem", "r");
			
			$private_key = "";
			
			// Читать построчно до конца файла
			while(!feof($f)) { 
				$private_key .= fgets($f);
			}

			fclose($f);	
				
				
			$key = <<<SOMEDATA777
$private_key
SOMEDATA777;

			$pk  = openssl_get_privatekey($key);
			openssl_private_decrypt(base64_decode($data_urldecode), $out, $pk);	
			
			$out_mas = explode('@@@', $out);
			
			$signature_correct_str = $out_mas[0];
			$sinc_key = $out_mas[1];
			
			if($signature_correct_str == $digital_signature)
			{
				//Синхронный ключ корректен ибо мы извлекли нашу хитрую подпись
				
				
				/* decode */
				$decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256,$sinc_key, base64_decode(urldecode($encrypted_data_urlencode)),MCRYPT_MODE_ECB));
				
				$decrypted_mas = explode('@@@', $decrypted);
				
				$return_key = $decrypted_mas[0];				
				
				$msg = str_replace($return_key . '@@@', '', $decrypted);
				
				$msg = str_replace("@@p@@", "+", $msg);
				$msg = str_replace("@@pr@@", " ", $msg);				
				
				unlink("keys/private_" . $packet_key . ".pem");
				unlink("keys/public_" . $packet_key . ".pem");
				
				$return_data['return_key'] = $return_key;
				$return_data['msg'] = $msg;//Наше сообщение

				
				
			}
			else
			{
				echo $return_key_error;
			}			
			
			
		}
		
		return $return_data;
		
	}
	
	public function getPublicKeyForPushDataToB($get)
	{
		$packet_key = $get['packet_key'];
		$get_digital_signature = $get['digital_signature']; 
		
		$SIGNATURE_KEY = $this->getSIGNATURE_KEY();
		
		$digital_signature = md5($SIGNATURE_KEY . '_' . date('Y') . '_' . $packet_key);
				
		if($digital_signature == $get_digital_signature)
		{
			$bait = 1024;
			
			$com = "openssl genrsa -out keys/private_" . $packet_key . ".pem " . $bait;		
			$res = exec( $com, $output);

			$com = "openssl rsa -in keys/private_" . $packet_key . ".pem -out keys/public_" . $packet_key . ".pem -outform PEM -pubout";		
			$res = exec( $com, $output);	

			$f = fopen("keys/public_" . $packet_key . ".pem", "r");

			$public_key = "";
			
			// Читать построчно до конца файла
			while(!feof($f)) { 
				$public_key .= fgets($f);
			}

			fclose($f);	
			
			return $public_key;		
			
		}
	}	
	
	public function pushDataToB($data_mas)
	{	
		$result = false;
	
		//Однонаправленный посыл шифрованных данных на машину B с машины A
		//В случае успеха вернет true		

		$SIGNATURE_KEY = $this->getSIGNATURE_KEY();
	
		$path_b = $data_mas['path_b'];
		$path_public_key = $data_mas['path_public_key'];
		$notice = $data_mas['notice'];
		$method = $data_mas['method'];	
		$params = $data_mas['params'];	

		$packet_key = $this->genConnectionId();//Ключ для текущего экземпляра коннекции
		$digital_signature = md5($SIGNATURE_KEY . '_' . date('Y') . '_' . $packet_key);//Формируем данные доп проверки
		
		$url_public_key = $path_public_key . '?packet_key=' . $packet_key . '&digital_signature=' . $digital_signature;
		
		
		
		$public_key = file_get_contents($url_public_key);
		
		
		$pub = <<<SOMEDATA777
$public_key
SOMEDATA777;

		$sinc_key = $this->genSincKey(); //Этим ключем уже будем шифровать большой объем
		
		$data = $digital_signature . "@@@" . $sinc_key;
		
		
		
		$pk  = openssl_get_publickey($pub);
		openssl_public_encrypt($data, $encrypted, $pk);//Шифруем данные синхронного ключа и данные дополнительной проверки публичным ключем
		$data_key_crypt = chunk_split(base64_encode($encrypted));		
				
		$data_key_crypt = str_replace("+", "@@p@@", $data_key_crypt);
		$data_key_crypt = str_replace(" ", "@@pr@@", $data_key_crypt);
		
		
				
		//=============
		//Данные синхронного ключа для передачи готовы, теперь шифруем сами данные
		
		/* encode */			
		
		$return_key = md5($digital_signature  . '_' . date('Y.m.d') . '_' . $packet_key); 
		
		$notice = str_replace("+", "@@p@@", $notice);
		$notice = str_replace(" ", "@@pr@@", $notice);
		
		$notice_text = $return_key . '@@@' . $notice;	

		
		
		$encrypted_data = urlencode(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $sinc_key, $notice_text, MCRYPT_MODE_ECB)));
		
		//========================================
		//========================================
		
			//$test_decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256,$sinc_key, base64_decode(urldecode($encrypted_data)),MCRYPT_MODE_ECB));
		
		
		//========================================
		//========================================
		
		$encrypted_data = str_replace("+", "@@p@@", $encrypted_data);
		$encrypted_data = str_replace(" ", "@@pr@@", $encrypted_data);		
		
				
		//=============
			
		if($method == "GET")
		{
			$params_str = urlencode(json_encode($params));
			$encrypted_data_urlencode = $encrypted_data;
			$key_crypt_urlencode = urlencode($data_key_crypt);			
	
	
	
			$url_send = $path_b . '?packet_key=' . $packet_key . '&digital_signature=' . $digital_signature . '&key_crypt_urlencode=' . $key_crypt_urlencode . '&encrypted_data_urlencode=' . $encrypted_data_urlencode . '&params=' . $params_str;
			
			
			$send = file_get_contents($url_send);
		}			
		else
		{
			$params_str = json_encode($params);
			
			$postvars_mas = array();
			$postvars_mas['method'] = $method;
			$postvars_mas['packet_key'] = $packet_key;
			$postvars_mas['encrypted_data_urlencode'] = $encrypted_data;
			$postvars_mas['digital_signature'] = $digital_signature;
			$postvars_mas['key_crypt_urlencode'] = $data_key_crypt;
			$postvars_mas['params'] = $params_str;
			
			$ch = curl_init();	
			curl_setopt($ch, CURLOPT_URL, $path_b);
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars_mas);
			$send = curl_exec($ch);	
			curl_close($ch);	

			
	
		}	

		if($send == $return_key)
			$result = true;
	
		return $result;
	}
	
	

}
