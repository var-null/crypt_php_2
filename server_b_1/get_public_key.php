<?php
header('Content-Type: text/html; charset=utf-8');

	include 'Encode.php';
	
	//Этот скрипт получит шифрованные данные с машины A в случае если нам их пытаются передать методом pushDataToB
	
	
	$BOT_API_KEY = '11111';//Это значение должно быть одинаково на обоих машинах. менять можно раз в год
	$encode_ex = new Encode($BOT_API_KEY);		

	echo $encode_ex->getPublicKeyForPushDataToB($_GET);//Сгенерируем публичный ключ для получения синхронного ключа и данных

	
?>