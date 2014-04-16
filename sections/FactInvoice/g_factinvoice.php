<?php

/**
 * Таблица накладных
 */
class g_FactInvoice extends Config
{
    public function __construct($Loader)
    {
        parent::__construct($Loader, array(
            'config/common/Lib/lib.php', 
            'config/common/Lib/access.php',
            'config/sections/Payment/s_payment.php',
        ));
    }

    /**
     * Создание платежа из Накладной
     */
    public function createPayment($params)
    {
        $p_id = $params['id'];
        $con = $this->connection;

        $factinvoice_info = GetFormatedFieldValuesByFieldValue('FactInvoice', 'ID', $p_id, array(
            'AccountID', 'ContactID', 'CurrencyID', 'ProjectID', 'PactID', 'InvoiceID','Amount'), $con);
        //Переименуем Amount -> PlanAmount
        SetArrayValueByParameter($factinvoice_info['FieldValues'], 'Name', 'Amount', 'Name', 'PlanAmount');

        $Loader = Loader::getLoader();
        $Payment = new s_Payment($Loader);
        $payment_values = $Payment->onPrepare(array('mode' => 'insert'), $factinvoice_info);
        //$payment_values = Payment_GetDefaultValues($factinvoice_info);

        $payment_id = create_guid();
        $payment_values = FieldValueFormat('ID', $payment_id, null, $payment_values);
        $payment_values = FieldValueFormat('FactInvoiceID', $p_id, null, $payment_values);

        //Вставка записи
        InsertRecord('Payment', $payment_values['FieldValues'], $con);
        $number = UpdateNumber('Payment', $payment_id, 'PaymentNumber', 'PaymentNumberDate');

        //miv 30.03.2010 Проставим права доступа к платежу (как у родительской записи)
        include_once GetPath().'/config/common/Lib/access.php';
        GetRecordPermissions('iris_FactInvoice', $p_id, $permissions, $con);
        ChangeRecordPermissions('iris_Payment', $payment_id, $permissions, $con);

        return $number;
    }
}
