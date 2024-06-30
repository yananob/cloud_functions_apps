{extends file='_layout.tpl'}

{block name=title}貸出一覧{/block}

{block name=head}
    <script type="text/javascript">
        {include file="js/lendingList.js"}
    </script>
{/block}

{block name=header_info}
    [更新
    {assign var="timestamp_format" value="n/j H:i"}
    {if !empty($updated_dates["reserved_books"])}予約:{$updated_dates["reserved_books"]->format($timestamp_format)}{/if},
    {if !empty($updated_dates["lending_books"])}貸出:{$updated_dates["lending_books"]->format($timestamp_format)}{/if}]
{/block}

{block name=content}
    {include file="_buttonUpdateList.tpl" updateCommand="update_all_lending"}
    <div class="row-cols-auto justify-content-center">
        <table id="area_content" class="table table-striped table-hover table-bordered">
            <thead>
                <tr class="thead-light sticky-top">
                    <th>書籍名</th>
                    <th style="text-align: center">状態</th>
                    <th style="text-align: center">返却期限</th>
                    <th style="text-align: center">カード</th>
                    <th style="text-align: center"></th>
                </tr>
            </thead>
            <tbody>
                {foreach $books as $book}
                    <tr>
                        <td>{$book->title} | {$book->author}</td>
                        <td style="text-align: center">{$book->state->value}</td>
                        <td style="text-align: center">
                            <span class="{if $book->isReturndateCame()}bg-warning{/if}">
                                {substr($book->returnLimitDate, 5, 5)}
                            </span>
                        </td>
                        <td style="text-align: center">{substr($book->owner, -2)}</td>
                        <td style="text-align: center" id="message_{$book->lendingBookId}">
                            {if $book->isExtendable()}
                                <a class="btn btn-secondary btn-sm js_extend" data-userid="{$book->owner}" data-bookid="{$book->lendingBookId}" data-progress=".js_extend_progress_{$book->lendingBookId}" data-message="#message_{$book->lendingBookId}" role="button">
                                    延長
                                </a>
                                <button class="btn btn-secondary btn-sm js_extend_progress_{$book->lendingBookId}" style="display: none" type="button" disabled data-bookid="{$book->lendingBookId}">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                </button>
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>

    {literal}
    <script type="text/javascript">
        $(document).ready(function() {
            $('#area_content').tablesorter({sortList: [[2, 0], [1, 0], [0, 0], [3, 0]]});
        });
    </script>
    {/literal}
{/block}
