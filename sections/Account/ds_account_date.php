<?php
/**
 * Серверная логика карточки компании
 */
class ds_Account_Date extends Config
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
        };

        $con = GetConnection($p_con);

        $result = null;

        // Дата
        $Date = GetCurrentDBDate($con);
        $result = FieldValueFormat('Date', $Date, null, $result);

        return $result;
    }
}
