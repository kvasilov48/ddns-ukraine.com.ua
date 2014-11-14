<?php
// Настройки. Подробнее https://cp.ukraine.com.ua/user/api/
$auth_login = "login@mail.ru";
$auth_token = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";
$domain = "example.net";
$sub_domain = "admin";

// Ф-ция отправки данных и получения ответа с ukraine.com.ua
function ukr_response($fields){
	$field = http_build_query($fields, '', '&');
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://cp.ukraine.com.ua/tools/api.php");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $field);
	$response = curl_exec($ch);
	curl_close($ch);
	$xml = simplexml_load_string($response);
	for($i=0;$i<count($xml->data->record);$i++){
		$info[$i] = (array)$xml->data->record[$i];
		if(!$info[$i]['priority']){
			unset($info[$i]['priority']);
		}
	}
	$info['status'] = strval($xml->status);
	if($info['status'] == "error"){
		echo $xml->message;
		exit;
	}
	return $info;
}

// Ф-ция получает айпишник машини на которой выполняется скрипт.
function get_real_ip(){
	$a = file_get_contents("http://internet.yandex.ru/");
	$start = mb_strpos($a, "IPv4:")+6;
	$a = mb_substr($a, $start);
	$end = mb_strpos($a, " ");
	$ip = mb_substr($a, 0, $end);
	if(preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])'.'\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]?|[0-9])$/', $ip) == FALSE){
		die(date("Y-m-d H:i:s")." - Получил не правильный IP адресс -".$ip);
	}
	return trim($ip);
}

// Строка запроса для метода 'info'.
$field = array('auth_login'=>$auth_login,'auth_token'=>$auth_token,'format'=>"xml",'class'=>"dns_record",'method'=>"info",'domain'=>$domain);
$info = ukr_response($field);

// Получаем реальний айпишник нашего сервера. Если ненужно просто установите новый IP в переменную $real_ip.
$real_ip = get_real_ip();

// Перебираем все записи, ищем наш поддомен
for($i=0;$i<count($info);$i++){
	// Если поддомен найден...
	if($info[$i]['record'] == $sub_domain.".".$domain."."){
		// Сверяем айпишники. Если не сходятся устанавливаем новый.
		if($info[$i]['data'] != $real_ip){
			$stack = array(array('id'=>$info[$i]['id'],'data'=>$real_ip));
			$str = array('auth_login'=>$auth_login,'auth_token'=>$auth_token,'format'=>"xml",'class'=>"dns_record",'method'=>"edit",'domain'=>$domain,'stack'=>$stack);
			$edit = ukr_response($str);
			
			if($edit['status'] == "success"){
				echo "Установил новый IP (".$real_ip.") для домена - ".$sub_domain.".".$domain;
			}
		}
		else{echo "Айпишники совпадают.";}
		exit();
	}
}
?>
