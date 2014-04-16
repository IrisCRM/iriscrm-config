<?php
/**
 * Серверная логика фильтра отчёта
 */
include_once Loader::getLoader()->basePath() . 'config/common/Lib/report.php';

class ds_Report_Filter extends ReportConfig
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'config/common/Lib/lib.php'));
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        // Номер
        $result = FieldValueFormat('Number', 
                $this->getPosition($params['detail_column_value'], 'Filter'),
                null, $result);

        // Условие по умолчанию
        $result = FieldValueFormat('condition', 1, null, $result);

        // Отображать
        $result = FieldValueFormat('isvisible', 1, null, $result);
        
        return $result;
    }

    public function onAfterPost($table, $id, $old_data, $new_data) {
        // Перенумеруем позиции при необходимости
        $this->renumber($old_data, $new_data, $id, 'Filter');
    }

}
