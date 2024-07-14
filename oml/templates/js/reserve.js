let searching = false;
let currentPage = 1;
let reserveInfoQueue = [];  // 予約数取得用書籍IDキュー

$(document).ready(function () {
    $(".js_enter_search").keypress(function(event) {
        if (event.key === "Enter") {
            $("#search_button").click();
        }
    });

    $("#search_button").click(function() {
        triggerSearch(1);
    });

    $(".js_show_list").click(function () {
        showList($(this));
    });

    $("#show_next_page").click(function() {
        triggerSearch(currentPage);
    });

    $(".js_toggle_search_detail").click(function() {
        $(".js_search_detail").toggle("fast");
    });

    $(".js_switch_tab").click(function () {
        const selectedTab = $(this).data().tab;
        $(".js_switch_tab").each(function (_, element) {
            const currentTab = $(element).data().tab;
            if ($(element).data().tab === selectedTab) {
                $("#tab-" + currentTab).show();
                $(element).addClass("active");
            } else {
                $("#tab-" + currentTab).hide();
                $(element).removeClass("active");
            }
        });
    });

    $(".js_switch_tab:first").click();
    $("input[name=keyword]").focus();
});

function triggerSearch(startPage) {
    const keyword = $("input[name=keyword]").val();
    const title = $("input[name=title]").val();
    const author = $("input[name=author]").val();
    if (keyword === "" && title === "" && author === "") {
        return;
    }
    if ((keyword + title + author).length < 2) {
        alert("合計2文字以上入力してください。");
        return;
    }

    if (searching) {
        return;
    }
    searching = true;
    if (startPage === 1) {
        $("#books_list").hide("normal").html("");
    }
    showProgress($("#search_button"));
    $("#area_content").show("normal");
    $("#show_next_page").hide("normal");
    search(keyword, title, author, startPage, startPage + 3);
    // search(keyword, title, author, startPage, startPage + 1);
}

function search(keyword, title, author, searchPage, endPage) {
    let searchButton = $("#search_button");
    $.ajax({
        dataType: "json",
        url: "{$base_path}?cmd=json-search",
        data: {
            keyword: keyword,
            title: title,
            author: author,
            page: searchPage,
        },
    })
    .done( (data) => {
        if (data.success) {
            $("#books_list").append(data.html).show("normal");
            attachReserveButtonEvent();
            $(".js_books_list").show("normal");
            reserveInfoQueue.push(...data.bookIds);
            processReserveInfoQueue();

            searchPage++;
            currentPage = searchPage;
            if ((currentPage > endPage) || (data.html.length === 0)) {
                stopProgress(searchButton);
                searching = false;
                if ((searchPage >= endPage) && (data.html.length !== 0)) {
                    $("#show_next_page").show("normal");
                }
                return;
            }
            search(keyword, title, author, searchPage, endPage);
        }
        else {
            alert(data.message);
            stopProgress(searchButton);
            searching = false;
        }
    })
        .fail((data) => {
            alert("処理に失敗しました");
            stopProgress(searchButton);
            searching = false;
        });
}

function showList(clickedLink) {
    showProgress(clickedLink, false);
    $("#area_content").show("normal");
    $("#show_next_page").hide("normal");
    $.ajax({
        dataType: "json",
        url: "{$base_path}?cmd=json-showlist",
        data: {
            lv2: clickedLink.data().lv2,
        },
    }).done((data) => {
        if (data.success) {
            $("#books_list").append(data.html).show("normal");
            $(".js_books_list").show("normal");
            reserveInfoQueue.push(...data.bookIds);
            processReserveInfoQueue();
        }
        else {
            alert(data.message);
        }
        stopProgress(clickedLink);
        searching = false;
        return;
    }).fail((data) => {
        alert("処理に失敗しました");
        stopProgress(clickedLink);
        searching = false;
    });
}

function attachReserveButtonEvent() {
    // bind only once
    $(".js_btn_reserve").unbind("click.reserve").bind("click.reserve", function() {
        showProgress($(this));
        $.ajax({
            dataType: "json",
            url: "{$base_path}?cmd=json-reserve",
            data: {
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
}

function processReserveInfoQueue() {
    // console.log("queue length: " + reserveInfoQueue.length);
    if (reserveInfoQueue.length === 0) {
        setTimeout(processReserveInfoQueue, 2000);
        return;
    }
    let bookId = reserveInfoQueue.shift();
    // console.log("getting info: " + bookId);
    $.ajax({
        dataType: "json",
        url: "{$base_path}?cmd=json-bookreserveinfo",
        data: {
            bookId: bookId,
        },
    })
    .done( (data) => {
        $("#reserves_" + bookId).html(data.reserves);
        $("#waitWeeks_" + bookId).html(data.waitWeeks);
        processReserveInfoQueue();
    })
    .fail( (data) => {
        $("#message_" + bookId).html("エラー");
    });
}
