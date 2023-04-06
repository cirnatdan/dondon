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
            return true;
        } else {
            // asset file; serve from filesystem
            return false;
        }
    }
}

$container = new \League\Container\Container();
$container->defaultToShared(true);
$container->add(\PDO::class)
    ->addArgument('sqlite:' . __DIR__ . '/data/db/dondon.sqlite');
$container->add(\HalcyonSuite\HalcyonForMastodon\Mastodon::class);
$container->add(\App\AccessTokenRepository::class)
    ->addArgument(\PDO::class);
$container->add(\App\UserRepository::class)
    ->addArgument(\PDO::class);
$container->add(\App\FollowRepository::class)
    ->addArgument(\PDO::class);

$container->add(\App\NitterScraper::class)
    ->addArgument(\Goutte\Client::class);
$container->add(\Goutte\Client::class)
    ->addArgument(\Symfony\Component\HttpClient\CachingHttpClient::class);
$container->add(\Symfony\Component\HttpClient\CachingHttpClient::class)
    ->addArgument(\Symfony\Contracts\HttpClient\HttpClientInterface::class)
    ->addArgument(\Symfony\Component\HttpKernel\HttpCache\StoreInterface::class)
    ->addArgument(['default_ttl' => 300]);
$container->add(\Symfony\Contracts\HttpClient\HttpClientInterface::class, function () {
   return \Symfony\Component\HttpClient\HttpClient::create();
});
$container->add(\Symfony\Component\HttpKernel\HttpCache\StoreInterface::class, \Symfony\Component\HttpKernel\HttpCache\Store::class)
    ->addArgument(__DIR__ . '/data/nitter-cache');

