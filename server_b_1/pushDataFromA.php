<?php
header('Content-Type: text/html; charset=utf-8');

	include 'Encode.php';
	
	//Этот скрипт получит шифрованные данные с машины A в случае если нам их пытаются передать методом pushDataToB
		
	$BOT_API_KEY = '11111';//Это значение должно быть одинаково на обоих машинах. менять можно раз в год
	$encode_ex = new Encode($BOT_API_KEY);		

	$return_data = $encode_ex->parseDataFromA($_GET, $_POST);
	$msg = $return_data['msg'];
	$params = $return_data['params'];
	
	//Примем данные и просто положим в файлик:
	
	$fp = fopen("tmp_from_a.txt", 'a');
	$trace = fwrite($fp, $msg); 
	fclose($fp);		
	
	echo $return_data['return_key'];
		
	


?>