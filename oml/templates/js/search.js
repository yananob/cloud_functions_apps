let searching = false;
let currentPage = 1;
let reserveInfoQueue = [];  // 予約数取得用書籍IDキュー
// let bookContentQueue = [];  // 書籍内容取得用書籍IDキュー

$(document).ready(function () {
    $(".js_enter_search").keypress(function(event) {
        if (event.key === "Enter") {
            $("#search_button").click();
        }
    });

    $("#search_button").click(function() {
        triggerSearch(1);
    });

    $("#show_next_page").click(function() {
        triggerSearch(currentPage);
    });

    $(".js_toggle_search_detail").click(function() {
        $(".js_search_detail").toggle("fast");
    });

    $("input[name=keyword]").focus();
});

function triggerSearch(startPage) {
    const keyword = $("input[name=keyword]").val();
    const title = $("input[name=title]").val();
    const author = $("input[name=author]").val();
    if (keyword === "" && title === "" && author === "") {
        return;
    }
    if ((keyword + title + author).length < 3) {
        alert("合計3文字以上入力してください。");
        return;
    }

    if (searching) {
        return;
    }
    searching = true;
    if (startPage === 1) {
        $("#searched_books").hide("normal").html("");
    }
    showProgress($("#search_button"));
    $("#area_content").show("normal");
    $("#show_next_page").hide("normal");
    search(keyword, title, author, startPage, startPage + 4);
}

function search(keyword, title, author, searchPage, endPage) {
    let searchButton = $("#search_button");
    $.ajax({
        dataType: "json",
        url: ".?cmd=json-search",
        data: {
            keyword: keyword,
            title: title,
            author: author,
            page: searchPage,
        },
    })
    .done( (data) => {
        if (data.success) {
            $("#searched_books").append(data.html).show("normal");
            attachReserveButtonEvent();
            $(".js_searched_books").show("normal");
            reserveInfoQueue.push(...data.bookIds);
            processReserveInfoQueue();
            // bookContentQueue.push(...data.bookIds);
            // processBookContentQueue();

            searchPage++;
            currentPage = searchPage;
            if ((currentPage >= endPage) || (data.html.length === 0)) {
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
    .fail( (data) => {
        alert("処理に失敗しました");
        stopProgress(searchButton);
        searching = false;
    });
}

function attachReserveButtonEvent() {
    // bind only once
    $(".js_btn_reserve").unbind("click.reserve").bind("click.reserve", function() {
        showProgress($(this));
        $.ajax({
            dataType: "json",
            url: ".?cmd=json-reserve",
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
        url: ".?cmd=json-bookreserveinfo",
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

// function processBookContentQueue() {
//     return;

//     console.log("queue length: " + bookContentQueue.length);
//     if (bookContentQueue.length === 0) {
//         setTimeout(processBookContentQueue, 2000);
//         return;
//     }
//     let bookId = bookContentQueue.shift();
//     console.log("getting info: " + bookId);
//     $.ajax({
//         dataType: "json",
//         url: ".?cmd=json-bookcontent",
//         data: {
//             bookId: bookId,
//         },
//     })
//     .done( (data) => {
//         let elem = $("#title_" + bookId);
//         elem.html("<a href='javascript:alert(\"" + data.content + "\")'>" + elem.html() + "</a>");
//         processBookContentQueue();
//     })
//     .fail( (data) => {
//         $("#message_" + bookId).html("エラー");
//     });
// }
