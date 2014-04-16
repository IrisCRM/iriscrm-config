<?php
/**
 * Серверная логика карточки дела
 */
class s_Task extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
                'config/common/Lib/lib.php',
                'config/common/Lib/access.php',
        ));
        $this->_section_name = substr(__CLASS__, 2);
    }

    public function onPrepare($params) 
    {
        // Заполняем значения по умолчанию только при создании новой записи
        if ($params['mode'] != 'insert') {
            return null;
        }

        $result = $this->prepareDetail($params);

        $con = $this->connection;

        //Значения справочников
        $result = GetDictionaryValues(
            array (
                //array ('Dict' => 'TaskType', 'Code' => 'Execute'),
                array ('Dict' => 'TaskState', 'Code' => 'Plan'),
                array ('Dict' => 'TaskImportance', 'Code' => 'Normal')
            ), 
            $con, $result);

        //Дата
        $Date = GetCurrentDBDateTime($con);
        $result = FieldValueFormat('StartDate', $Date, null, $result);

        //Ответственный    
        $result = GetDefaultOwner(GetUserName(), $con, $result);




        if ($params['card_params'] == 'undefined') {
            $params['card_params'] = null;
        }

        $card_params = json_decode($params['card_params'], true);
        if ($card_params != null) {
            // если карточку открыли через кнопку позвонить, то заполним поля компания, контакт, телефон, тип
            if ($card_params['mode'] == 'open_outcoming_call') {
                $con = db_connect();
                $sql  = "select T0.id as cid, T0.name as cname, T0.accountid as aid, T1.name as aname, null as oid, null as oname from iris_contact T0 ";
                $sql .= "left join iris_account T1 on T0.accountid = T1.id ";
                $sql .= "where ((T0.phone1=:phone or (T0.phone1 is null and :phone='')) and (T0.phone1addl=:addl or (T0.phone1addl is null and :addl=''))) ";
                $sql .= "or (T0.phone2=:phone)";
                $cmd = $con->prepare($sql, array(PDO::ATTR_EMULATE_PREPARES => true));
                $cmd->execute(array(":phone" => $card_params['phone'], ":addl" => $card_params['phoneaddl']));
                $contactinfo = $cmd->fetchAll(PDO::FETCH_ASSOC); // получим контакт и его компанию по номеру телефона
                if ($contactinfo == null) {
                    // если не нашли по контакту, то ищем по объекту
                    $sql  = "select T2.id as cid, T2.name as cname, T1.id as aid, T1.name as aname, T0.id as oid, T0.name as oname from iris_object T0 ";
                    $sql .= "left join iris_account T1 on T0.accountid = T1.id left join iris_contact T2 on T0.contactid = T2.id ";
                    $sql .= "where (((T0.phone1=:phone or (T0.phone1 is null and :phone='')) and (T0.phone1addl=:addl or (T0.phone1addl is null and :addl=''))) ";
                    $sql .= "or ((T0.phone2=:phone or (T0.phone2 is null and :phone='')) and (T0.phone2addl=:addl or (T0.phone2addl is null and :addl=''))) ";
                    $sql .= "or ((T0.phone3=:phone or (T0.phone3 is null and :phone='')) and (T0.phone3addl=:addl or (T0.phone3addl is null and :addl=''))))";
                    $cmd = $con->prepare($sql, array(PDO::ATTR_EMULATE_PREPARES => true));
                    $cmd->execute(array(":phone" => $card_params['phone'], ":addl" => $card_params['phoneaddl']));
                    $contactinfo = $cmd->fetchAll(PDO::FETCH_ASSOC); // получим компанию, контакт, объект по номеру телефона
                }
                if ($contactinfo == null) {
                    // если не нашли по контакту или объекту, то ищем по компании
                    $sql  = "select null as cid, null as cname, T0.id as aid, T0.name as aname, null as oid, null as oname from iris_account T0 ";
                    $sql .= "where (((T0.phone1=:phone or (T0.phone1 is null and :phone='')) and (T0.phone1addl=:addl or (T0.phone1addl is null and :addl=''))) ";
                    $sql .= "or ((T0.phone2=:phone or (T0.phone2 is null and :phone='')) and (T0.phone2addl=:addl or (T0.phone2addl is null and :addl=''))) ";
                    $sql .= "or ((T0.phone3=:phone or (T0.phone3 is null and :phone='')) and (T0.phone3addl=:addl or (T0.phone3addl is null and :addl=''))))";
                    $cmd = $con->prepare($sql, array(PDO::ATTR_EMULATE_PREPARES => true));
                    $cmd->execute(array(":phone" => $card_params['phone'], ":addl" => $card_params['phoneaddl']));
                    $contactinfo = $cmd->fetchAll(PDO::FETCH_ASSOC); // получим компанию по номеру телефона
                }
                
                $result = FieldValueFormat('ContactID', $contactinfo[0]['cid'], $contactinfo[0]['cname'], $result);
                $result = FieldValueFormat('AccountID', $contactinfo[0]['aid'], $contactinfo[0]['aname'], $result);
                $result = FieldValueFormat('ObjectID', $contactinfo[0]['oid'], $contactinfo[0]['oname'], $result);

                $result = FieldValueFormat('Phone', $card_params['phone'], null, $result);
                $result = FieldValueFormat('PhoneAddl', $card_params['phoneaddl'], null, $result);
                list($tasktypeid, $tasktypename) = GetFieldValuesByFieldValue('tasktype', 'code', 'Call', array('id', 'name'), $con);
                $result = FieldValueFormat('TaskTypeID', $tasktypeid, null, $result);
            }
            else
            if ($card_params['mode'] == 'addFromCalendar') {
                $result = GetDictionaryValues(
                    array (
                        array ('Dict' => 'TaskType', 'Code' => 'Execute'),
                    ), 
                    $con, $result);
            }
        }
        else {
            $result = GetDictionaryValues(
                array (
                    array ('Dict' => 'TaskType', 'Code' => 'Execute'),
                ), 
                $con, $result);

            $Local = Local::getInstance();
            $finish_date = $Local->timeToLocalDateTime($Local->dbDateToTime($Date) + 60*60*2);
            $result = FieldValueFormat('FinishDate', $finish_date, null, $result);
        }

        return $result;
    }

    public function onAfterPost($p_table, $p_id, $old_data, $new_data)
    {
        $old_owner_id = GetArrayValueByName($old_data['FieldValues'], 'ownerid');
        $new_owner_id = GetArrayValueByName($new_data['FieldValues'], 'ownerid');

        if ($old_owner_id != $new_owner_id) {
            return record_chown($p_table, $p_id, $old_owner_id, $new_owner_id, 
                    array("showMessage" => 1));
        }
    }

}
