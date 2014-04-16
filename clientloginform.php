<?php

/**********************************************************************
clientloginform.php
Форма для авторизации клиентов
**********************************************************************/

function showClientLoginForm($p_default_style) {
	$login_html = file_get_contents('config/login.html');
	// если передали параметры для автовхода, то передадим их в форму логина для автовхода
	if ($_POST['autologin'] != '') {
		$login_html = iris_str_replace('post_vars=""', 'post_vars="'.htmlentities(json_encode($_POST), ENT_QUOTES).'"', $login_html);
	}
	$login_html = iris_str_replace('#charset#', GetDefaultEncoding(), $login_html);
		
	return $login_html;
}


?>