<?php

function SendProjectStageNotify($project_id) {
	$con = db_connect();
	// получим информацию по проекту
	$cmd = $con->prepare("select T0.id as projectid, T0.number as number, T1.code as stage, T1.name as stagename, T2.email as email, T2.isnotify as isnotify 
							from iris_project T0 
							left join iris_projectstage T1 on T0.projectstageid = T1.id 
							left join iris_contact T2 on T0.contactid = T2.id 
							where T0.id=:id");
	$cmd->execute(array(":id" => $project_id));
	$project_info = current($cmd->fetchAll(PDO::FETCH_ASSOC));
//	print_r($project_info);

	if ($project_info['isnotify'] != 1)
		return 'notify disabled'; // если у ответственного уведомления отключены, то выйдем

	if ($project_info['email'] == '')
		return 'recipient is not have a email'; // если у получателя не указан email
//return 'under construction';
	// в зависимости от состояния проекта выберем форму для email-уведомления
   	$printform_code = 'project-notify-client';
/*   	
	switch ($project_info['stage']) {
		case 'Payment':
			$printform_code = 'project-notify-client-pay';
			break;
		case 'Execution':
			$printform_code = 'project-notify-client-execute';
			break;
		case 'Finished':
			$printform_code = 'project-notify-client-finish';
			break;
		default:
			$printform_code = '';
	}
*/
	if ($printform_code == '')
		return 'not notifyed state'; // если стадия != оплата, выполнение, завершен, то уведомление не отсылается
	
	$printform = current($con->query("select id from iris_printform where code='".$printform_code."'")->fetchAll(PDO::FETCH_ASSOC));
	// в message - заполненая печатная форма
	require_once GetPath().'/core/engine/printform.php';
	$message = FillForm($printform['id'], $project_info['projectid']);
	
	// заменим в форме поля [cabinet]
	$admin_info = current($con->query("select email from iris_contact T0 left join iris_accessrole T1 on T0.accessroleid=T1.id where T1.code in ('admin') order by email")->fetchAll());
	if ($admin_info['email'] != '')
		$admin_info['email'] = $admin_info['email'];
	$message = iris_str_replace('[admin_email]', $admin_info['email'], $message);
	// заменим в форме поле [admin_email]
	$optxml = simplexml_load_file(realpath('./../../../site/options.xml'));
	$message = iris_str_replace('[cabinet]', (string)$optxml->INDEX_LINK.'/enter.php', $message);	

	$stagename = explode('. ', $project_info['stagename']);
	$stagename = $stagename[1]; // стадия заказа бец цифры для темы письма
	$subject = 'Заказ '.$project_info['number'].' переведен на стадию "'.$stagename.'"';
	
	require_once GetPath().'/config/common/Lib/mailfunc.php';
	$email_params = get_mail_settings(realpath('./../../../site/options.xml'));

	$res = SendEmail($email_params, $project_info['email'], $subject, $message);
	if ($res != 0)
		return 'mail error';
	
	return 'ok';
}

?>