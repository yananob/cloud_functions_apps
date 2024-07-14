// common.js

function run(command, params) {
    url = new URL(location.href);
    $("body").append($("<form name='formJq' method='POST' action='" + url.pathname + "'>"));
    let $form = $("form[name=formJq]");
    $form.append($("<input type='hidden' name='cmd'>").val(command));
    for (const k in params) {
        $form.append($("<input type='hidden'>").attr("name", k).val(params[k]));
    }
    $form.submit();
}

// function initProgress(obj) {
//     let myId = "#" + obj.attr("id");
//     $(myId + '_progress').css('display', 'none');
//     $(myId).css('display', 'inline-block');
//     $('#area_content').css('display', 'block');
// }

function showProgress(obj, hideobj = true) {
    // data-progressのエレメントを表示
    $($(obj).data().progress).show();
    if (hideobj) {
        $(obj).hide();
    }
}

function stopProgress(obj) {
    // data-progressのエレメントを非表示
    $($(obj).data().progress).hide();
    $(obj).show();
}

function showAjaxError(message, messageAreaObj) {
    messageAreaObj.html(message);
    alert(message);
    $(document.body).animate({
        scrollTop: messageAreaObj.offset().top - 100
    });
}
