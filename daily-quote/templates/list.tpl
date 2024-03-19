{include file="header.tpl"}

{literal}
<script type="text/javascript">
    function view(page) {
        run("view", {page: page});
    }
    function add() {
        run("add");
    }
    function edit(doc_no) {
        run("edit", {doc_no: doc_no});
    }
</script>
{/literal}

<div class="container">
    <p class="h3">Quotes</p>

    <div class="row">
        <div class="col-2">
            <button type="button" class="btn btn-primary btn-sm" onClick="add()">Add</button>
        </div>
        <div class="col-8 justify-content-center">
            <button type="button" class="btn btn-light btn-sm" onclick="view(1)">&lt;&lt;</button>
            <button type="button" class="btn btn-light btn-sm" onclick="view({$page - 1})">&lt;</button>
            <button type="button" class="btn btn-light btn-sm">Page {$page}</button>
            <button type="button" class="btn btn-light btn-sm" onclick="view({$page + 1})">&gt;</button>
            <button type="button" class="btn btn-light btn-sm" onclick="view({$max_page})">&gt;&gt;</button>
        </div>
        <div class="col-2">
        </div>
    </div>
    <table class="table table-striped table-hover table-bordered">
        <tr>
            <th>No</th>
            <th>Message</th>
            <th>Author</th>
        </tr>
        {foreach $quotes as $quote}
            {$data=$quote->data()}
            <tr>
                <td>
                    <button type="button" class="btn btn-primary btn-sm" onClick="edit({$data['no']})">{$data["no"]}</button>
                </td>
                <td>{$data["message"]}</td>
                <td>{$data["author"]}</td>
            </tr>
        {/foreach}
    </table>
</div>

{include file="footer.tpl"}
