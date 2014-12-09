<?php
/**
 * Серверная логика таблицы
 */
class ds_Test_Days_custom extends Config
{
    public function onPrepareGrid($parameters)
    {
        $parameters['parameters']['testparam'] = 1;
        return $parameters;
    }
}
