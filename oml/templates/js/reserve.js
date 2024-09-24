let searchedTimestamp;
let currentPage = 1;
let reserveInfoQueue = [];  // 予約数取得用書籍IDキュー

$(document).ready(function () {
    $(".js_enter_search").keypress(function(event) {
        if (event.key === "Enter") {
            $("#search_button").focus();
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

    searchedTimestamp = Date.now();
    if (startPage === 1) {
        $("#books_list").hide("normal").html("");
    }
    showProgress($("#search_button"));
    $("#area_content").show("normal");
    $("#show_next_page").hide("normal");
    reserveInfoQueue = [];  // TODO: もっと抽象化
    search(keyword, title, author, startPage, startPage + 3);
}

function search(keyword, title, author, searchPage, endPage) {
    let searchButton = $("#search_button");
    let triggeredTimestamp = searchedTimestamp;
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
        // 新たな検索が実行されていたら、何もせず終了する
        if (triggeredTimestamp != searchedTimestamp) {
            return;
        }
        if (data.success) {
            $("#books_list").append(data.html).show("normal");
            attachEventsToBookList();
            $(".js_books_list").show("normal");
            reserveInfoQueue.push(...data.bookIds);
            processReserveInfoQueue();

            searchPage++;
            currentPage = searchPage;
            if ((currentPage > endPage) || (data.html.length === 0)) {
                stopProgress(searchButton);
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
        }
    })
        .fail((data) => {
            // 新たな検索が実行されていたら、何もせず終了する
            if (triggeredTimestamp != searchedTimestamp) {
                return;
            }
            alert("処理に失敗しました");
            stopProgress(searchButton);
        });
}

function showList(clickedLink) {
    // reset active
    $(".js_show_list").each(function (index) {
        if ($(this).hasClass("bg-success")) {
            $(this).removeClass("bg-success").addClass("bg-primary");
        }
    });
    clickedLink.removeClass("bg-primary").addClass("bg-success");
    $("#books_list").hide("normal").html("");
    showProgress(clickedLink, false);
    $("#area_content").show("normal");
    $("#show_next_page").hide("normal");
    reserveInfoQueue = [];  // TODO: もっと抽象化
    $.ajax({
        dataType: "json",
        url: "{$base_path}?cmd=json-showlist",
        data: {
            type: clickedLink.data().type,
            category: clickedLink.data().category,
        },
    }).done((data) => {
        if (data.success) {
            $("#books_list").append(data.html).show("normal");
            attachEventsToBookList();
            $(".js_books_list").show("normal");
            reserveInfoQueue.push(...data.bookIds);
            processReserveInfoQueue();
        }
        else {
            alert(data.message);
        }
        stopProgress(clickedLink);
        return;
    }).fail((data) => {
        alert("処理に失敗しました");
        stopProgress(clickedLink);
    });
}

function attachEventsToBookList() {
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

    $(".js_search_with_author").unbind("click.search_with_author").bind("click.search_with_author", function () {
        $(".js_switch_tab:first").click();
        $("input[name=keyword]").val("");
        $("input[name=title]").val("");
        $("input[name=author]").val($(this).data().author);
        $("#search_button").click();
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
