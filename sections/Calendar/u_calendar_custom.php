<?php
/**
 * Календарь
 */
require_once dirname(__FILE__) . '/u_calendar.php';

class u_Calendar_custom extends u_Calendar
{

    // Переопределяем метод получения списка дел
    protected function getTasks($from, $to, $id, $where, $params) {
        $sql  = "select T0.id as id, "
                //. "WT.code as worktype, WT.color as worktypecolor, "
                . "T0.name as title, "
                . "to_char(startdate, 'YYYY-MM-DDThh24:MI:SS+00:00') as start, "
                . "to_char(finishdate, 'YYYY-MM-DDThh24:MI:SS+00:00') as end, "
                . "TT.code as type, TI.code as importance, "
                . "TS.code as state, TR.code as result, "
                . "case when T0.ownerid = :user_id then 1 else 0 end as my_task, "
                . "T0.ownerid as userid "
                . "from " . $this->_DB->tableName("{task}") ." T0 "
                //. "left join " . $this->_DB->tableName('{worktype}') . " WT "
                    //. "on T0.worktypeid = WT.id "
                . "left join " . $this->_DB->tableName('{tasktype}') . " TT "
                    . "on T0.tasktypeid = TT.id "
                . "left join " . $this->_DB->tableName('{taskimportance}') . " TI " 
                    . "on T0.taskimportanceid = TI.id "
                . "left join " . $this->_DB->tableName('{taskstate}') . " TS "
                    . "on T0.taskstateid = TS.id "
                . "left join " . $this->_DB->tableName('{taskresult}') . " TR "
                    . "on T0.taskresultid = TR.id ";
        $params[':user_id'] = $this->_User->property('id');

        if ($this->isCheckAccess($this->_DB->tableName('{task}'))) {
            $sql .= "left join " . $this->_DB->tableName('{task_access}') . " PG " 
                        . "on (PG.RecordID = T0.ID and PG.AccessRoleID = :role_id) "
                    . "left join " . $this->_DB->tableName('{task_access}') . " PU "
                        . "on (PU.RecordID = T0.ID and PU.ContactID = :user_id) ";
            $params[':role_id'] = $this->_User->getUserRoleId();
        }
        if ($id == null) {
            $sql .= "where " . $where . " "
                    . "and startdate::date <= to_date(:to, 'YYYY-MM-DD') "
                    . "and finishdate::date >= to_date(:from, 'YYYY-MM-DD') ";
            $params[':from'] = $from;
            $params[':to'] = $to;
        }
        else {
            $sql .= "where " . $where . " and T0.id = :id ";
            $params[':id'] = $id;
        }
        if ($this->isCheckAccess($this->_DB->tableName('{task}'))) {
            $sql .= "and ((PU.R is not null and PU.R = '1') "
                    . "or (PU.R is null and PG.R = '1'))";
        }
        $sql .= ' and (T0.hidefromcalendar is null or T0.hidefromcalendar = 0)';

        $tasks = $this->_DB->exec($sql, $params);

        return $tasks;
    }

    protected function createEventFromTask($task) {
        // Обработчик родительского класса
        $event = parent::createEventFromTask($task);

        //$event["color"] = $task["worktypecolor"];

        return $event;
    }

    /**
     * Переопределение метода для получения списка пользователей
     * (брать из раздела Исполнители).
     */
    public function getUsers($params) {
        $condition = '';
        $filters = array();
        foreach ($params['filters'] as $filter) {
            // Если для фильтра номер 6 установлено значение
            if ($filter[0] == 6 && $filter[1]) {
                $condition .= 'and c1.id = :userid ';
                $filters[':userid'] = $filter[1];
            }
        }
        $sql = "select c1.id as id, c1.name as name " 
                . "from " . $this->_DB->tableName('{Contact}') . " c1 "
                . "left join " . $this->_DB->tableName('{ContactType}') . " ct1 "
                    . "on ct1.id = c1.contacttypeid "
                . "where ct1.code = 'Your' $condition"
                . "order by c1.name";
        $users = $tasks = $this->_DB->exec($sql, $filters);

        return $users;
    }
}
