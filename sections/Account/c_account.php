<?php
/**
 * Карточка компании
 */
class c_Account extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, array('config/common/Lib/lib.php'));
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
