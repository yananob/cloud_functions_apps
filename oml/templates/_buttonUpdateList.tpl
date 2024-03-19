{literal}
<script type="text/javascript">
$(document).ready(function () {
    // initProgress($("#btn_update"));

    $("#btn_update").click(function () {
        showProgress($(this));
        {/literal}
        run("{$updateCommand}");
        {literal}
    });
});
</script>
{/literal}

<div class="row justify-content-center">
    <div class="col-1"></div>
    <div class="col-auto">
        <a id="btn_update" class="btn btn-secondary" role="button" data-progress="#btn_update_progress">
            <i class="bi bi-cloud-upload-fill"></i> リストを更新
        </a>
        <button id="btn_update_progress" class="btn btn-secondary" style="display: none" type="button" disabled>
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            更新しています...
        </button>
    </div>
</div>
