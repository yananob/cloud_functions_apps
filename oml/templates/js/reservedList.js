$(document).ready(function () {
    $(".js_cancel_reservation").click(function() {
        showProgress($(this));
        $.ajax({
            dataType: "json",
            url: ".?cmd=json-cancelreservation",
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
