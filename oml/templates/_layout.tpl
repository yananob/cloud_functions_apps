<html>
<head>
    <title>{block name=title}{/block} - oml books</title>
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <!-- tablesorter https://mottie.github.io/tablesorter/docs/index.html#Introduction -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.3/js/jquery.tablesorter.min.js"></script>
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.3/css/theme.default.min.css">: disabling: too small -->
    <style>
        {include file="css/common.css"}
    </style>
    <script type="text/javascript">
        {include file="js/common.js"}
    </script>
    {block name=head}{/block}
</head>
<body>
    <span id="top"></span>

    {include file="_menu.tpl"}

    <div class="container">
        <div class="row">
            <div class="col-12 my-1">
            </div>
        </div>
    </div>

    {if !empty($messages)}
        <div class="alert alert-primary" role="alert">{"<br>"|implode:$messages}</div>
    {/if}
    {if !empty($alerts)}
        <div class="alert alert-warning" role="alert">{"<br>"|implode:$alerts}</div>
    {/if}
    {if !empty($errors)}
        <div class="alert alert-danger" role="alert">{"<br>"|implode:$errors}</div>
    {/if}

    <div class="container">
        {block name=content}{/block}
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-1"></div>
            <div class="col-auto">
                <a class="btn btn-primary position-fixed start-0 bottom-0 ms-5 mb-5" href="#top">
                    <i class="bi bi-chevron-up"></i>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
