<?php

//********************************************************************
// Серверная логика карточки работы
//********************************************************************
include_once GetPath().'/config/common/Lib/lib.php';

function Work_onPrepare($params) {
	$con = GetConnection();

	//Значения справочников
	$result = GetDictionaryValues(
		array (
			array ('Dict' => 'WorkType', 'Code' => 'Work'),
			array ('Dict' => 'WorkState', 'Code' => 'Plan')
			), $con);

	//Ответственный
	$UserName = GetUserName();
	$result = GetDefaultOwner($UserName, $con, $result);
	//$result = FieldValueFormat('OwnerID', GetUserID(), GetUserName(), $result);
    return $result;
}

?>