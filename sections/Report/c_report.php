<?php
/**
 * Карточка отчёта
 */
include_once Loader::getLoader()->basePath() . 'config/common/Lib/report.php';

class c_Report extends ReportConfig
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'config/common/Lib/lib.php'));
    }

}
