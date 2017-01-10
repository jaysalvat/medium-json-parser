# Medium Json Parser

[![Latest Stable Version](https://poser.pugx.org/jaysalvat/medium-json-parser/v/stable.svg)](https://packagist.org/packages/jaysalvat/medium-json-parser)
[![License](https://poser.pugx.org/jaysalvat/medium-json-parser/license.svg)](https://packagist.org/packages/jaysalvat/medium-json-parser)

This is a PHP parser for the Medium Json API.

## Installation:

Create a [composer.json](https://getcomposer.org/) file with:

    {
       "require": {
           "jaysalvat/medium-json-parser": "~1.0"
       }
    }

Run [Composer](https://getcomposer.org/) to install MediumJsonParser.

    $ curl -sS https://getcomposer.org/installer | php
    $ composer.phar install

## Example:

```php
$url = 'https://medium.com/@jaysalvat/my-title-99dcb55001b6';

$parser = new MediumJsonParser\Parser($url);

// Path to the iFrame proxy, see below
$parser->iframeProxyPath = 'iframe.php';

// Image compression
$parser->imageQuality = 80;

// Image max size
$parser->imageWidth = 2000;

$html = $parser->html([
    // Skip/keep the title and subtitle
    'skip_header'  => false,

    // HTML or Array of HTML
    'return_array' => false
]);

echo $html;
```

## iFrame Proxy

To avoid CORS issues with iFrame, create a iframe.php proxy.

```php
readfile('http://medium.com/media/' . $_GET['resource_id'] . '?postId=' . $_GET['post_id'] . '"');
```
