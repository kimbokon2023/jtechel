<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\Server;

class WebSocketServer implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
            $client->send($msg);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error occurred on connection {$conn->resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }
}

$loop = Factory::create();
$webSocket = new \React\Socket\Server('j-techel.co.kr:8080', $loop);

$webServer = new Server($loop);
$webServer->listen(8080, '0.0.0.0');

$webServer->on('upgrade', function ($request, $socket, $body) use ($webSocket) {
    $webSocket->handleUpgrade($request, $socket, $body)->then(function ($conn) use ($webSocket) {
        $webSocket->addConnection($conn);
    });
});

$webSocketServer = new WsServer(new WebSocketServer());

$webSocket->on('connection', function ($conn) use ($webSocketServer) {
    $webSocketServer->onOpen($conn);
});

$webSocketServer->enableKeepAlive($loop);

$webSocket->listen($webServer);

$loop->run();
?>
