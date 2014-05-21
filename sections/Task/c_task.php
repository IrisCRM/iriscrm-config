<?php
/**
 * Карточка дела
 */
class c_Task extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array('config/common/Lib/lib.php'));
    }

    public function onChangeTaskID($params)
    {
        return GetLinkedValues('Task', $params['value'], 
                array('Account', 'Contact', 'Object', 'Product', 'Project', 
                    'Issue', 'Bug', 'Marketing', 'Space', 'Offer', 'Pact', 
                    'Invoice', 'Payment', 'FactInvoice', 'Document', 
                    'Incident'), 
                $this->connection);
    }

    public function onChangeContactID($params)
    {
        return GetLinkedValues('Contact', $params['value'], 
                array('Account', 'Object'), $this->connection);
    }

    public function onChangeObjectID($params)
    {
        return GetLinkedValues('Object', $params['value'], 
                array('Account', 'Contact'), $this->connection);
    }

    public function onChangeProjectID($params)
    {
        return GetLinkedValues('Project', $params['value'], 
                array('Account', 'Object', 'Contact'), $this->connection);
    }

    public function onChangeIssueID($params)
    {
        return GetLinkedValues('Issue', $params['value'], 
                array('Product'), $this->connection);
    }

    public function onChangeBugID($params)
    {
        $result = GetLinkedValues('Bug', $params['value'], 
                array('Project', 'Issue'), $this->connection);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), $this->connection, 
                $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'IssueID', 'Value');
        $result = GetLinkedValues('Issue', $id, 
                array('Product'), $this->connection, $result);

        return $result;
    }

    public function onChangeIncidentID($params)
    {
        return GetLinkedValues('Incident', $params['value'], 
                array('Account', 'Contact', 'Object', 'Product', 'Issue', 
                    'Marketing', 'Space', 'Project', 'Offer', 'Pact', 
                    'Invoice', 'Payment', 'FactInvoice', 'Document'), 
                $this->connection);
    }

    public function onChangeOfferID($params)
    {
        $result = GetLinkedValues('Offer', $params['value'], 
                array('Project', 'Account', 'Contact'), $this->connection);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), $this->connection, 
                $result);

        return $result;
    }

    public function onChangePactID($params)
    {
        $result = GetLinkedValues('Pact', $params['value'], 
                array('Account', 'Contact', 'Project'), $this->connection);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), $this->connection, 
                $result);

        return $result;
    }

    public function onChangeInvoiceID($params)
    {
        $result = GetLinkedValues('Invoice', $params['value'], 
                array('Account', 'Contact', 'Project', 'Pact', 'Offer'), 
                $this->connection);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'PactID', 'Value');
        $result = GetLinkedValues('Pact', $id, 
                array('Account', 'Contact', 'Project'), $this->connection, 
                $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), $this->connection, 
                $result);

        return $result;
    }

    public function onChangePaymentID($params)
    {
        $result = GetLinkedValues('Payment', $params['value'], 
                array('Account', 'Contact', 'Project', 'Pact', 'Invoice'), 
                $this->connection);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'InvoiceID', 'Value');
        $result = GetLinkedValues('Invoie', $id, 
                array('Account', 'Contact', 'Project', 'Pact'), 
                $this->connection, $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'PactID', 'Value');
        $result = GetLinkedValues('Pact', $id, 
                array('Account', 'Contact', 'Project'), $this->connection, 
                $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), $this->connection, 
                $result);

        return $result;
    }

    public function onChangeFactInvoiceID($params)
    {
        $result = GetLinkedValues('FactInvoice', $params['value'], 
            array('Account', 'Contact', 'Project', 'Pact', 'Invoice'), 
            $this->connection);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'InvoiceID', 'Value');
        $result = GetLinkedValues('Invoice', $id, 
                array('Account', 'Contact', 'Project', 'Pact'), 
                $this->connection, $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'PactID', 'Value');
        $result = GetLinkedValues('Pact', $id, 
                array('Account', 'Contact', 'Project'), $this->connection, 
                $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), $this->connection, 
                $result);

        return $result;
    }

    public function onChangeDocumentID($params)
    {
        $result = GetLinkedValues('Document', $params['value'], 
                array('Account', 'Contact', 'Project', 'Pact'), 
                $this->connection);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'PactID', 'Value');
        $result = GetLinkedValues('Pact', $id, 
                array('Account', 'Contact', 'Project'), $this->connection, 
                $result);

        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), $this->connection, 
                $result);

        return $result;
    }

    public function onChangeTaskResultID($params)
    {
        $code = GetFieldValueByID(
                'TaskResult', $params['value'], 'Code', $this->connection);

        $result = null;
        if ($code == 'Completed') {
            $result = GetDictionaryValues(array(
                array('Dict' => 'TaskState', 'Code' => 'Finished'),
            ), $this->connection);

            $date = GetCurrentDBDateTime($this->connection);
            $result = FieldValueFormat('FinishDate', $date, null, $result);
            $result = FieldValueFormat('IsRemind', 0, null, $result);
        }

        return $result;
    }

    public function onChangeNextTaskTargetID($params)
    {
        list($days, $hours, $minutes) = GetFieldValuesByID('TaskTarget', $params['value'], 
            array('days', 'hours', 'minutes'), $this->connection);

        $next_date = $this->_Local->timeToLocalDateTime(
                $this->_Local->dbDateToTime($this->_DB->datetime()) + 
                60 * 60 * 24 * $days +
                60 * 60 * $hours +
                60 * $minutes);
        return FieldValueFormat('NextStartDate', $next_date);
    }

    public function renderSelectRecordDialog($params)
    {
        // Описание колонок, которые будут отображаться в таблице
        $columns = array(
            'orderpos' => array(
                'caption' => 'Порядок',
                'type' => 'int',
                'display' => false,
                'sort' => 'asc',
                'width' => '10%',
            ),
            'stagename' => array(
                'caption' => 'Стадия',
                'type' => 'string',
                'width' => '20%',
            ),
            'name' => array(
                'caption' => 'Название',
                'type' => 'string',
                'width' => '50%',
            ),
            'completed' => array(
                'caption' => 'Завершено',
                'type' => 'datetime',
                'width' => '20%',
            ),
        );

        // Выбираем данные для отображеия в таблице
        $sql = "select t0.id as id, 
                t0.orderpos as orderpos, 
                t1.name as stagename,
                t0.name as name,
                _iris_datetime_to_string[(select max(FinishDate)
                    from iris_task t00
                    left join iris_taskstate t01 on t01.id = t00.taskstateid
                    where t00.projectid = :projectid
                    and t00.tasktargetid = t0.id
                    and t01.code = 'Finished'
                )] as completed
                from iris_tasktarget t0
                left join iris_projectstage t1 on t0.projectstageid = t1.id
                where t0.isactive = 1
                order by t0.orderpos
        ";
        $filter = array(
            ':projectid' => $params['projectid'],
        );
        $values = $this->_DB->exec($sql, $filter);

        // Выбранная по умолчанию запись - либо следующая либо текущая цель
        $targetid = $params['nexttargetid'] && $params['nexttargetid'] != 'null'
                ? $params['nexttargetid'] : $params['targetid'];
        $parameters = array(
            'selected_id' => $targetid,
            'grid_id' => 'custom_grid_'. md5(time() . rand(0, 10000)),
        );

        // Подготовка данных для пердставления таблицы
        $data = $this->getCustomGrid($columns, $values, $parameters);

        // Построение представления таблицы
        $result = array(
            //'Error' => 'Возникла ошибка',
            'Card' => $this->renderView('grid', $data),
            'GridId' => $parameters['grid_id'],
        );
        return $result;
    }
}
