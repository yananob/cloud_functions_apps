{extends file='_layout.tpl'}

{block name=title}トップ{/block}

{block name=header_info}
    [更新
    {assign var="timestamp_format" value="n/j H:i"}
    {if !empty($updated_dates["reserved_books"])}予約:{$updated_dates["reserved_books"]->format($timestamp_format)}{/if},
    {if !empty($updated_dates["lending_books"])}貸出:{$updated_dates["lending_books"]->format($timestamp_format)}{/if}]
{/block}

{block name=head}
    <style>
        th, td {
            text-align: center;
        }
        tr.total td {
            font-weight: bold;
        }
    </style>
    {literal}
        <script type="text/javascript">
            function oml_login(user_id) {
                // 1)ログイン事前表示
                let w = window.open("https://web.oml.city.osaka.lg.jp/webopac/mobidf.do?cmd=init&next=mobasklst", "form-target");

                setTimeout(function () {
                    // 2)ログイン送信
                    let f = $('form[name="oml_login_' + user_id + '"]');
                    f.prop("target", "form-target");
                    f.submit();
                }, 1 * 1000);
            }

            function oml_logout() {
                // omlログアウト（別のログイン画面表示用）　※$.ajax() だとCORSエラーになるので、imgを使う
                $("#oml_logout_img").attr("src", "https://web.oml.city.osaka.lg.jp/webopac/moboff.do?mode=logout&display=moblogout&time=" + (new Date).valueOf());
            }
            // アクティブになった際の処理
            $(window).focus(oml_logout);
        </script>

    {/literal}
{/block}

{block name=content}
    <img id="oml_logout_img" src="" width="0" height="0">
    <div class="row-cols-auto">
        <table class="table table-striped table-hover table-bordered">
            <tr class="thead-light">
                <th>カード</th>
                <th>取置数 / 予約数</th>
                <th>延滞数 / 貸出数</th>
                {* <th></th> *}
            </tr>
            {assign var="total_keeping" value=0}
            {assign var="total_reserved" value=0}
            {assign var="total_overdue" value=0}
            {assign var="total_lending" value=0}
            {foreach $books as $user_id => $account_books}
                <tr>
                    {assign var="reserved_books" value=$account_books["reserved_books"]}
                    {assign var="lending_books" value=$account_books["lending_books"]}
                    <td>
                        {substr($user_id, -2)}
                    </td>
                    <td>
                        <span class='{if $account_books["count_keeping"] > 0}bg-warning{/if}'>
                        {$account_books["count_keeping"]}
                        </span>
                        /
                        <span class="{if $reserved_books|@count < 15}bg-info{/if}">
                            {$reserved_books|@count}
                        </span>
                    </td>
                    <td>
                        <span class='{if $account_books["count_overdue"] > 0}bg-warning{/if}'>
                        {$account_books["count_overdue"]}
                        </span>
                        /
                        {$lending_books|@count}
                    </td>
                    {*
                        <td>
                            <form name="oml_login_{$user_id}" method="POST" action="https://web.oml.city.osaka.lg.jp/webopac/mobidf.do?cmd=login">
                                <a id="btn_oml" class="btn btn-secondary" href="javascript:oml_login('{$user_id}')" role="button">
                                    <i class="bi bi-book"></i> oml
                                </a>
                                <input type="hidden" name="userid" value="{$user_id}">
                                <input type="hidden" name="password" value="">
                            </form>
                    </td>
                    *}
                    {assign var="total_keeping" value=$total_keeping+$account_books["count_keeping"]}
                    {assign var="total_reserved" value=$total_reserved+$reserved_books|@count}
                    {assign var="total_overdue" value=$total_overdue+$account_books["count_overdue"]}
                    {assign var="total_lending" value=$total_lending+$lending_books|@count}
                </tr>
            {/foreach}
            <tr class="total">
                <td>
                    合計
                </td>
                <td>
                    {$total_keeping} / {$total_reserved}
                </td>
                <td>
                    {$total_overdue} / {$total_lending}
                </td>
            </tr>
        </table>
    </div>
{/block}
