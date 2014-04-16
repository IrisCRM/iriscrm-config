<?php
/**
 * Карточка комплектации продукта
 */
class dc_Product_Constituent extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'config/common/Lib/lib.php', 
            'config/sections/Project/s_project.php'));
    }

    public function onChangeConstituentID($params)
    {
        return GetValuesFromTable('Product', $params['value'], 
                array('Price', 'UnitID', 'Cost'), $this->connection);
    }
}
