<?php

require_once __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('direct_logs', 'direct', false, false, false);

list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);

$severities = array_slice($argv, 1);

if (empty($severities)) {
    file_put_contents('php://strerr', "Usage: $argv[0] [info] [warning] [error]\n");
}

foreach ($severities as $severity) {
    $channel->queue_bind($queue_name, 'direct_logs', $severity);
}

echo "[*] Waiting for logs. To exit press CRTL+C\n";

$callback = function ($msg) {
    echo ' [x] ' . $msg->delivery_info['routing_key'] . ': ' . $msg->body . PHP_EOL;
};

$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();