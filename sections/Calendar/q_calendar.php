<?php
/**
 * Календарь
 */

if (!session_id()) {
    @session_start();
    if (!session_id()) {
        echo 'Невозможно создать сессию!';
    }
}
session_write_close();

$path = $_SESSION['INDEX_PATH'];
include_once $path . '/core/engine/applib.php';

$Loader = Loader::getLoader();
$Loader->loadOnce('config' . Loader::DS . 'sections' . Loader::DS . 'Calendar' 
        . Loader::DS . 'u_calendar.php');
$class = $Loader->getChildClassName('u_Calendar');
$Calendar = new $class($Loader);


SendRequestHeaders();

if (!isAuthorised()) {
    echo '<b>Не авторизован<b><br>';
    die;
}

if (strlen($_POST['_func']) == 0) {
    $response = PrintError('Имя функции не задано');
}
else {
    switch ($_POST['_func']) {
  
    case 'getEvents':
        $response = $Calendar->getEvents($_POST['start'], $_POST['end'], 
                json_decode($_POST['filters']));
        break;
    
    default:
        $response = 'Неверное имя функции: ' . $_POST['_func'];
  }
}

echo json_encode($response);
