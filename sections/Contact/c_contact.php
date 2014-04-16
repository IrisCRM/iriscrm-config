<?php
/**
 * Карточка контакта
 */
class c_Contact extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, array('config/common/Lib/lib.php'));
    }

    // Получить страну, город, область по id компании
    function onChangeAccountID($params, $con = null)
    {
        $con = GetConnection($con);
        $result = GetFormatedFieldValuesByFieldValue('Account', 'ID', 
                $params['value'], array('Address', 'ZIP', 'Scheme'), $con);
        $result = GetLinkedValues('Account', $params['value'], 
                array('Country', 'Region', 'City'), $con, $result);
        return $result;
    }

    // Получить страну, город, область, компанию по id объекта
    function onChangeObjectID($params, $con = null)
    {
        $con = GetConnection($con);
        $result = GetFormatedFieldValuesByFieldValue('Object', 'ID', 
                $params['value'], array('Address', 'ZIP', 'Scheme'), $con);
        $result = GetLinkedValues('Object', $params['value'], 
                array('Country', 'Region', 'City', 'Account'), $con, $result);
        return $result;
    }

    function onChangeRegionID($params, $con = null)
    {
        return GetLinkedValues('Region', $params['value'], 
                array('Country'), $con);
    }

    function onChangeCityID($params, $con = null)
    {
        return GetLinkedValues('City', $params['value'], 
                array('Country', 'Region'), $con);
    }
}
