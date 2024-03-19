{extends file='_layout.tpl'}

{block name=title}検索{/block}

{block name=head}
    <script type="text/javascript">
        {include file="js/search.js"}
    </script>
{/block}

{block name=header_info}
    [予約可能数: {$totalReservableCount}]
{/block}

{block name=content}
    <form onsubmit="return false;">
        <div class="row mb-1">
            <div class="col-8">
                <input type="search" name="keyword" class="form-control js_enter_search" placeholder="キーワード (3文字以上)">
            </div>
            <button type="button" class="btn btn-primary col-3" id="search_button" data-progress=".js_search_button_progress">
                <i class="bi bi-search"></i> 検索
            </button>
            <button type="button" class="btn btn-primary col-3 js_search_button_progress" disabled style="display: none">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            </button>
            <div class="col-1 align-self-center">
                <label class="form-check-label js_toggle_search_detail js_search_detail" for="flexCheckDefault">
                    <i class="bi bi-arrows-expand"></i>
                </label>
                <label class="form-check-label js_toggle_search_detail js_search_detail" for="flexCheckDefault" style="display: none">
                    <i class="bi bi-arrows-collapse"></i>
                </label>
            </div>
        </div>
        <div class="row mb-3 js_search_detail" style="display: none">
            <div class="col-6">
                <input type="search" name="title" class="form-control js_enter_search" placeholder="タイトル">
            </div>
            <div class="col-6">
                <input type="search" name="author" class="form-control js_enter_search" placeholder="著者名">
            </div>
        </div>
        <input type="hidden" name="max_page" value="{if $is_local}2{else}4{/if}">
    </form>

    <div class="row-cols-auto justify-content-center">
        <table id="area_content" class="table table-striped table-hover table-bordered" style="display: none;">
            <thead>
                <tr class="thead-light sticky-top">
                    <th>書籍名</th>
                    <th class="text-end">予約数</th>
                    <th class="text-end">待ち週</th>
                    <th class="text-center"></th>
                </tr>
            </thead>
            <tbody id="searched_books">
            </tbody>
            <tbody class="js_search_button_progress">
                <tr class="thead-light">
                    <td colspan="4" class="text-center">
                        <div class="alert alert-secondary" role="alert">
                            <div class="spinner-border text-info" role="status"></div> 検索中...
                        </div>
                    </td>
                </tr>
            </tbody>
            <tbody id="show_next_page">
                <tr class="thead-light">
                    <td colspan="4" class="text-center">
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-fast-forward-circle-fill"></i> 次のページ
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {literal}
    <script type="text/javascript">
        $(document).ready(function() {
            $('#area_content').tablesorter({sortList: [[3, 0], [1, 0], [2, 1]]});
        });
    </script>
    {/literal}
{/block}
