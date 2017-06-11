<?php
header('Content-Type: text/html; charset=utf-8');


	include 'Encode.php';
	
	//Демо: http://softomania.org.ua/telegram_editor/vendor/longman/telegram-bot/src/server_b_1/testGetDataFromA.php
	
	//=======================================================
	//Пример получения шифрованных данных с машины А на машину B по запросу с машины B (текущая машина B).
	//=======================================================
	

	
	$data_mas = array();
	$data_mas['path_a'] = "http://q99920bs.bget.ru/all/s_radoid/dialogs/api/methods/server_a_1/getDataToB.php";//Файл на удаленной машине A, который должен вернуть данные
	$data_mas['method'] = 'GET';
	
		$params = array();
		$params['test'] = $data_mas['method'];		
	
	$data_mas['params'] = $params;
	
	
	/*
		path_a - файл на сервере с которого мы должны получить шифрованные данные
		params - Эти данные передадутся при первичном обращении к удаленной машине, в незашифрованном виде
		method - Метод которым будет делать первичный запрос. Если у вас ограничение на количество POST запросов - то рекомендую использовать GET
				 но если у вас в params передаются большие значения - GET метод не подойдет, - используйте POST
	*/
	
	$BOT_API_KEY = '11111';//Это значение должно быть одинаково на обоих машинах. менять можно раз в год
	$encode_ex = new Encode($BOT_API_KEY);		
	$text = $encode_ex->getDataFromA($data_mas);//Получаем с удаленного сервера зашифрованный нашим публичным ключем данные с синхронным ключем и доп данными для проверки
	
	echo 'text=' . $text;
	
	
	

?>