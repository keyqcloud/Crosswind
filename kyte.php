<?php

function html($lang, $charset, $title, $body) {
    return <<<EOF
<!DOCTYPE html>
<html lang="$lang">

<head>
    <!-- Required meta tags always come first -->
    <meta charset="$charset">
    <meta name="google" content="notranslate">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>$title</title>

    <link rel="stylesheet" href="/css/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.11.2/css/all.css">
    <!-- Bootstrap core CSS -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <!-- Material Design Bootstrap -->
    <link href="/css/mdb.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="/css/addons/datatables.min.css" rel="stylesheet">
    <!-- DataTables Select CSS -->
    <link href="/css/addons/datatables-select.min.css" rel="stylesheet">
    <!-- JQuery -->
    <script type="text/javascript" src="/js/jquery.min.js"></script>
    <!-- DataTables JS -->
    <script type="text/javascript" src="/js/addons/datatables.min.js"></script>
    <script type="text/javascript" src="/js/addons/datatables-select.min.js"></script>
    <!-- Bootstrap tooltips -->
    <script type="text/javascript" src="/js/popper.min.js"></script>
    <!-- Bootstrap core JavaScript -->
    <script type="text/javascript" src="/js/bootstrap.min.js"></script>
    <!-- MDB core JavaScript -->
    <script type="text/javascript" src="/js/mdb.min.js"></script>
    <!-- formvalidation -->
    <script type="text/javascript" src="https://cdn.keyq.cloud/js/formvalidation.js"></script>
    <!-- Kyte API -->
    <script type="text/javascript" src="https://cdn.api.keyq.cloud/v2/kyte.api.js"></script>
    <script type="text/javascript" src="/js/api.js"></script>
</head>
<body>
$body
</body>
</html>

EOF;
}

?>