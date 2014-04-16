<?php
/**
 * Серверная логика вкладки Бонусы карточки контакта
 */
class ds_Contact_Date extends Config
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

        // Дата
        return FieldValueFormat('Date', GetCurrentDBDate(null));
    }

}
