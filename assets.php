<?php
$filepath = join('/',
    array_filter(
        [
            realpath(getenv('JVVM_CLIENT_PATH')), 
            ...explode('/', $_SERVER['PATH_INFO'])
        ],
        fn($part) => $part !== '' && $part !== '.' && $part !== '..'
    )
);

if (!file_exists($filepath)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$type = substr($filepath, -3);
switch ($type) {
    case 'css':
        header('Content-Type: text/css');
        break;
    case '.js':
    case 'mjs':
        header('Content-Type: text/javascript');
        break;
    case 'png':
        header('Content-Type: image/png');
        break;
    case 'jpg':
    case 'peg':
        header('Content-Type: image/jpeg');
        break;
    case 'gif':
        header('Content-Type: image/gif');
        break;
    case 'svg':
        header('Content-Type: image/svg+xml');
        break;
    case 'tml':
        header('Content-Type: text/html');
        break;
    case 'son':
        header('Content-Type: application/json');
        break;
    default:
        header('Content-Type: application/octet-stream');
}
readfile($filepath);