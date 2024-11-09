{extends file='_layout.tpl'}

{block name=title}予約{/block}

{block name=head}
<script type="text/javascript">
    {include file = "js/reserve.js" }
</script>
<style>
    .badge {
        font-size: 0.85em;
    }
</style>
{/block}

{block name=header_info}
[予約可能数: {$totalReservableCount}]
{/block}

{block name=content}
<ul class="nav nav-tabs mb-2">
    <li class="nav-item">
        <a class="nav-link js_switch_tab" href="#" data-tab="search">検索</a>
    </li>
    <li class="nav-item">
        <a class="nav-link js_switch_tab" href="#" data-tab="new_adult">新/大</a>
        </li>
        <li class="nav-item">
            <a class="nav-link js_switch_tab" href="#" data-tab="new_child">新/子</a>
    </li>
    <li class="nav-item">
        <a class="nav-link js_switch_tab" href="#" data-tab="lending">貸出30</a>
    </li>
    <li class="nav-item">
        <a class="nav-link js_switch_tab" href="#" data-tab="reserve">予約30</a>
    </li>
</ul>

<div id="tab-search" style="display: none">
    <form onsubmit="return false;">
        <div class="row mb-1">
            <div class="col-9">
                <input type="search" name="keyword" class="form-control js_enter_search" placeholder="キーワード (3文字以上)">
            </div>
            <button type="button" class="btn btn-primary col-3" id="search_button"
                data-progress=".js_search_button_progress">
                <i class="bi bi-search"></i> 検索
            </button>
            <button type="button" class="btn btn-primary col-3 js_search_button_progress" disabled
                style="display: none">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            </button>
        </div>
        <div class="row mb-3 js_search_detail">
            <div class="col-6">
                <input type="search" name="title" class="form-control js_enter_search" placeholder="タイトル">
            </div>
            <div class="col-6">
                <input type="search" name="author" class="form-control js_enter_search" placeholder="著者名">
            </div>
        </div>
        <input type="hidden" name="max_page" value="{if $is_local}2{else}4{/if}">
    </form>
</div>

<div id="tab-new_adult" style="display: none">
    {foreach $upcomingAdultList as $k => $v}
    <span class="badge bg-primary js_show_list" data-type="upcoming_adult" data-category="{$k}"
        data-progress=".js_search_button_progress">{$v}</span>
    {/foreach}
</div>

<div id="tab-new_child" style="display: none">
    {foreach $upcomingChildList as $k => $v}
    <span class="badge bg-primary js_show_list" data-type="upcoming_child" data-category="{$k}"
        data-progress=".js_search_button_progress">{$v}</span>
    {/foreach}
</div>

<div id="tab-lending" style="display: none">
    {foreach $bestList as $k => $v}
    <span class="badge bg-primary js_show_list" data-type="lending_best" data-category="{$k}"
        data-progress=".js_search_button_progress">{$v}</span>
    {/foreach}
</div>

<div id="tab-reserve" style="display: none">
    {foreach $bestList as $k => $v}
    <span class="badge bg-primary js_show_list" data-type="reserve_best" data-category="{$k}"
        data-progress=".js_search_button_progress">{$v}</span>
    {/foreach}
</div>

<div class="row-cols-auto justify-content-center mt-2">
    <table id="area_content" class="table table-striped table-hover table-bordered" style="display: none;">
        <thead>
            <tr class="thead-light sticky-top">
                <th>書籍名</th>
                <th class="text-end">予約数</th>
                <th class="text-end">待ち週</th>
                <th class="text-center"></th>
            </tr>
        </thead>
        <tbody id="books_list">
        </tbody>
        <tbody class="js_search_button_progress">
            <tr class="thead-light">
                <td colspan="4" class="text-center">
                    <div class="alert alert-secondary" role="alert">
                        <div class="spinner-border text-info" role="status"></div> 取得中...
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
    $(document).ready(function () {
        $('#area_content').tablesorter({ sortList: [[3, 0], [1, 0], [2, 1]] });
    });
</script>
{/literal}
{/block}