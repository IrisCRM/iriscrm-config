<?php
/**
 * Продукты в компании
 */
class dc_Account_Product extends Config
{
    function __construct($Loader)
    {
        parent::__construct($Loader, array('config/common/Lib/lib.php'));
    }

	// Обработка изменения значения поля
	function onChangeProductID($params, $con = null)
	{
		return GetValuesFromTable('Product', $params['value'], 
				array('Price'), $con);
	}
}