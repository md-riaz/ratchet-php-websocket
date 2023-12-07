<?php
// show all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require dirname(__DIR__) . '/vendor/autoload.php';


$context = new ZMQContext();
$socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher', function ($socket) {
    print_r($socket);
});

echo '<pre>';
print_r($socket);
echo '</pre>';
// exit;

$socket->connect("tcp://localhost:9000");

// $socket->on('error', function ($event) {
//     print_r($event);
//     die();
// });

$socket->send("Time is " . date('Y-m-d H:i:s'), ZMQ::MODE_NOBLOCK);

$socket->disconnect("tcp://localhost:9000");



echo "Sent: Time is " . date('Y-m-d H:i:s') . "\n";
