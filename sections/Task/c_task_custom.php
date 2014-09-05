<?php
/**
 * Карточка дела
 */
require_once dirname(__FILE__) . Loader::DS . 'c_task.php';

class c_Task_custom extends c_Task
{
    protected function _useFilters(&$params, &$filter, $names)
    {
    	$where = '';
        foreach ($names as $name) {
            if (!empty($params[$name])) {
                $filter[':' . $name] = $params[$name];
                $where .= ($where ? ' and ' : '') 
                        . 't0.' . $name . " like '%' || :$name || '%'";
            }
        }
        return $where ? 'where ' . $where : '';
    }

    public function getGridWithContactList($params)
    {
        // Описание колонок, которые будут отображаться в таблице
        $columns = array(
            'name' => array(
                'caption' => 'ФИО',
                'type' => 'string',
                'sort' => 'asc',
                'width' => '40%',
            ),
            'phone1' => array(
                'caption' => 'Телефон',
                'type' => 'string',
                'width' => '20%',
            ),
            'phone2' => array(
                'caption' => 'Мобильный',
                'type' => 'string',
                'width' => '20%',
            ),
            'email' => array(
                'caption' => 'E-mail',
                'type' => 'string',
                'width' => '20%',
            ),
        );

        // Выбираем данные для отображеия в таблице
        $sql = "select t0.id as id, 
                t0.name as name, 
                t0.phone1 as phone1,
                t0.phone2 as phone2,
                t0.email as email
                from iris_contact t0
                #where#
                order by t0.name
                limit 20
        ";

        $where = '';
        $filter = array();
        $where = $this->_useFilters($params, $filter, 
        		array('name', 'phone1', 'phone2', 'email'));
        $sql = str_replace('#where#', $where, $sql);
        $values = $this->_DB->exec($sql, $filter);
        $parameters = array(
            'grid_id' => 'custom_grid_'. md5(time() . rand(0, 10000)),
        );

        // Для того, чтобы скрыть кнопки пользовательского грида
        $parameters['is_custom'] = false;
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
