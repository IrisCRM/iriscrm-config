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
}
