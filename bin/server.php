<?php

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\SocketServer;
use React\ZMQ\Context;

require dirname(__DIR__) . '/vendor/autoload.php';

$loop = React\EventLoop\Loop::get();
$socketServer = new \MyApp\SocketServer();

// Listen for the web server to make a ZeroMQ push after an ajax request
$context = new Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);

try {
    $pull->bind('tcp://127.0.0.1:9000'); // Binding to 127.0.0.1 means the only client that can connect is itself
} catch (ZMQSocketException $e) {
    echo "ZMQSocketException: " . $e->getMessage() . PHP_EOL;
    die();
}

$pull->on('message', array($socketServer, 'handleZmqMessage'));

// Set up our WebSocket server for clients wanting real-time updates

$webSock = new SocketServer('0.0.0.0:8080'); // Binding to 0.0.0.0 means remotes can connect
$wsServer = new WsServer($socketServer);
$webServer = new IoServer(
    new HttpServer($wsServer),
    $webSock
);

$wsServer->enableKeepAlive($loop); // Pass the event loop to enableKeepAlive

$loop->run();


// run with ulimit -n 10000; exec php7.4 /var/www/websocket/bin/server.php
// php7.4 /usr/local/bin/composer
