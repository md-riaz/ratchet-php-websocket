<?php

namespace MyApp;

use Ratchet\WebSocket\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        switch ($data['type']) {
            case 'offer':
            case 'answer':
            case 'candidate':
            case 'call-end':
                // Broadcast the signaling message to all clients except the sender
                foreach ($this->clients as $client) {
                    if ($client !== $from) {
                        $client->send($msg);
                    }
                }
                break;
            default:
                // Handle other message types (if any)
                $message = [
                    'type' => 'message',
                    'user' => $data['user'],
                    'content' => $data['content'],
                ];

                foreach ($this->clients as $client) {
                    $client->send(json_encode($message));
                }
                break;
        }
    }

    //handleZmqMessage
    public function onZmqMessage($msg)
    {
        $data = json_decode($msg, true);
        $message = [
            'type' => 'message',
            'user' => $data['user'],
            'content' => $data['content'],
        ];

        foreach ($this->clients as $client) {
            $client->send(json_encode($message));
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
