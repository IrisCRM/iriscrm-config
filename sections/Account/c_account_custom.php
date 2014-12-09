<?php
/**
 * Серверная логика карточки компании
 */
class c_Account_custom extends Config
{
    function __construct()
    {
        parent::__construct(array('config/common/Lib/lib.php'));
    }

    public function renderMonthCard($parameters)
    {
        $data = $this->getCustomCard(array(
            'section' => 'Test',
            //'dict' => 'TestDict', // instead of section
            //'detail' => 'TestDetail', // with section|dict
            //'parent_id' => $parent_id, // with detail
            'mode' => 'update',
            //'where' => $parameters['where'], // where condition
            'id' => $parameters['id'],
            'parameters' => $parameters['parameters'], // parameters
        ));
        $result = &$data[0];

        // Построение представления карточки
        $result['card'] = $this->renderView('card', $result);
        return $result;
    }
}
