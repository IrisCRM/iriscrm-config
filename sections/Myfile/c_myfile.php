<?php
/**
 * Серверная логика карточки файла
 */
class c_Myfile extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
                'config/common/Lib/lib.php',
        ));
    }

    public function onChangeContactID($params)
    {
        return GetLinkedValues('Contact', $params['value'], 
                array('Account', 'Object'), $this->connection);
    }

    public function onChangeProjectID($params)
    {
        return GetLinkedValues('Project', $params['value'], 
                array('Account', 'Object', 'Contact'), $this->connection);
    }

}
