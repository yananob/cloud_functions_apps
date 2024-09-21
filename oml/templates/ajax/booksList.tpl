{foreach $books as $book}
    <tr class="js_books_list" style="display: none">
        <td id="title_{$book->reservedBookId}">
            <a class="btn btn-secondary btn-sm" href="https://www.oml.city.osaka.lg.jp/?page_id=266#catdbl-{$book->reservedBookId}" target="_blank">内容</a>
            {$book->title} | <a href="#" class="js_search_with_author" data-author="{$book->authorForSearch}">{$book->author}</a>
            |
            {$book->publishedYear}
        </td>
        <td class="text-end" id="reserves_{$book->reservedBookId}">
            <div class="spinner-border spinner-border-sm text-info" role="status"></div>
        </td>
        <td class="text-end" id="waitWeeks_{$book->reservedBookId}">
            <div class="spinner-border spinner-border-sm text-info" role="status"></div>
        </td>
        <td class="text-center" id="message_{$book->reservedBookId}">
            <a class="btn btn-secondary btn-sm js_btn_reserve" data-bookid="{$book->reservedBookId}" data-progress=".js_btn_reserve_progress_{$book->reservedBookId}" data-message="#message_{$book->reservedBookId}" role="button">
                予約
            </a>
            <button class="btn btn-secondary btn-sm js_btn_reserve_progress_{$book->reservedBookId}" style="display: none" disabled>
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            </button>
        </td>
    </tr>
{/foreach}