<?php
header('Content-Type: text/html; charset=utf-8');


	include 'Encode.php';

	$BOT_API_KEY = '11111';//Это значение должно быть одинаково на обоих машинах. менять можно раз в год
	$encode_ex = new Encode($BOT_API_KEY);	
	$packet_key = $encode_ex->genConnectionId();
	$public_key = $encode_ex->genPublicKeyB();//Машана B генерирует публичный ключ чтбы машина A могла отослать ей данные
	
	$params = array();
	$params['test'] = '111';
	
	$data_mas = array();
	$data_mas['path_a'] = "http://q99920bs.bget.ru/all/s_radoid/dialogs/api/methods/server_a_1/getData.php";
	$data_mas['method'] = 'GET';
	$data_mas['packet_key'] = $packet_key;//Уникальный ключ коннекции
	$data_mas['public_key'] = $public_key;//Отсылаем публичный ключ
	$data_mas['params'] = $params;
	
	/*
		path_a - файл на сервере с которого мы должны получить шифрованные данные
		params - Эти данные передадутся при первичном обращении к удаленной машине, в незашифрованном виде
		method - Метод которым будет делать первичный запрос. Если у вас ограничение на количество POST запросов - то рекомендую использовать GET
				 но если у вас в params передаются большие значения - GET метод не подойдет
	*/
	
	$text = $encode_ex->getDataFromA($data_mas);//Получаем с удаленного сервера зашифрованный нашим публичным ключем данные с синхронным ключем и доп данными для проверки
	
	echo 'text=' . $text;
	


?>