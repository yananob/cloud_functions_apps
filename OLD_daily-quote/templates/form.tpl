{include file="header.tpl"}

{literal}
<script type="text/javascript">
    function cancel() {
        if (window.confirm("入力をキャンセルしていいですか？")) {
            history.back();
        }
    }

    function __checkForm() {
        // 必須チェック
        let check_fields = ["message", "author", "source"];
        for (let i = 0; i < check_fields.length; i++) {
            let element = check_fields[i];
            if (document.getElementById(element).value.trim() === "") {
                alert("[" + element + "]を入力してください。");
                return false;
            }
        }
        return true;
    }

    function save(doc_no) {
        if (!__checkForm()) {
            return;
        }

        const url_params = new URLSearchParams("");
        url_params.append("cmd", "save");
        if (doc_no != "") {
            url_params.append("doc_no", doc_no);
        }
        document.edit_form.action = "?" + url_params.toString();
        document.edit_form.submit();
    }

    function remove(doc_no) {
        if (window.confirm("本当に削除してもいいですか？（削除は取り消しできません）")) {
            run("remove", {doc_no: doc_no});
        }
    }
</script>
{/literal}

<div class="container">
    <div class="row justify-content-center">
        <div class="row justify-content-center">
            <div class="col-5">
                <p class="h3">Edit quote</p>
            </div>
            <div class="col-1">
                <button type="button" class="btn btn-danger" onClick="remove('{$doc_no}')">Remove</button>
            </div>
        </div>
        <div class="col-6">
            <form name="edit_form" method="POST">
                <label class="form-label" for="message">Message</label>
                <textarea class="form-control" type="text" name="message" id="message" rows="5">{$quote['message']}</textarea>

                <label class="form-label" for="author">Author</label>
                <input class="form-control" type="text" name="author" id="author" value="{$quote['author']}">

                <label class="form-label" for="source">Source</label>
                <input class="form-control" type="text" name="source" id="source" value="{$quote['source']}">

                <label class="form-label" for="source_link">Source link</label>
                <input class="form-control" type="text" name="source_link" id="source_link" value="{$quote['source_link']}">
            </form>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-1">
            <button type="button" class="btn btn-warning" onClick="cancel()">Cancel</button>
        </div>
        <div class="col-4">
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-primary" onClick="save('{$doc_no}')">Save</button>
        </div>
    </div>
</div>

{include file="footer.tpl"}
