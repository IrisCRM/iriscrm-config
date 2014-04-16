<?php
/**
 * Карточка проекта
 */
class c_Project extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'config/common/Lib/lib.php', 
            'config/sections/Project/s_project.php'));
    }

    public function onChangeAccountID($params)
    {
        $result = GetLinkedValuesDetailed('iris_Account', $params['value'], array(
            array('Field' => 'PrimaryContactID',
                'GetField' => 'Name',
                'GetTable' => 'iris_Contact')
        ));
        if (!$result['FieldValues'][0]['Value']) {
            return null;
        }
        $result['FieldValues'][0]['Name'] = 'ContactID';
        return $result;
    }

    public function onChangeContactID($params)
    {
        return GetLinkedValues('Contact', $params['value'], 
                array('Account'), $this->connection);
    }

    public function onChangeObjectID($params)
    {
        return GetLinkedValues('Object', $params['value'], 
                array('Account', 'Contact'), $this->connection);
    }

    public function onChangeProjectStageID($params) 
    {
        $s_Project = new s_Project(Loader::getLoader());
        return $s_Project->getProbability($params['value']);
    }

    public function onChangeProjectStateID($params) 
    {
        // Изменение состояния ведет к изменению стадии (если завершено)
        $StateCode = GetFieldValueByID('ProjectState', $params['value'], 
                'Code', $this->connection);

        if ($StateCode == 'Finished') {
            list ($Probability, $StageID) = GetFieldValuesByFieldValue(
                    'ProjectStage', 'Code', 'Finished', 
                    array('Probability', 'ID'), $this->connection);
            $result = FieldValueFormat('Probability', $Probability, null, $result);
            $result = FieldValueFormat('ProjectStageID', $StageID, null, $result);
            $date = GetCurrentDBDate($this->connection);
            $result = FieldValueFormat('FinishDate', $date, null, $result);
        }            
        return $result;
    }


    public function getEnabledFields($params)
    {
        $p_ProjectID = $params['id'];
            
        $l_PaymentCode_In = "In";
        $l_PaymentCode_Out = "Out";
        
        //Блокировать или не блокировать поля
        // - Планируемый доход. Если есть продукты, то блокировать
        $select_sql = "select count(*) from iris_Project_Product "
                . "where ProjectID = :p_ProjectID";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(':p_ProjectID' => $p_ProjectID));
        $statement->bindColumn(1, $Number);
        $res = $statement->fetch();
        $PlanIncome_enabled = $Number == 0 ? true : false;

        // - Доход. Если есть входящие платежи, то блокировать
        $select_sql = "select count(*) "
                . "from iris_Payment p, iris_PaymentType pt "
                . "where p.PaymentTypeID = pt.id "
                . "and pt.Code = :p_PaymentTypeCode "
                . "and ProjectID = :p_ProjectID";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':p_ProjectID' => $p_ProjectID,
            ':p_PaymentTypeCode' => $l_PaymentCode_In,
        ));
        $statement->bindColumn(1, $Number);
        $res = $statement->fetch();
        $Income_enabled = $Number == 0 ? true : false;

        // - Расходы. Если есть исходящие платежи, то блокировать
        $select_sql = "select count(*) "
                . "from iris_Payment p, iris_PaymentType pt "
                . "where p.PaymentTypeID = pt.id "
                . "and pt.Code = :p_PaymentTypeCode "
                . "and ProjectID = :p_ProjectID";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(
            ':p_ProjectID' => $p_ProjectID,
            ':p_PaymentTypeCode' => $l_PaymentCode_Out,
        ));
        $statement->bindColumn(1, $Number);
        $res = $statement->fetch();
        $Expense_enabled = $Number == 0 ? true : false;

        $l_result['ProjectEnabled']['PlanIncome'] = $PlanIncome_enabled;
        $l_result['ProjectEnabled']['Income'] = $Income_enabled;
        $l_result['ProjectEnabled']['Expense'] = $Expense_enabled;
        return $l_result;
    }
}