$container->add(\App\TweeterIDDotComAPI::class)
    ->addArgument(\Symfony\Contracts\HttpClient\HttpClientInterface::class);

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) use ($container) {
    $r->addRoute('GET', '/', function () {
        ob_start();
        include('views/home.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/login[/]', function () {
        ob_start();
        chdir(__DIR__ . '/views/login');
        include ('login.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('POST', '/login[/]', function () {
        error_log('LOGIN');
        ob_start();
        chdir(__DIR__ . '/views/login');
        include ('login.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/auth', function () use ($container) {
        $api = $container->get(\HalcyonSuite\HalcyonForMastodon\Mastodon::class);
        $domain = htmlspecialchars((string)filter_input(INPUT_GET, 'host'), ENT_QUOTES);
        if(in_array($domain,json_decode(base64_decode("WyJnYWIuY29tIiwiZ2FiLmFpIl0=")))) die();
        $URL= 'https://'.$domain;
        $api->selectInstance($URL);
        $response = $api->get_access_token($api->clientWebsite.'/auth?&host='.$domain, htmlspecialchars((string)filter_input(INPUT_GET, 'code'), ENT_QUOTES));
        if(isset($response) && is_array($response) && isset($response['html']) && is_array($response['html']) && isset($response['html']["access_token"])) {
            $access_token = $response['html']["access_token"];
            $profile = $api->accounts_verify_credentials()['html'];
            $account_id = $profile['id'];
            /** @var \App\AccessTokenRepository $accessTokenRepository */
            $accessTokenRepository = $container->get(\App\AccessTokenRepository::class);
            $accessTokenRepository->saveAccessToken($account_id, $domain, $access_token, new \DateTime());
        } else {
            return new \Amp\Http\Server\Response(302, ['location' => '/login?error=no_token']);
        }
        ob_start();
        chdir(__DIR__ . '/views/login');
        include ('auth.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/logout', function () {
        ob_start();
        chdir(__DIR__ . '/views/login');
        include ('logout.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/local', function () {
        ob_start();
        include('views/local.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/federated', function () {
        ob_start();
        include ('views/federated.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/notifications', function () {
        ob_start();
        include ('views/notifications.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/lists', function () {
        ob_start();
        include ('views/lists.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/privacy', function () {
        ob_start();
        chdir(__DIR__ . '/views/login');
        include ('privacy.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/terms', function () {
        ob_start();
        chdir(__DIR__ . '/views/login');
        include ('terms.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/imprint', function () {
        ob_start();
        chdir(__DIR__ . '/views/login');
        include ('imprint.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/direct', function () {
        ob_start();
        include ('views/direct.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/lists/{id}', function (string $id) {
        $_GET['id'] = $id;
        ob_start();
        include('views/lists_view.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/lists/{id}/add', function (string $id) {
        $_GET['id'] = $id;
        ob_start();
        include('views/lists_add.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/instance', function () {
        ob_start();
        include ('views/instance.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/private', function () {
        ob_start();
        include ('instance.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/settings/profile', function () {
        ob_start();
        include ('views/settings_profile.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/settings/appearance', function () {
        ob_start();
        include ('views/settings_appearance.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/settings/filters', function () {
        ob_start();
        include ('views/settings_filters.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/settings/media', function () {
        ob_start();
        include ('views/settings_media.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/settings/blocks', function () {
        ob_start();
        include ('views/settings_accounts.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/settings/mutes', function () {
        ob_start();
        include ('views/settings_accounts.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/settings/followers', function () {
        ob_start();
        include ('views/settings_accounts.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/settings', function () {
        ob_start();
        include ('views/settings_general.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/bookmarks', function () {
        ob_start();
        include ('views/bookmarks.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/search', function () {
        ob_start();
        include ('views/search_hash_tag.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/search/users', function () {
        ob_start();
        include ('views/search_user.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/whotofollow', function () {
        ob_start();
        include ('views/who_to_follow.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/{handle:@.+@.+\.[a-z]+}/with_replies', function (string $handle) {
        $_GET['user'] = $handle;
        ob_start();
        include('views/user_include_replies.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/{handle:@.+@.+\.[a-z]+}/status/{statusId}', function (string $handle, string $statusId) {
        preg_match('/@(.+)@(.+)\.([a-z]+)/', $_SERVER['REQUEST_URI'], $matches);
        $_GET['user'] = $handle;
        $_GET['status'] = $statusId;
        ob_start();
        include('views/user_include_replies.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/{handle:@.+@.+\.[a-z]+/?}', function (string $handle) {
        $_GET['user'] = $handle;
        ob_start();
        include('views/user.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/{handle:@.+@.+\.[a-z]+}/following', function (string $handle) {
        $_GET['user'] = $handle;
        ob_start();
        include('views/user_following.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/{handle:@.+@.+\.[a-z]+}/followers', function (string $handle) {
        $_GET['user'] = $handle;
        ob_start();
        include('views/user_followers.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('GET', '/{handle:@.+@.+\.[a-z]+}/favourites', function (string $handle) {
        $_GET['user'] = $handle;
        ob_start();
        include('views/user_favorite.php');
        $content = ob_get_clean();
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], $content);
    });

    $r->addRoute('POST', '/api/v1/accounts/{id:\d+}/follow', function (int $id) {
        return new \Amp\Http\Server\Response(421, ['content-type' => 'text/html'], 'Probable Mastodon account. Please use the Mastodon instance API');
    });

    $r->addRoute('POST', '/api/v1/accounts/{id:\d+}/unfollow', function (int $id) {
        return new \Amp\Http\Server\Response(421, ['content-type' => 'text/html'], 'Probable Mastodon account. Please use the Mastodon instance API');
    });

    $r->addRoute('POST', '/api/v1/accounts/{handle:@.+@.+\.[a-z]+}/follow', function (string $handle ) use ($container){
        $token = explode(' ', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (count($token) !== 2 || $token[0] !== 'Bearer') {
            return new \Amp\Http\Server\Response(401, ['content-type' => 'text/html'], 'Unauthorized');
        }

        /** @var \App\AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $container->get(\App\AccessTokenRepository::class);
        $accessToken = $accessTokenRepository->findAccessToken($token[1]);
        if (!$accessToken) {
            return new \Amp\Http\Server\Response(401, ['content-type' => 'text/html'], 'Unauthorized');
        }
        /** @var \App\UserRepository $userRepository */
        $userRepository = $container->get(\App\UserRepository::class);
        $username = explode('@', $handle)[1];
        $user = $userRepository->findByUsernameAndInstance($username, 'twitter.com');
        if (!$user) {
            return new \Amp\Http\Server\Response(404, ['content-type' => 'text/html'], 'User Not Found');
        }
        /** @var \App\FollowRepository $followRepository */
        $followRepository = $container->get(\App\FollowRepository::class);
        $followRepository->saveRelationship($accessToken->getInstance(), $accessToken->getIdOnInstance(), 'twitter.com', $user->getIdOnInstance());
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], 'Follow successful');
    });

    $r->addRoute('POST', '/api/v1/accounts/{handle:@.+@.+\.[a-z]+}/unfollow', function (string $handle ) use ($container){
        $token = explode(' ', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (count($token) !== 2 || $token[0] !== 'Bearer') {
            return new \Amp\Http\Server\Response(401, ['content-type' => 'text/html'], 'Unauthorized');
        }

        /** @var \App\AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $container->get(\App\AccessTokenRepository::class);
        $accessToken = $accessTokenRepository->findAccessToken($token[1]);
        if (!$accessToken) {
            return new \Amp\Http\Server\Response(401, ['content-type' => 'text/html'], 'Unauthorized');
        }
        /** @var \App\UserRepository $userRepository */
        $userRepository = $container->get(\App\UserRepository::class);
        $username = explode('@', $handle)[1];
        $user = $userRepository->findByUsernameAndInstance($username, 'twitter.com');
        if (!$user) {
            return new \Amp\Http\Server\Response(404, ['content-type' => 'text/html'], 'User Not Found');
        }
        /** @var \App\FollowRepository $followRepository */
        $followRepository = $container->get(\App\FollowRepository::class);
        $followRepository->deleteRelationship($accessToken->getInstance(), $accessToken->getIdOnInstance(), 'twitter.com', $user->getIdOnInstance());
        return new \Amp\Http\Server\Response(200, ['content-type' => 'text/html'], 'Follow successful');
    });

    $r->addRoute('GET', '/api/v1/accounts/relationships', function () use ($container) {
        if (!preg_match('/@(.+)@twitter.com/', $_GET['id'], $matches)) {
            return new \Amp\Http\Server\Response(421, ['content-type' => 'text/html'], 'Not Implemented');
        }

        $token = explode(' ', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (count($token) !== 2 || $token[0] !== 'Bearer') {
            return new \Amp\Http\Server\Response(401, ['content-type' => 'text/html'], 'Unauthorized');
        }

        /** @var \App\AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $container->get(\App\AccessTokenRepository::class);
        $accessToken = $accessTokenRepository->findAccessToken($token[1]);
        if (!$accessToken) {
            return new \Amp\Http\Server\Response(401, ['content-type' => 'text/html'], 'Unauthorized');
        }

        /** @var \App\UserRepository $userRepository */
        $userRepository = $container->get(\App\UserRepository::class);
        $targetUser = $userRepository->findByUsernameAndInstance($matches[1], 'twitter.com');
        if (!$targetUser) {
            return new \Amp\Http\Server\Response(404, ['content-type' => 'text/html'], 'User Not Found');
        }

        /** @var \App\FollowRepository $followRepository */
        $followRepository = $container->get(\App\FollowRepository::class);
        $relationships = [];

        $relationship = $followRepository->getRelationship($accessToken->getInstance(), $accessToken->getIdOnInstance(), 'twitter.com', $targetUser->getIdOnInstance());
        if (null !== $relationship)
            $relationships[] = [
                'id' => $_GET['id'],
                'following' => true,
                'showing_reblogs' => true,
                'notifying' => false,
                'languages' => null,
                'followed_by' => false,
                'blocking' => false,
                'blocked_by' => false,
                'muting' => false,
                'muting_notifications' => false,
                'requested' => false,
                'requested_by' => false,
                'domain_blocking' => false,
                'endorsed' => false,
                'note' => '',
            ];

        return new \Amp\Http\Server\Response(200, ['content-type' => 'application/json'], json_encode($relationships));
    });

    $r->addRoute('GET', '/api/v1/timelines/home', function () use ($container) {
        $token = explode(' ', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (count($token) !== 2 || $token[0] !== 'Bearer') {
            return new \Amp\Http\Server\Response(401, ['content-type' => 'text/html'], 'Unauthorized');
        }

        /** @var \App\AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $container->get(\App\AccessTokenRepository::class);
        $accessToken = $accessTokenRepository->findAccessToken($token[1]);
        if (!$accessToken) {
            return new \Amp\Http\Server\Response(401, ['content-type' => 'text/html'], 'Unauthorized');
        }

        /** @var \App\FollowRepository $followRepository */
        $followRepository = $container->get(\App\FollowRepository::class);
        /** @var \Domain\FollowRelationship[] $followRelationships */
        $followRelationships = $followRepository->getFollowRelationShipsForIdOnInstance($accessToken->getInstance(), $accessToken->getIdOnInstance());

        /** @var \App\UserRepository $userRepository */
        $userRepository = $container->get(\App\UserRepository::class);
        /** @var \App\NitterScraper $nitterScraper */
        $nitterScraper = $container->get(\App\NitterScraper::class);
        $statuses = [];
        foreach ($followRelationships as $relationship) {
            if ($relationship->getTargetInstance() === 'twitter.com') {
                $user = $userRepository->findByIdOnInstance($relationship->getTargetIdOnInstance(), 'twitter.com');
                foreach($nitterScraper->getAccountTweets('@' . $user->getUsername() . '@twitter.com') as $tweet) {
                    $statuses[] = $tweet;
                }
            }
        }

        usort($statuses, function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });

        return new \Amp\Http\Server\Response(200, ['content-type' => 'application/json'], json_encode($statuses));
    });

    $r->addRoute('GET', '/api/v1/statuses/twitter.com:{twitterUsername:.+}:{statusId:\d+}[/]', function (string $twitterUsername, string $statusId) use ($container) {
        $token = explode(' ', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (count($token) !== 2 || $token[0] !== 'Bearer') {
            return new \Amp\Http\Server\Response(401, ['content-type' => 'text/html'], 'Unauthorized');
        }

        /** @var \App\AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $container->get(\App\AccessTokenRepository::class);
        $accessToken = $accessTokenRepository->findAccessToken($token[1]);
        if (!$accessToken) {
            return new \Amp\Http\Server\Response(401, ['content-type' => 'text/html'], 'Unauthorized');
        }

        /** @var \App\NitterScraper $nitterScraper */
        $nitterScraper = $container->get(\App\NitterScraper::class);

        return new \Amp\Http\Server\Response(200, ['content-type' => 'application/json'], json_encode($nitterScraper->getTweet($statusId)));
    });

    $r->addRoute('GET', '/api/{param:.+}', function (string $param) use ($container) {
        $url = parse_url($_SERVER['REQUEST_URI']);
        $query = $url['query'] ?? '';
        $mastodonRequestUri = str_replace('/mastodon/', '', $url['path']);
        error_log($mastodonRequestUri);

        /** @var \App\NitterScraper $nitterScraper */
        $nitterScraper = $container->get(\App\NitterScraper::class);
        if ($mastodonRequestUri === '/api/v2/search' && preg_match('/@(.+)@twitter.com/', $_GET['q'])) {
            $twitterAccounts = $nitterScraper->searchAccounts($_GET['q'], $_GET['resolve'] ?? false);

            if (count($twitterAccounts) > 0) {
                /** @var \App\UserRepository $userRepository */
                $userRepository = $container->get(\App\UserRepository::class);
                $tweeterIDDotComAPI = $container->get(\App\TweeterIDDotComAPI::class);
                /** @var \App\TweeterIDDotComAPI $tweeterIDDotComAPI */
                if ($twitterAccounts[0]['twitter_id'] === null) {
                    $twitterAccounts[0]['twitter_id'] = $tweeterIDDotComAPI->getTwitterID($twitterAccounts[0]['username']);
                }
                $user = $userRepository->findByIdOnInstance(intval($twitterAccounts[0]['twitter_id']), 'twitter.com');
                if (null === $user) {
                    if (!in_array($twitterAccounts[0]['twitter_id'], [null, 0, 'error-em'])) {
                        $userRepository->save($twitterAccounts[0]['username'], 'twitter.com', intval($twitterAccounts[0]['twitter_id']));
                    }
                }
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
        } elseif (preg_match('|/api/v1/accounts/@(.+)@twitter.com|', $mastodonRequestUri, $matches)) {
            preg_match('/@(.+)@twitter.com/', $mastodonRequestUri, $matches);
            return new \Amp\Http\Server\Response(200, ['content-type' => 'application/json'],
                json_encode($nitterScraper->lookupAccount($matches[1])),
            );
        }elseif (preg_match('|/api/v1/accounts/relationships|', $mastodonRequestUri, $matches)) {
            if (preg_match('/@(.+)@twitter.com/', $query)) {
                return new \Amp\Http\Server\Response(200, ['content-type' => 'application/json'],
                    json_encode([]),
                );
            }
        } elseif (preg_match('|/api/v1/statuses/(.*)/context|', $mastodonRequestUri, $matches)) {
            $statusId = $matches[1];
            if (preg_match('/@(.+)@twitter.com/', $_SERVER['HTTP_REFERER'] ?? '', $matches)) {
                return new \Amp\Http\Server\Response(200, ['content-type' => 'application/json'],
                    json_encode($nitterScraper->getTweetContext($statusId)),
                );
            }
        } elseif (preg_match('|/api/v1/statuses/(.*)/|', $mastodonRequestUri, $matches)) {
            $statusId = $matches[1];
            if (preg_match('/@(.+)@twitter.com/', $_SERVER['HTTP_REFERER'] ?? '', $matches)) {
                return new \Amp\Http\Server\Response(200, ['content-type' => 'application/json'],
                    json_encode($nitterScraper->getTweet($statusId)),
                );
            }
        }

        return new \Amp\Http\Server\Response(421, ['content-type' => 'text/html'], 'Not implemented');
    });

    $r->addRoute('GET', '/proxy/video/{param:[A-F0-9]+}/{url:.+}', function (string $param, string $url) use ($container) {
        error_log('VIDEO ROUTE CORRECT ');
        $content = file_get_contents('https://nitter.net/video/' . $param . '/' . urlencode($url));

        return new \Amp\Http\Server\Response(200, ['content-type' => 'video/mp4'], $content);
    });

    $r->addRoute('GET', '/video/{param:[A-F0-9]+}/{url:.+}', function (string $param, string $url) use ($container) {
        error_log('VIDEO ROUTE CORRECT ');
        $content = file_get_contents('https://nitter.net/video/' . $param . '/' . urlencode($url));

        return new \Amp\Http\Server\Response(200, ['content-type' => 'video/mp4'], $content);
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
        $response = call_user_func_array($handler, $vars);
        http_response_code($response->getStatus());
        foreach ($response->getHeaders() as $header => $value) {
            header($header . ': ' . $value[0]);
        }

        \Amp\Loop::run(function () use ($response) {
            echo yield $response->getBody()->read();
        });
        break;
}