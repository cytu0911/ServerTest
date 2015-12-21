<?php
function num2UInt32Str($num) {
	$str = '';
	$str .= pack('C', ($num >> 24) & 0xFF);
	$str .= pack('C', ($num >> 16) & 0xFF);
	$str .= pack('C', ($num >> 8) & 0xFF);
	$str .= pack('C', ($num >> 0) & 0xFF);
	return $str;
}

function UInt32Binary2Int($data) {
	return ($data[0] << 24) | ($data[1] << 16) | ($data[2] << 8) | ($data[3]);
}

function utf8substr($str, $start, $len) {
	$res = "";
	$strlen = $start + $len;
	for ($i = 0; $i < $strlen; $i++) {
		if ( ord(substr($str, $i, 1)) > 127 ) {
			$res.=substr($str, $i, 3);
			$i+=2;
		}
		else {
			$res.= substr($str, $i, 1);
		}
	}
	return $res;  
}

function dump($data)
{
	$data = is_array($data) ? json_encode($data) : strval($data);
	echo "[".date("m-d H:i:s")."] $data\n";
}
//post|get to url 
function urlReq($url, $is_post = false, $data = null, $agent = 0, $cookie = null, $timeout = 10)
{
	if ($agent && is_int($agent)) {
		$user_agent = ini_get('user_agent');
		ini_set('user_agent', 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727;)');
	}
	elseif ($agent && is_array($agent)) {
		$user_agent = ini_get('user_agent');
		ini_set('user_agent', $agent[array_rand($agent)]);
	}
	elseif (is_string($agent)) {
		$user_agent = ini_get('user_agent');
		ini_set('user_agent', $agent);
	}
	else {
		$user_agent = false;
	}
	$context['http']['method'] = (!$is_get && is_array($data)) ? 'POST' : 'GET';
	$context['http']['header'] = (!$is_get && is_array($data)) ? "Content-Type: application/x-www-form-urlencoded; charset=utf-8" : "Content-Type: text/html; charset=utf-8";
	$context['http']['timeout'] = $timeout;
	if ( $context['http']['method'] == 'POST' )
	{
		if ( $cookie && is_string($cookie) )
		{
			$context['http']['header'] .= PHP_EOL.$cookie;
		}
		if ( strpos($url, 'https://') === 0 && isset($data['https_user']) && isset($data['https_password']) )
		{
			$context['http']['header'] .= PHP_EOL."Authorization: Basic ".base64_encode($data['https_user'].":".$data['https_password']);
			unset($data['https_user']);
			unset($data['https_password']);
		}
		$context['http']['content'] = http_build_query($data, '', '&');
	}
	$res = file_get_contents($url, false, stream_context_create($context));
	$user_agent !== false && ini_set('user_agent', $user_agent);
	return $res;
}

