<?php
header('Content-Type: text/html; charset=utf-8');

	include 'Encode.php';

	$BOT_API_KEY = '11111';//Это значение должно быть одинаково на обоих машинах. менять можно раз в год
	
	$encode_ex = new Encode($BOT_API_KEY);			
	$params = $encode_ex->parseArgsFromB($_GET, $_POST);//Получаем первичные данные в массив (если они переданы) и публичный ключ
		
	echo $encode_ex->echoEncodeDataToB('Привет');	
		
	

?>