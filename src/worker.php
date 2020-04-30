<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');

$channel = $connection->channel();

$channel->queue_declare('task_queue', false, false, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

/**
 * @param AMQPMessage $msg
 */
$callback = function ($msg) {
    echo ' [x] Received ', $msg->body, "\n";
    sleep(substr_count($msg->body, '.'));
    //for acknowledging after you are done
    $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
    echo " [x] Done\n";
};

//$channel->basic_consume('hello', '', false, true, false, false, $callback); //no acknowledgement
$channel->basic_consume('hello', '', false, false, false, false, $callback); //acknowledgement

//run few consumers in terminal to achieve faster message consuming
while ($channel->is_consuming()) {
    $channel->wait();
}