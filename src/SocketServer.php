<?php

namespace MyApp;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

define('DB_HOST', 'localhost');
define('DB_TYPE', 'pgsql'); // Values(postgres OR mysql OR sqlite)
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');

require_once 'Database.php';

class SocketServer implements MessageComponentInterface
{
    protected $clients;
    private $db;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;

        // Connect to the database
        $this->db = new Database();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // get token from uri
        $uri = $conn->httpRequest->getUri()->getQuery();

        $token = explode("=", $uri ?? "");

        $token = (isset($token[0], $token[1]) && $token[0] === 'token') ? $token[1] : null;

        $tokenInfo = $this->isValidToken($token);
        // Perform token validation and user authentication here
        if (!$tokenInfo) {
            echo "Unauthorized access. Closing connection.\n";
            $conn->close();
            return;
        }

        $conn->tokenInfo = $tokenInfo;

        // Store the new connection to send messages to later
        print_r("\n " . ($this->clients->count() + 1) . " ");
        $this->clients->attach($conn);

        echo "New connection! (ResourceID: {$conn->resourceId})\n";

        $conn->send(json_encode($tokenInfo));
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $numRecv = $this->clients->count();
        echo sprintf(
            'Connection %d sending message "%s" to %d other connection%s' . "\n",
            $from->resourceId,
            $msg,
            $numRecv - 1,
            $numRecv == 1 ? '' : 's'
        );

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
        print_r("Total connected clients: " . $this->clients->count() . " ");
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    public function handleZmqMessage($msg)
    {
        foreach ($this->clients as $client) {
            $client->send($msg);
        }
    }

    private function isValidToken( $token)
    {
        if (empty($token)) {
            return false;
        }

        $result = $this->db->query("SELECT * FROM token WHERE token = ? LIMIT 1", $token)->fetchArray();

        if (empty($result)) {
            return false;
        }

        return $result;
    }
}
