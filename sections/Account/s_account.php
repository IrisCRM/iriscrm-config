<?php
/**
 * Серверная логика карточки компании
 */
class s_Account extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, array('config/common/Lib/lib.php'));
    }


    function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $con = $this->connection;

        $result = null;

        // Значения справочников
        $result = GetDictionaryValues(
            array(
                array ('Dict' => 'AccountType', 'Code' => 'Client'),
                array ('Dict' => 'AccountFace', 'Code' => 'Legal'),
                array ('Dict' => 'AccountState', 'Code' => 'C'),
                array ('Dict' => 'Category', 'Code' => 'D')
            ), $con, $result
        );

        // Ответственный
        $UserName = GetUserName();
        $result = GetDefaultOwner($UserName, $con, $result);

        // Получим страну, город и область из компании пользователя
        $UserID = GetArrayValueByParameter($result['FieldValues'], 'Name', 'OwnerID', 'Value');
        $AccountID = GetFieldValueByID('Contact', $UserID, 'AccountID', $con);
        $result = GetLinkedValues('Account', $AccountID, array('Country', 'City', 'Region'), $con, $result);

        // Дата
        $Date = GetCurrentDBDate($con);
        $result = FieldValueFormat('FirstContactDate', $Date, null, $result);

        return $result;
    }
}
