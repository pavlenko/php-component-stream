<?php

namespace PE\Component\Stream;

require_once __DIR__ . '/../vendor/autoload.php';

$factory = new Factory();

$active = true;
$stream = $factory->createClient('127.0.0.1:9999');

$select = new Select();
$select->attachStreamWR($stream, function (Stream $stream, Select $select) use (&$active) {
    $select->detachStreamWR($stream);
    echo "!: Connected to remote {$stream->getAddress(true)}\n";

    $socket = new Socket($stream, $select);
    $socket->onInput(function (string $message) use ($socket) {
        echo 'I: ' . trim($message) . "\n";
        if ('HELLO' === trim($message)) {
            sleep(1);
            $socket->write("HELLO\n");
        }
    });
    $socket->onClose(function (string $message) use (&$active) {
        echo '!: ' . trim($message) . "\n";
        $active = false;
    });
});

while ($active) {
    $select->dispatch();
    usleep(1000);
}
