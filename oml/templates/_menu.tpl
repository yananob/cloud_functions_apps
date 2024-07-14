<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">{block name=title}{/block}</a>
        <span class="navbar-text">
            {block name=header_info}{/block}
        </span>
        <nav class="nav">
            {* TODO: MyApp\Command::Main だとNG *}
            <a class="nav-link" href="{$base_path}?cmd=main"><span class="btn btn-secondary btn-sm">トップ</span></a>
            <a class="nav-link" href="{$base_path}?cmd=reserve"><span class="btn btn-secondary btn-sm">予 約</span></a>
            <a class="nav-link" href="{$base_path}?cmd=list_reserved"><span class="btn btn-secondary btn-sm">予約一覧</span></a>
            <a class="nav-link" href="{$base_path}?cmd=list_lending"><span class="btn btn-secondary btn-sm">貸出一覧</span></a>
        </nav>
    </div>
</nav>
