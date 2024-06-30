$(document).ready(function () {
    $(".js_extend").click(function () {
        showProgress($(this));
        $.ajax({
            dataType: "json",
            url: "{$base_path}?cmd=json-extend",
            data: {
                user_id: $(this).data().userid,
                book_id: $(this).data().bookid,
            },
        })
        .done( (data) => {
            $($(this).data().message).html(data.message);
        })
        .fail( (data) => {
            showAjaxError("エラー", $($(this).data().message));
            stopProgress(this);
        });
    });
});
