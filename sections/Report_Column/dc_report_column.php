<?php
/**
 * Карточка поля отчёта
 */
include_once Loader::getLoader()->basePath() . 'config/common/Lib/report.php';

class dc_Report_Column extends ReportConfig
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'config/common/Lib/lib.php'));
    }

    public function onChangeColumnID($params)
    {
        return GetValuesFromTable('Table_Column', $params['value'], 
                array('Name', 'Code', 'ColumnTypeID'), $this->connection);
    }

}