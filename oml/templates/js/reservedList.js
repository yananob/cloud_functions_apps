$(document).ready(function () {
    $(".js_reserve_again").click(function() {
        if (!confirm("一度取消して再予約しますか？")) {
            return;
        }
        showProgress($(this));
        $.ajax({
            dataType: "json",
            url: "{$base_path}?cmd=json-reserveagain",
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

    $(".js_cancel_reservation").click(function() {
        if (!confirm("取消しますか？")) {
            return;
        }
        showProgress($(this));
        $.ajax({
            dataType: "json",
            url: "{$base_path}?cmd=json-cancelreservation",
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
