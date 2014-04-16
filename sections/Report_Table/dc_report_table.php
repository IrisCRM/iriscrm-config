<?php
/**
 * Карточка таблицы отчёта
 */
include_once Loader::getLoader()->basePath() . 'config/common/Lib/report.php';

class dc_Report_Table extends ReportConfig
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'config/common/Lib/lib.php'));
    }

    public function onChangeTableID($params)
    {
        return GetValuesFromTable('Table', $params['value'], 
                array('Name', 'Code'), $this->connection);
    }

}
