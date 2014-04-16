<?php
/**
 * Карточка проекта
 */
class c_Issue extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'config/common/Lib/lib.php'));
    }

    public function onChangeIssueStateID($params, $con = null) 
    {
        $StateCode = GetFieldValueByID('IssueState', $params['value'], 'Code', 
                $this->connection);
        if ($StateCode == 'Finished') {
            $date = GetCurrentDBDate($this->connection);
            $result = FieldValueFormat('FinishDate', $date, null, $result);
        }
        return $result;
    }
}
