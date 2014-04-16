<?php
/**
 * Серверная логика карточки напоминания
 */
class s_Remind extends Config
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

        $record = current($this->connection->query(
                "select max(number) + 1 as number from iris_remind")->
                fetchAll(PDO::FETCH_ASSOC));

        return FieldValueFormat('Number', $record['number'], null, $values);
    }
}
