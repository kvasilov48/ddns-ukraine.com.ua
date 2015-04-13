<?php
// Настройки. Подробнее https://cp.ukraine.com.ua/user/api/
$auth_login = 'login@mail.ru';
$auth_token = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
$domain = 'example.net';
$sub_domain = 'admin';
$format = 'json';

// Ф-ция отправки данных и получения ответа с ukraine.com.ua
function ukr_response($fields){
	$field = http_build_query($fields, '', '&');
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://cp.ukraine.com.ua/tools/api.php');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $field);
	$response = curl_exec($ch);
	curl_close($ch);
	$info = json_decode($response, TRUE);
	if($info['status'] == 'error'){
		echo $info['message'];
		return FALSE;
	}
	else return $info;
}
// Ф-ция получения и проверки айпишника.
function get_real_ip($url){
	$ip = file_get_contents($url);
	if(preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])'.'\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]?|[0-9])$/', $ip) == FALSE){
		die(date('Y-m-d H:i:s').' - Получил не правильный IP адресс -'.$ip);
	}
	return trim($ip);
}

// Строка запроса для метода 'info'.
$field = array('auth_login'=>$auth_login,'auth_token'=>$auth_token,'format'=>"xml",'class'=>"dns_record",'method'=>"info",'domain'=>$domain);
$info = ukr_response($field);
// Получаем реальний айпишник нашего сервера. Если ненужно просто установите новый IP в переменную $real_ip.
$real_ip = get_real_ip('http://curlmyip.com');

// Перебираем все записи, ищем наш поддомен
foreach($info['data'] as $key => $value){
	// Если поддомен найден...
	if($info['data'][$key]['record'] == $sub_domain.".".$domain."."){
		// Сверяем айпишники. Если не сходятся устанавливаем новый.
		if($info['data'][$key]['data'] != $real_ip){
			$stack = array(array('id'=>$info['data'][$key]['id'],'data'=>$real_ip));
			$str = array('auth_login'=>$auth_login,'auth_token'=>$auth_token,'format'=>'json','class'=>'dns_record','method'=>'edit','domain'=>$domain,'stack'=>$stack);
			$edit = ukr_response($str);
			if($edit['status'] == 'success'){
				echo 'Установил новый IP ('.$real_ip.') для домена - '.$sub_domain.'.'.$domain;
			}
		}
		else echo 'Айпишники совпадают.';
		exit();
	}
}
?>
