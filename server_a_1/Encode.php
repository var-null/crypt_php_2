<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
		
	
	public function parseArgs($get, $post)
	{
		$method = "GET";
		
		if($post['method'] == "POST")
			$this->method = "POST";
		
		
		
		if($method == "GET")
		{		
			$public_key = urldecode($get['public_key']);
			
			$public_key = str_replace("@@p@@", "+", $public_key);
			$public_key = str_replace("@@pr@@", " ", $public_key);					
		
			$this->public_key_from_args_from_b = $public_key;
			$this->packet_key_from_args_from_b = $get['packet_key'];
			$this->args = json_decode(urldecode($get['params']), true);
			
		}
		else
		{
			$public_key = urldecode($post['public_key']);
			
			$public_key = str_replace("@@p@@", "+", $public_key);
			$public_key = str_replace("@@pr@@", " ", $public_key);	
			
			$this->public_key_from_args_from_b = urldecode($public_key);
			$this->packet_key_from_args_from_b = $post['packet_key'];
			$this->args = json_decode(urldecode($post['params']), true);
			
		}
		
	
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
	

	
		$sinc_key = md5(uniqid() . ' ' . uniqid()); //Этим ключем уже будем шифровать большой объем

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
	
	public function genPublicKeyB()
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
		
		//$public_key_urlencode = urlencode($public_key);
		
		//$data_str = $id . '_' . $public_key;  
		
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
		
		//echo 'uniq_id=' . $uniq_id;
		//echo 'private_key=' . $private_key;
		
		$key = <<<SOMEDATA777
$private_key
SOMEDATA777;

		$pk  = openssl_get_privatekey($key);
		openssl_private_decrypt(base64_decode($data_with_sinc), $out, $pk);	
		
		//echo 'out=' . $out;
		
		$out_mas = explode('@@@', $out);
		
		$signature_correct_str = $out_mas[0];
		$sinc_key = $out_mas[1];	
			
		
		if($signature_correct_str == $digital_signature)
		{
			//echo '!!!!!!!!!!!!!!!!!!!!';
			
			//Синхронный ключ корректен ибо мы извлекли нашу хитрую подпись
			
			//echo 'sinc_key target=' . $sinc_key . '<br>';

			
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
		
		$public_key =urlencode($public_key);
		$params = $data_mas['params'];
		
		$params_str = urlencode(json_encode($params));
		
		$resp = file_get_contents($path_a . "?method=" . $method . "&packet_key=" . $packet_key . "&public_key=" . $public_key . "&params=" . $params_str);
			
		$resp_mas = explode('@@@' , $resp);	
			
		return $this->parseDataWithSincFromA($resp_mas);//Разбираем ответ с машины A. В этом ответе синхронный ключ
	}
	
	
	public function getDataFromA($data_mas)
	{
		//Запросим удаленный php на получение данных
		
		return $this->reqDataSincFromA($data_mas);
		
	}	
	
	public function pushToB($encrypt_mas)
	{
		//Однонаправленный посыл шифрованных данных на машину B с машины A
		//В случае успеха вернет true
		
		$SIGNATURE_KEY = $this->getSIGNATURE_KEY();
	
		$server_b = $encrypt_mas['server_b'];
		$file_target = $encrypt_mas['file_target'];
		$id = $encrypt_mas['id'];
		$notice = $encrypt_mas['notice'];
	
		//Генерим цифровую подпись, которую сможем проверить на месте после расшифровки глобального сообщения
		$digital_signature = md5($SIGNATURE_KEY . ' ' . date('Y') . '_' . $id);
		//Отсылаем запрос на приемник чтобы нам сгенерили открытый ключ для этого id
		
		//echo 'SIGNATURE_KEY=' . $SIGNATURE_KEY . '<br>';
		//echo 'id=' . $id . '<br>';
		//echo 'digital_signature=' . $digital_signature . '<br>';
		
		$url_get_public_key = $server_b . '/get_public_key.php?id=' . $id . '&digital_signature=' . $digital_signature;
		
		$public_key = file_get_contents($url_get_public_key);
		
	

			
		$pub = <<<SOMEDATA777
$public_key
SOMEDATA777;


		//echo $pub . '<br>';
		
		$sinc_key = md5($digital_signature  . '_' . date('Y.m.d')); 

		$data = $digital_signature . "@@@" . $sinc_key;

		
		//echo 'sinc_key=' . $sinc_key . '<br>';
		
		$pk  = openssl_get_publickey($pub);
		openssl_public_encrypt($data, $encrypted, $pk);
		$data_key_crypt = chunk_split(base64_encode($encrypted));		

		//echo 'data_key_crypt=' . $data_key_crypt . '<br>';
		
		$data_key_crypt = str_replace("+", "@@p@@", $data_key_crypt);
		
		$key_crypt_urlencode = urlencode($data_key_crypt);
		
		//echo 'key_crypt_urlencode=' . $key_crypt_urlencode . '<br>';
		
		//=============
		
		/* encode */
		
		$return_key = md5($digital_signature  . '_' . date('Y.m.d') . '_' . $id); 
		
		//echo 'return_key=' . $return_key . '<br>';
		
		$notice_text = $return_key . '@@@' . str_replace("+", "@@p@@", $notice);
		
		//echo 'notice_text=' . $notice_text . '<br>';
		
		$encrypted_data = urlencode(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $sinc_key, $notice_text, MCRYPT_MODE_ECB)));

		//echo 'Зашифровали синхронным ключом=' . $encrypted_data . '<br>';
		
		$encrypted_data_urlencode = urlencode($encrypted_data);
		
		//echo 'urlencode текста=' . $encrypted_data_urlencode . '<br>';
		
		
		
		//=============
		
		$url_send = $server_b . '/' . $file_target . '?id=' . $id . '&digital_signature=' . $digital_signature . '&key_crypt_urlencode=' . $key_crypt_urlencode . '&encrypted_data_urlencode=' . $encrypted_data_urlencode;
		$send = file_get_contents($url_send);

		
		$ddd = "";
		$ddd .= "send=" . $send . "\n";
		$ddd .= "digital_signature=" . $digital_signature . "\n";
		$ddd .= "key_crypt_urlencode=" . $key_crypt_urlencode . "\n";
		$ddd .= "SIGNATURE_KEY=" . $SIGNATURE_KEY . "\n";
		$ddd .= "url_send=" . $url_send . "\n";
		
		
		//$fp = fopen("vendor/longman/telegram-bot/src/enccc.txt", 'a');
		//$trace = fwrite($fp, $ddd); 
		//fclose($fp);			
		
		
		if($send == $return_key)
			return true;
		else
			return false;
		
		
	}		
	

}
