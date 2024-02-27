<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Ratchet\WebSocket\WsServer;
use React\Socket\SocketServer;
use Ratchet\Http\HttpServer;
use React\ZMQ\Context;
use Ratchet\Server\IoServer;
use React\EventLoop\Loop;
use MyApp\Chat;


$loop = Loop::get();
$chatWSServer = new Chat();

// Listen for the web server to make a ZeroMQ push after an ajax request
$context = new Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);

try {
    $pull->bind('tcp://127.0.0.1:9000'); // Binding to 127.0.0.1 means the only client that can connect is itself
} catch (ZMQSocketException $e) {
    echo "ZMQSocketException: " . $e->getMessage() . PHP_EOL;
    die();
}

$pull->on('message', [$chatWSServer, 'onZmqMessage']);

// Set up our WebSocket server for clients wanting real-time updates
$webSock = new SocketServer('0.0.0.0:8080'); // Binding to 0.0.0.0 means remotes can connect

$wsServer = new WsServer($chatWSServer);
$webServer = new IoServer(
    new HttpServer($wsServer),
    $webSock
);

$wsServer->enableKeepAlive($loop); // Pass the event loop to enableKeepAlive

$loop->run();
