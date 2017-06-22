<?php

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

require_once __DIR__ . "/vendor/autoload.php";

class Pusher implements MessageComponentInterface
{

    private $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
    }

    function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
    }

    function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
    }

    function onMessage(ConnectionInterface $from, $msg)
    {
        // Don't handle any messages from clients yet
    }

    public function push($json)
    {
        if ($json) {
            foreach ($this->clients as $client) {
                $client->send($json);
            }
        }
    }
}

$redisClient = new Predis\Client("tcp://localhost:6379");
$pusher = new Pusher();
$wsServer = new Ratchet\WebSocket\WsServer($pusher);
$httpServer = new \Ratchet\Http\HttpServer($wsServer);
$socketServer = Ratchet\Server\IoServer::factory($httpServer, 8081);

$socketServer->loop->addPeriodicTimer(
    0.05, // every 50 ms push data to clients
    function () use ($pusher, $redisClient) {
        $start = microtime(true);
        $pusher->push(
            $redisClient->get('galaxy-simple-json-Andromeda')
        );
        $end = microtime(true);

        printf("Looped in %s ms." . PHP_EOL, round(($end - $start) * 1000, 3));
    }
);

$socketServer->run();
