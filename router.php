<?php

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
        $filePath != __DIR__ . DIRECTORY_SEPARATOR . 'router.php' &&
        substr(basename($filePath), 0, 1) != '.'
    ) {
        if (strtolower(substr($filePath, -4)) == '.php') {
            // php file; serve through interpreter
            include $filePath;
        } else {
            // asset file; serve from filesystem
            return false;
        }
    } else {
        // disallowed file
        header("HTTP/1.1 404 Not Found");
        echo "404 Not Found";
    }
} elseif (preg_match('/@(.+)@(.+)\.([a-z]+)/', $_SERVER['REQUEST_URI'], $matches)) {
    $_GET['user'] = $matches[0];
    include 'user.php';
} elseif (preg_match('/\/login/', $_SERVER['REQUEST_URI'])) {
    chdir('login');
    include 'login/login.php';
} elseif (preg_match('/\/logout/', $_SERVER['REQUEST_URI'])) {
    chdir('login');
    include 'login/logout.php';
} elseif (preg_match('/\/auth/', $_SERVER['REQUEST_URI'])) {
    chdir('login');
    include 'login/auth.php';
} elseif (preg_match('/\/federated/', $_SERVER['REQUEST_URI'])) {
    include 'federated.php';
} elseif (preg_match('/\/search/', $_SERVER['REQUEST_URI'])) {
    include 'search_hash_tag.php';
} elseif (preg_match('/\/notifications/', $_SERVER['REQUEST_URI'])) {
    include 'notifications.php';
} elseif (preg_match('/\/local/', $_SERVER['REQUEST_URI'])) {
    include 'local.php';
} else {
    // rewrite to our index file
    include __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
}
chdir(__DIR__);
