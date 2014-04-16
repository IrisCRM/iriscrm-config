<?php

//********************************************************************
// Серверная логика карточки письма
//********************************************************************

// Функция вызывается после сохранения карточки
function Email_AfterPost($p_table, $p_id, $OldData, $NewData) {
	if (!empty($_POST['_reply_email_id'])) {
		$con = db_connect();
		// проставим ссылку на письмо, на которое отвечаем
		$cmd = $con->prepare("update iris_email set parentemailid = :parentid where id=:id");
		$cmd->execute(array(":parentid" => $_POST['_reply_email_id'], ":id" => $p_id));
	}
}

?>