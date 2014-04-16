<?php
ini_set('display_errors', 'on');

if (!session_id()) {
	@session_start();
	if (!session_id()) {
		echo 'Невозможно создать сессию!';
	}			
}
// miv 20.05.2009: Заканчивам текущую сессию и сохраняем данные сессии
// Поскольку данные сессии блокируются для предотвращения конкурирующей записи, только один скрипт может работать с сессией в данный момент времени
// Данные сессии нам нужны тоьлко для чтения, поэтому сразу зарываем сессию
session_write_close();

$path = $_SESSION['INDEX_PATH'];

include_once $path.'/core/engine/applib.php';
include_once $path.'/config/sections/Email/lib/common.php';
include_once $path.'/config/sections/Email/lib/send_lib.php';

SendRequestHeaders();

if (isset($_POST['id'])) {
	echo send_email_message($_POST['id'], $_POST['send_mode']);
}

?>
