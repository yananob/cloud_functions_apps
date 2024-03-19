{include file="header.tpl"}

{literal}
<script type="text/javascript">
    function __checkForm() {
        // 必須チェック
        let check_fields = ["password"];
        for (let i = 0; i < check_fields.length; i++) {
            let element = check_fields[i];
            if (document.getElementById(element).value.trim() === "") {
                alert("[" + element + "]を入力してください。");
                return false;
            }
        }
        return true;
    }

    function login() {
        if (!__checkForm()) {
            return;
        }

        document.login_form.submit();
    }
</script>
{/literal}

<div class="container">
    <div class="row justify-content-center">
        <div class="col-6">
            <p class="h3">Login</p>

            <div class="alert alert-info" role="alert">{$message}</div>

            <form name="login_form" method="POST" action="?cmd=login">
                <label class="form-label" for="message">Password</label>
                <input class="form-control" type="password" name="password" id="password" value="">
            </form>
            <button type="button" class="btn btn-primary" onClick="javascript:login()">Login</button>
        </div>
    </div>
</div>

{include file="footer.tpl"}
