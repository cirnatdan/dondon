<?php
require_once 'vendor/autoload.php';

use Amp\Http\Client\HttpClientBuilder;
use Goutte\Client;
chdir(__DIR__);
$filePath = realpath(ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
if ($filePath && is_dir($filePath)){
    // attempt to find an index file
    foreach (['index.php', 'index.html'] as $indexFile){
        if ($filePath = realpath($filePath . DIRECTORY_SEPARATOR . $indexFile)){
            break;
        }
    }
}
if ($filePath && is_file($filePath)) {
    // 1. check that file is not outside of this directory for security
    // 2. check for circular reference to router.php
    // 3. don't serve dotfiles
    if (strpos($filePath, __DIR__ . DIRECTORY_SEPARATOR) === 0 &&
        $filePath != __DIR__ . DIRECTORY_SEPARATOR . 'index.php' &&
        substr(basename($filePath), 0, 1) != '.'
    ) {
        if (strtolower(substr($filePath, -4)) == '.php') {
            // php file; serve through interpreter
            include $filePath;
        } else {
            // asset file; serve from filesystem
            return false;
        }
    }
}

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', function () {
        ob_start();
        include ('home.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/login', function () {
        ob_start();
        chdir(__DIR__ . '/login');
        include ('login.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('POST', '/login', function () {
        error_log('LOGIN');
        ob_start();
        chdir(__DIR__ . '/login');
        include ('login.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/auth', function () {
        ob_start();
        chdir(__DIR__ . '/login');
        include ('auth.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/logout', function () {
        ob_start();
        chdir(__DIR__ . '/login');
        include ('logout.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/local', function () {
        ob_start();
        include ('local.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/federated', function () {
        ob_start();
        include ('federated.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/notifications', function () {
        ob_start();
        include ('notifications.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/lists', function () {
        ob_start();
        include ('lists.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/privacy', function () {
        ob_start();
        chdir(__DIR__ . '/login');
        include ('privacy.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/direct', function () {
        ob_start();
        include ('direct.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/lists/{id}', function () {
        ob_start();
        include('lists_view.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/instance', function () {
        ob_start();
        include ('instance.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/private', function () {
        ob_start();
        include ('instance.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/{handle:@.+@.+\.[a-z]+}/with_replies', function () {
        preg_match('/@(.+)@(.+)\.([a-z]+)/', $_SERVER['REQUEST_URI'], $matches);
        $_GET['user'] = $matches[0];
        ob_start();
        include ('user_include_replies.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/{handle:@.+@.+\.[a-z]+/?}', function () {
        preg_match('/@(.+)@(.+)\.([a-z]+)/', $_SERVER['REQUEST_URI'], $matches);
        $_GET['user'] = $matches[0];
        ob_start();
        include ('user.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/api/{param:.+}', function () {
        $url = parse_url($_SERVER['REQUEST_URI']);
        $query = $url['query'] ?? '';
        $mastodonRequestUri = str_replace('/mastodon/', '', $url['path']);
        error_log($mastodonRequestUri);

        $nitterScraper = new \App\NitterScraper(new Client());
        if ($mastodonRequestUri === '/api/v2/search' && preg_match('/@(.+)@twitter.com/', $_GET['q'])) {
            $twitterAccounts = $nitterScraper->searchAccounts($_GET['q'], $_GET['resolve'] ?? false);

            if (count($twitterAccounts) > 0) {
                $response = [];
                $response['accounts'] = array_merge($response['accounts'] ?? [], $twitterAccounts);
                $response['statuses'] = [];
                $response['hashtags'] = [];
                return new \Amp\Http\Server\Response(200, ['content-type' => 'application/json'], json_encode($response));
            }
        } elseif ($mastodonRequestUri === '/api/v1/accounts/lookup' && preg_match('/@(.+)@twitter.com/', $_GET['acct'])) {
            return new \Amp\Http\Server\Response(200, ['content-type' => 'application/json'],
                json_encode($nitterScraper->lookupAccount($_GET['acct'])),
            );
        } elseif (preg_match('|/api/v1/accounts/(.+)/statuses|', $mastodonRequestUri, $matches)) {

            $accountId = $matches[1];

            if (preg_match('/@(.+)@twitter.com/', $accountId)) {
                if (($_GET['only_media'] ?? false) === 'true') {
                    error_log('ONLY MEDIA');
                    return new \Amp\Http\Server\Response(200, ['content-type' => 'application/json'],
                        json_encode([]),
                    );
                }

                if (($_GET['pinned'] ?? false) === 'true') {
                    return new \Amp\Http\Server\Response(200, ['content-type' => 'application/json'], json_encode([]));
                }

                return new \Amp\Http\Server\Response(200, ['content-type' => 'application/json'],
                    json_encode($nitterScraper->getAccountTweets($accountId, !boolval($_GET['exclude_replies'] ?? false))),
                );
            }
        } elseif (preg_match('|/api/v1/accounts/relationships|', $mastodonRequestUri, $matches)) {
            if (preg_match('/@(.+)@twitter.com/', $query)) {
                return new \Amp\Http\Server\Response(200, ['content-type' => 'application/json'],
                    json_encode([]),
                );
            }
        }

        return new \Amp\Http\Server\Response(302, ['Location' => $_GET['mastodon_host'] . '/' . $mastodonRequestUri . '?' . $query]);
    });
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

error_log('Dispatching');
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        header('HTTP/1.0 404 Not Found');
        // ... 404 Not Found
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        header('HTTP/1.0 405 Method Not Allowed');
        // ... 405 Method Not Allowed
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        $response = $handler();
        http_response_code($response->getStatus());
        foreach ($response->getHeaders() as $header => $value) {
            header($header . ': ' . $value[0]);
        }

        \Amp\Loop::run(function () use ($response) {
            echo yield $response->getBody()->read();
        });
        break;
}