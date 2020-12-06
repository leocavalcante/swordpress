<?php declare(strict_types=1);

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

register_shutdown_function(static function (): void {
    var_dump(ob_get_contents());
    var_dump(headers_list());
    var_dump(error_get_last());
    var_dump(headers_sent());
});

ob_start();
require_once __DIR__ . '/web/wp-config.php';

$document_root = __DIR__ . '/web';

$server = new Server('0.0.0.0', 80);

$server->on('request', static function (Request $request, Response $response) use ($document_root): void {
    $_GET = $request->get ?? [];
    $_POST = $request->post ?? [];
    $_SERVER = $request->server ?? [];
    $_COOKIE = $request->cookie ?? [];

    ob_start();

    $filename = $document_root . $request->server['request_uri'];

    if (!is_readable($filename)) {
        $filename = __DIR__ . '/web/index.php';
    }

    try {
        require $filename;
    } catch (\Swoole\ExitException $exception) {
        // Allow exit();
    }

    $response->write(ob_get_contents());
    ob_end_clean();

    foreach (headers_list() as $header) {
        [$key, $value] = explode(': ', $header);
        $response->setHeader($key, $value);
    }

    $response->end();
});

$server->start();
