<?php
/**
 * Серверная логика карточки дела
 */
require_once dirname(__FILE__) . Loader::DS . 's_task.php';

class s_Task_custom extends s_Task
{

    public function onBeforePostContactID($params)
    {
        $id = $this->fieldValue($params['old_data'], 'ContactID');
        $result = $this->getLinkedValues('{Contact}', $id, 
                array('{{Account}}', '{{Object}}', 'GenderID', 'name', 
                'Phone1', 'phone2', 'email', 'donotcall'));

        $value = $this->fieldValue($result, 'GenderID');
        $this->removeField($result, 'GenderID');
        $this->mergeFields($result, $this->formatField('clientgenderid', $value));

        $value = $this->fieldValue($result, 'name');
        $this->removeField($result, 'name');
        $this->mergeFields($result, $this->formatField('clientname', $value));
        $this->mergeFields($result, $this->formatField('ContactID', $id, $value));

        $value = $this->fieldValue($result, 'Phone1');
        $this->removeField($result, 'Phone1');
        $this->mergeFields($result, $this->formatField('Phone', $value));

        $value = $this->fieldValue($result, 'email');
        $this->removeField($result, 'email');
        $this->mergeFields($result, $this->formatField('clientemail', $value));

        $value = $this->fieldValue($result, 'donotcall');
        $this->removeField($result, 'donotcall');
        $this->mergeFields($result, $this->formatField('clientdonotcall', $value));

        return $result;
    }

    public function onAfterPost($table, $id, $old_data, $new_data)
    {
        $result = parent::onAfterPost($table, $id, $old_data, $new_data);

        $name = $this->fieldValue($new_data, 'clientname');
        $phone1 = $this->fieldValue($new_data, 'phone');
        $phone2 = $this->fieldValue($new_data, 'phone2');
        $email = $this->fieldValue($new_data, 'clientemail');
        $genderid = $this->fieldValue($new_data, 'clientgenderid');
        $donotcall = $this->fieldValue($new_data, 'clientdonotcall');
        $contactid = $this->fieldValue($new_data, 'contactid');

        // Если id контакта не указано, но указано ФИО, то создаем нового контакта
        if (!$contactid && $name) {
            $this->mergeFields($contact, $this->formatField('name', $name));
            $this->mergeFields($contact, $this->formatField('phone1', $phone1));
            $this->mergeFields($contact, $this->formatField('phone2', $phone2));
            $this->mergeFields($contact, $this->formatField('email', $email));
            $this->mergeFields($contact, $this->formatField('genderid', $genderid));
            $this->mergeFields($contact, $this->formatField('donotcall', $donotcall));
            // Создаем карточку контакта с учетом прав доступа
            $record = $this->saveRecord($contact, array(
                'source_name' => 'Contact',
            ));
            // Обновляем поле Контакт в картчоке созданного дела
            if ($record[0]['record_id']) {
                $update = $this->formatField('contactid', $record[0]['record_id']);
                //TODO
                $this->saveRecord($update, array(
                    'mode' => 'update',
                    'id' => $id,
                    'source_name' => 'Task',
                ));
            }
        }
        else if ($contactid) {
            // Обновим информацию о контакте
            $contact_fields = array('name', 'phone1', 'phone2', 'email', 'genderid', 'donotcall');
            $contact_old = $this->_DB->getRecord(
                    $contactid, '{Contact}', $contact_fields);
            $contact = null;
            foreach ($contact_fields as $field) {
                if ($$field && $contact_old[$field] != $$field) {
                    $this->mergeFields($contact, $this->formatField($field, $$field));
                }
            }
            if ($contact) {
                $record = $this->saveRecord($contact, array(
                    'mode' => 'update',
                    'id' => $contactid,
                    'source_name' => 'Contact',
                ));
            }
        }

        return $result;
    }
}
