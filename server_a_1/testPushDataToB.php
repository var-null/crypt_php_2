<?php
header('Content-Type: text/html; charset=utf-8');

	include 'Encode.php';

	//Демо - http://q99920bs.bget.ru/all/s_radoid/dialogs/api/methods/server_a_1/testPushDataToB.php
	
	//=======================================================
	//Пример отправки шифрованых данных на машину B от машины A (текущая машина A)
	//=======================================================	
	
	$BOT_API_KEY = '11111';//Это значение должно быть одинаково на обоих машинах. менять можно раз в год	
	$encode_ex = new Encode($BOT_API_KEY);	

	$data_mas = array();
	$data_mas['path_public_key'] = "http://softomania.org.ua/telegram_editor/vendor/longman/telegram-bot/src/server_b_1/get_public_key.php";
	$data_mas['path_b'] = "http://softomania.org.ua/telegram_editor/vendor/longman/telegram-bot/src/server_b_1/pushDataFromA.php";//Файл на удаленной машине B, который должен получить данные	
	$data_mas['method'] = 'POST';
	$data_mas['notice'] = 'Привет машине B методом ' . $data_mas['method'];//Текст, который необходимо передать на машину B
	
		$params = array();
		$params['test'] = "метод=" . $data_mas['method'];	

	$data_mas['params'] = $params;		
	
	if($encode_ex->pushDataToB($data_mas))
		echo 'REQUEST SUCCESS';
	else
		echo 'REQUEST ERROR';

	

?>