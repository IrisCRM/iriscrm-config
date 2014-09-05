<?php

/**
 * Серверная логика карточки интервью
 */
class s_Interview extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, array('config/common/Lib/lib.php'));
    }


    function onPrepare($params) 
    {
        if ($params['mode'] != 'insert') {
            return null;
        };

        $result = null;

        //Значения справочников
        $result = GetDictionaryValues(array(
                    array ('Dict' => 'InterviewState', 'Code' => 'plan')
                ), $this->connection, $result);

        //Ответственный
        $UserName = GetUserName();
        $result = GetDefaultOwner($UserName, $this->connection, $result);
      
        list ($ID, $Name) = GetShortUserInfo($UserName, $this->connection);
        $result = FieldValueFormat('OperatorID', $ID, $Name, $result);

        return $result;
    }
}