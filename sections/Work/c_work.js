/**********************************************************************
Раздел "Работы". Карточка.
**********************************************************************/

var c_Work_ScriptFileName = '/config/sections/Work/c_work.php';

function c_work_card_init(p_wnd_id) {
	var form = $(p_wnd_id).getElementsByTagName("form")[0];

    form.IsAutoDateCorrection.setAttribute('disabled', 'disabled');
    form.IsCalculateProgress.setAttribute('disabled', 'disabled');
    form.IsRemind.setAttribute('disabled', 'disabled');
    form.RemindDate.setAttribute('disabled', 'disabled');

	$(form.ParentWorkID).observe('lookup:changed', function() { c_work_setNumber(form); } );


    if (form._mode.value == 'insert') {
        c_work_setNumber(form);
    }

}

function c_work_setNumber(p_form) {
    var parent_id = c_Common_GetElementValue(p_form.ParentWorkID);
    var project_id = c_Common_GetElementValue(p_form.ProjectID);
    if (((parent_id == '') || (parent_id == null)) && ((project_id == '') || (project_id == null)))
        return;

    new Ajax.Request(g_path+ c_Work_ScriptFileName, {
        parameters: {_func: "getParentInfo", parent_id: parent_id, project_id: project_id},
        onSuccess: function(transport) {
            var result = transport.responseText.evalJSON();
            p_form.Number.value = ((result.number !=null) ? result.number + '.' : '') + (parseInt(result.workcount, 10)+1);
        }
    });
}
