<?php
header('Content-Type: text/html; charset=utf-8');

	include 'Encode.php';

	//Этот файл по http машина B запросит шифрованные данные
		
	$BOT_API_KEY = '11111';//Это значение должно быть одинаково на обоих машинах. менять можно раз в год
	
	$encode_ex = new Encode($BOT_API_KEY);			
	$params = $encode_ex->parseArgsFromB($_GET, $_POST);//Получаем первичные данные в массив (если они переданы) и публичный ключ
		
		$arg = $params['test'];
		
	$return_text = 'Привет от машины A. Кстати, машина A получила параметр "' . $arg . '". Но он был незашифрован. А этот текст зашифрован!';
		
	echo $encode_ex->echoEncodeDataToB($return_text);//То что мы вернем
		
	//$fp = fopen("tmp_from_a.txt", 'a');
	//$trace = fwrite($fp, print_r($_GET, true) . "_GET:--------------------" . print_r($_POST, true) . "_POST:========================================"); 
	//fclose($fp);		

?>