<?php
/**
 * Серверная логика карточки КП
 */
class s_Offer extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'config/common/Lib/lib.php',
        ));
        $this->_section_name = substr(__CLASS__, 2);
    }

    public function onPrepare($params, $result = null) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $result = $this->mergeFields($this->prepareDetail($params), $result);

        //Значения справочников
        $result = GetDictionaryValues(
            array(
                array('Dict' => 'OfferState', 'Code' => 'Prepare'),
                array('Dict' => 'Currency', 'Code' => 'RUB'),
            ), $this->connection, $result);

        //Ответственный    
        $UserName = GetUserName();
        $result = GetDefaultOwner($UserName, $this->connection, $result);

        //Номер
        $Number = GenerateNewNumber('OfferNumber', 'OfferNumberDate', $this->connection);
        $result = FieldValueFormat('Number', $Number, null, $result);
        $result = FieldValueFormat('Name', $Number, null, $result);
        
        //Дата
        $Date = GetCurrentDBDate($this->connection);
        $result = FieldValueFormat('Date', $Date, null, $result);

    //    $Tax = GetSystemVariableValue('Tax', $this->connection);
    //    $result = FieldValueFormat('Tax', $Tax, null, $result);

        //Получить реквизиты по умолчанию Вашей компании
        $select_sql = "select ap.ID, ap.Name "
                . "from iris_Account_Property ap, iris_Account a, iris_Contact c "
                . "where ap.AccountID = a.ID and a.ID = c.AccountID "
                . "and c.Login = :p_UserName and ap.IsMain = 1";
        $statement = $this->connection->prepare($select_sql);
        $statement->execute(array(':p_UserName' => $UserName));
        $statement->bindColumn(1, $AccountPropertyID);
        $res = $statement->fetch();

        $result = GetValuesFromTable('Account_Property', $AccountPropertyID, 
                array('Tax'), $this->connection, $result);

        return $result;
    }

    public function onAfterPost($table, $id, $old_data, $new_data)
    {
        // Если создаём запись
        if (!$old_data) {
            UpdateNumber('Offer', $id, 'OfferNumber', 'OfferNumberDate');
        }
    }

}
