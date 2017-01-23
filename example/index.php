<?php
    ini_set('display_errors', true);

    include '../vendor/autoload.php';

    $url = 'https://medium.com/@jaysalvat/my-title-99dcb55001b6';

    $parser = new MediumJsonParser\Parser($url);
    $parser->iframeProxyPath = 'iframe.php';
    $parser->imageQuality = 80;
    $parser->imageWidth = 2000;

    $html = $parser->html([
        'skip_header'  => false,
        'return_array' => false
    ]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Medium Parser</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="medium-container">
        <?php echo $html ?>
    </div>
</body>
</html>