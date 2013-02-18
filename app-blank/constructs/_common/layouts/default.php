<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" type="text/css" media="all" href="/css/global.css" />
    <?php Ox_WidgetHandler::CSS() ?>
    <?php Ox_WidgetHandler::JS() ?>
    <?php Ox_WidgetHandler::HtmlTitle() ?>
</head>
<body>
<header class="container_16">
    Ox Default Layout
</header>


<section class="container_16">
    <?php echo $content; ?>
</section>

<footer class="container_16">
    <?php echo date('Y-m-d H:i:s');?>
</footer>
</body>
</html>
