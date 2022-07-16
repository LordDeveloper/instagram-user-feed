<?php

declare(strict_types=1);

use Instagram\Api;
use Instagram\Auth\Checkpoint\ImapClient;
use Instagram\Exception\InstagramException;

use Psr\Cache\CacheException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

require realpath(dirname(__FILE__)) . '/../vendor/autoload.php';
$credentials = include_once realpath(dirname(__FILE__)) . '/credentials.php';

$cachePool = new FilesystemAdapter('Instagram', 0, __DIR__ . '/../cache');

try {
    $api = new Api($cachePool);
    $imapClient = new ImapClient($credentials->getImapServer(), $credentials->getImapLogin(), $credentials->getImapPassword());
    $api->login($credentials->getLogin(), $credentials->getPassword(), $imapClient);

    $newPassword = 'pass#word';

    $change = $api->changePassword($credentials->getLogin(), $credentials->getPassword(), $newPassword);

    echo $change . PHP_EOL;

} catch (InstagramException $e) {
    print_r($e->getMessage());
} catch (CacheException $e) {
    print_r($e->getMessage());
}
