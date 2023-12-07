<?php

use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\Chat;
use React\ZMQ\Context;
use React\Socket\SocketServer;

require dirname(__DIR__) . '/vendor/autoload.php';

$loop   = React\EventLoop\Loop::get();
$pusher = new Chat;

// Listen for the web server to make a ZeroMQ push after an ajax request
$context = new Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind('tcp://127.0.0.1:9000'); // Binding to 127.0.0.1 means the only client that can connect is itself
$pull->on('message', array($pusher, 'handleZmqMessage'));

// Set up our WebSocket server for clients wanting real-time updates
$webSock = new SocketServer('0.0.0.0:8080'); // Binding to 0.0.0.0 means remotes can connect

$webServer = new Ratchet\Server\IoServer(
    new HttpServer(
        new WsServer(
            $pusher
        )
    ),
    $webSock
);

$loop->run();

// run with php7.4 bin/server.php
