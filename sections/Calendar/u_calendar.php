<?php
/**
 * Календарь
 */
class u_Calendar extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'config/common/Lib/lib.php',
            'config/common/Lib/access.php',
        ));
    }

    public function moveEvent($params) {
        $id = $params['id'];
        $start = $params['start'];

        $permissions = $this->_User->getAccessToRecord('{task}', $id);
        if ($permissions['w'] == 0) {
            return array("isOk" => false);
        }

        $sql  = "update " . $this->_DB->tableName('{task}') . " "
                . "set _field_ = _field_ + " 
                    . "(to_timestamp(:newstartdate, 'YYYY-MM-DDThh24:MI:SS') " 
                    . "- startdate) "
                . "where id = :id "
                . "and _field_ is not null";

        $fileds = array('finishdate', 'reminddate', 'startdate'); // startdate MUST be last
        foreach ($fileds as $field) {
            $tmpsql = str_replace('_field_', $field, $sql);
            $cmd = $this->connection->prepare($tmpsql);
            $cmd->execute(array(
                ":id" => $id,
                ":newstartdate" => $start
            ));
            $code = $cmd->errorInfo();

            if ($code[0] != "00000") {
                return array("isOk" => false);
            }
        }

        return array("isOk" => true);
    }


    public function resizeEvent($params) {
        $permissions = $this->_User->getAccessToRecord('{task}', $id);
        if ($permissions['w'] == 0) {
            return array("isOk" => false);
        }

        $sql  = "update " . $this->_DB->tableName('{task}') . " "
                . "set finishdate = to_timestamp(:end, 'YYYY-MM-DDThh24:MI:SS') "
                . "where id = :id";

        $cmd = $this->connection->prepare($sql);
        $cmd->execute(array(
            ":id" => $params['id'],
            ":end" => $params['end'],
        ));
        $code = $cmd->errorInfo();

        return array("isOk" => $code[0] == "00000");
    }

    public function getEventById($params) {
        $tasks = $this->getTasks(null, null, $params['id'], '(1=1)', array());
        $events = $this->formatTasksToEvents($tasks);

        return $events[0];
    }

    protected function getTasks($from, $to, $id, $where, $params) {
        $sql  = "select T0.id as id, "
                . "T0.name as title, "
                . "to_char(startdate, 'YYYY-MM-DDThh24:MI:SS+00:00') as start, "
                . "to_char(finishdate, 'YYYY-MM-DDThh24:MI:SS+00:00') as end, "
                . "TT.code as type, TI.code as importance, "
                . "TS.code as state, TR.code as result, "
                . "case when T0.ownerid = :user_id then 1 else 0 end as my_task "
                . "from " . $this->_DB->tableName("{task}") ." T0 "
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
                    . "and startdate::date >= to_date(:from, 'YYYY-MM-DD') "
                    . "and startdate::date <= to_date(:to, 'YYYY-MM-DD') ";
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

    protected function formatTasksToEvents($tasks) {
        $events = array();
        foreach ($tasks as $task) {
            $events[] = $this->createEventFromTask($task);
        }

        return $events;
    }

    protected function createEventFromTask($task) {
        $event = array(
            "id" => $task["id"],
            "title" => $task["title"],
            "start" => $task["start"],
            "end" => $task["end"]
        );

        if ($task["importance"] == "High") {
            $event["color"] = "#ff9966";
            $event["textColor"] = "#404040";
        }

        if ($task["importance"] == "Highest") {
            $event["color"] = "#eaa0a0";
            $event["textColor"] = "#404040";
        }

        if ($task["state"] == "Finished" or
                $task["state"] == "Future" or
                $task["state"] == "Canceled") {
            $event["color"] = "#afb2b4";
        }

        if (!$task["my_task"]) {
            $event["color"] = "#dddddd";
            $event["textColor"] = "#404040";
        }

        if ($task["importance"] == "Highest" or
            $task["importance"] == "High") {
          // TODO
        }

        return $event;
    }

    protected function isCheckAccess($table) {
        return TableHasRecordAccessEnabled($table) == 1 &&
                !$this->_User->isAdmin();
    }

    public function generateEventId() {
        return array("id" => create_guid());
    }

    public function getEvents($from, $to, $filters) {
        if (count($filters) == 0) {
            return array(); // if filters not set then return empty array
        }

        $filter = $this->getFilterCondition('CalendarFilter', $filters);

        $tasks = $this->getTasks($from, $to, null, 
                $filter['where'], $filter['parameters']);
        $events = $this->formatTasksToEvents($tasks);

        return $events;
    }

    public function getFiltersHTML()
    {
        $data = $this->getCustomFilters('CalendarFilter');
        $result = array(
            'Filters' => $this->renderView('filter', $data),
        );
        return $result;
    }

}
