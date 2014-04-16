<?php
/**
 * Карточка инцидента
 */
class c_Incident extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'config/common/Lib/lib.php'));
    }

    public function onChangeInformID($params)
    {
        return GetLinkedValues('Contact', $params['value'], 
                array('Account'), $this->connection);
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

    public function onChangeIssueID($params)
    {
        return GetLinkedValues('Issue', $params['value'], 
                array('Product'), $this->connection);
    }

    public function onChangeProjectID($params)
    {
        return GetLinkedValues('Project', $params['value'], 
                array('Account', 'Object', 'Contact'), $this->connection);
    }

    public function onChangeTaskID($params)
    {
        return GetLinkedValues('Task', $params['value'], 
                array('Account', 'Contact', 'Object', 'Product', 'Issue', 
                    'Marketing', 'Space', 'Project', 'Offer', 'Pact', 
                    'Invoice', 'Payment', 'FactInvoice', 'Document'), 
                $this->connection);
    }

    public function onChangeOfferID($params)
    {
        $result = GetLinkedValues('Offer', $params['value'], 
                array('Project', 'Account', 'Contact'), $this->connection);

        $id = GetArrayValueByParameter($result['FieldValues'], 'Name', 
                'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), 
                $this->connection, $result);
        return $result;
    }

    public function onChangePactID($params)
    {
        $result = GetLinkedValues('Pact', $params['value'], 
                array('Account', 'Contact', 'Project'), $this->connection);

        $id = GetArrayValueByParameter($result['FieldValues'], 'Name', 
                'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), 
                $this->connection, $result);

        return $result;
    }

    public function onChangeInvoiceID($params)
    {
        $result = GetLinkedValues('Invoice', $params['value'], 
                array('Account', 'Contact', 'Project', 'Pact'), 
                $this->connection);

        $id = GetArrayValueByParameter($result['FieldValues'], 'Name', 
                'PactID', 'Value');
        $result = GetLinkedValues('Pact', $id, 
                array('Account', 'Contact', 'Project'), 
                $this->connection, $result);

        $id = GetArrayValueByParameter($result['FieldValues'], 'Name', 
                'Contact', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), 
                $this->connection, $result);

        return $result;
    }

    public function onChangePaymentID($params)
    {
        $result = GetLinkedValues('Payment', $params['value'], 
                array('Account', 'Contact', 'Project', 'Pact', 'Invoice'), 
                $this->connection);
        
        $id = GetArrayValueByParameter($result['FieldValues'], 'Name', 
                'InvoiceID', 'Value');
        $result = GetLinkedValues('Invoie', $id, 
                array('Account', 'Contact', 'Project', 'Pact'), 
                $this->connection, $result);
        
        $id = GetArrayValueByParameter($result['FieldValues'], 'Name', 
                'PactID', 'Value');
        $result = GetLinkedValues('Pact', $id, 
                array('Account', 'Contact', 'Project'), 
                $this->connection, $result);
        
        $id = GetArrayValueByParameter($result['FieldValues'], 'Name', 
                'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), 
                $this->connection, $result);

        return $result;
    }

    public function onChangeFactInvoiceID($params)
    {
        $result = GetLinkedValues('FactInvoice', $params['value'], 
                array('Account', 'Contact', 'Project', 'Pact', 'Invoice'), 
                $this->connection);
        
        $id = GetArrayValueByParameter($result['FieldValues'], 'Name', 
                'InvoiceID', 'Value');
        $result = GetLinkedValues('Invoie', $id, 
                array('Account', 'Contact', 'Project', 'Pact'), 
                $this->connection, $result);
        
        $id = GetArrayValueByParameter($result['FieldValues'], 'Name', 
                'PactID', 'Value');
        $result = GetLinkedValues('Pact', $id, 
                array('Account', 'Contact', 'Project'), 
                $this->connection, $result);
        
        $id = GetArrayValueByParameter($result['FieldValues'], 'Name', 
                'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), 
                $this->connection, $result);

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
                array('Account', 'Contact', 'Project'), 
                $this->connection, $result);
        
        $id = GetArrayValueByParameter(
                $result['FieldValues'], 'Name', 'ProjectID', 'Value');
        $result = GetLinkedValues('Project', $id, 
                array('Account', 'Contact', 'Object'), 
                $this->connection, $result);

        return $result;
    }

    public function onChangeIncidentStateID($params) 
    {
        $StateCode = GetFieldValueByID('IncidentState', $params['value'], 
                'Code', $this->connection);

        if ($StateCode == 'Found') {
            $date = GetCurrentDBDateTime($this->connection);
            $result = FieldValueFormat('AnswerDate', $date, null, $result);
        }            
        if ($StateCode == 'Decided') {
            $date = GetCurrentDBDateTime($this->connection);
            $result = FieldValueFormat('AnswerDate', $date, null, $result);
            $result = FieldValueFormat('CompleteDate', $date, null, $result);
        }            
        return $result;
    }

}
