{extends file='_layout.tpl'}

{block name=title}予約一覧{/block}

{block name=head}
    <script type="text/javascript">
        {include file="js/reservedList.js"}
    </script>
{/block}

{block name=header_info}
    [更新
    {assign var="timestamp_format" value="n/j H:i"}
    {if !empty($updated_dates["reserved_books"])}予約:{$updated_dates["reserved_books"]->format($timestamp_format)}{/if},
    {if !empty($updated_dates["lending_books"])}貸出:{$updated_dates["lending_books"]->format($timestamp_format)}{/if}]
{/block}

{block name=content}
    {include file="_buttonUpdateList.tpl" updateCommand="update_all_reserved"}
    <div class="row-cols-auto justify-content-center">
        <table id="area_content" class="table table-striped table-hover table-bordered">
            <thead>
                <tr class="thead-light sticky-top">
                    <th>書籍名</th>
                    <th style="text-align: center">状態</th>
                    <th style="text-align: right">予約順</th>
                    <th>取置期限</th>
                    <th style="text-align: center">カード</th>
                    <th style="text-align: center"></th>
                </tr>
            </thead>
            <tbody>
                {foreach $books as $book}
                    <tr>
                        <td>{$book->title} | {$book->author}</td>
                        <td style="text-align: center">
                            <span class="{if $book->state->value === '取置中'}bg-warning{/if}">
                                {$book->state->value}
                            </span>
                        </td>
                        <td style="text-align: right">{$book->reservedOrder}</td>
                        <td>{$book->keepLimitDate|substr:5:5}</td>
                        <td style="text-align: center">{substr($book->owner, -2)}</td>
                        <td style="text-align: center" id="message_{$book->reservedBookId}">
                            <a class="btn btn-secondary btn-sm js_cancel_reservation" data-userid="{$book->owner}" data-bookid="{$book->reservedBookId}" data-progress=".js_cancel_reservation_progress_{$book->reservedBookId}" data-message="#message_{$book->reservedBookId}" role="button">
                                取消
                            </a>
                            <button class="btn btn-secondary btn-sm js_cancel_reservation_progress_{$book->reservedBookId}" style="display: none" type="button" disabled data-bookid="{$book->reservedBookId}">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            </button>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>

    {literal}
    <script type="text/javascript">
        $(document).ready(function() {
            $('#area_content').tablesorter({sortList: [[3, 0], [2, 0], [0, 0], [4, 0]]});
        });
    </script>
    {/literal}
{/block}
