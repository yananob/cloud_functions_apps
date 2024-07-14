{extends file='_layout.tpl'}

{block name=title}予約{/block}

{block name=head}
<script type="text/javascript">
    {include file = "js/reserve.js" }
</script>
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
        <a class="nav-link js_switch_tab" href="#" data-tab="new">入る本</a>
    </li>
    <li class="nav-item">
        <a class="nav-link disabled" href="#">貸出30</a>
    </li>
    <li class="nav-item">
        <a class="nav-link disabled" href="#">予約30</a>
    </li>
</ul>

<div id="tab-search">
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

<div id="tab-new">
    これから入る本
    <ul>
        <li class="js_show_list">読書・報道・雑学</li>
        <li class="js_show_list">哲学・心理学・宗教</li>
        <li class="js_show_list">歴史・伝記</li>
        <li class="js_show_list">地理・旅行ガイド</li>
        <li class="js_show_list">政治・法律・経済・社会科学</li>
        <li class="js_show_list">社会福祉・教育</li>
        <li class="js_show_list">自然科学</li>
        <li class="js_show_list">動物・植物</li>
        <li class="js_show_list">医学・薬学</li>
        <li class="js_show_list">技術・工学・環境問題</li>
        <li class="js_show_list">コンピュータ・情報科学</li>
        <li class="js_show_list">生活・料理・育児</li>
        <li class="js_show_list">産業・園芸・ペット</li>
        <li class="js_show_list">芸術・音楽</li>
        <li class="js_show_list">スポーツ・娯楽</li>
        <li class="js_show_list">言語・語学・スピーチ</li>
        <li><a href="#" class="js_show_list" data-lv2="17">文学</a></li>
        <li><a href="#" class="js_show_list" data-lv2="18" data-progress=".js_search_button_progress">日本の小説</a></li>
        <li class="js_show_list">外国の小説</li>
        <li class="js_show_list">エッセイ</li>
    </ul>
</div>

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