<?php

namespace PE\Component\Stream;

require_once __DIR__ . '/../vendor/autoload.php';

$factory = new Factory();

$master = $factory->createServer(9999);
$master->setBlocking(false);

$client = null;
$select = new Select();
$select->attachStreamRD($master, function (Stream $master, Select $select) use ($factory, &$client) {
    $stream = $factory->accept($master);
    echo "!: New connection from {$stream->getAddress(true)}\n";

    $socket = $client = new Socket($stream, $select);
    $socket->onInput(function (string $message) use ($socket) {
        echo 'I: ' . trim($message) . "\n";
        if ('HELLO' === trim($message)) {
            sleep(1);
            $socket->write("WELCOME\n");
        }
    });
    $socket->onClose(function (string $message) {
        echo '!: ' . trim($message) . "\n";
    });
    $socket->write("HELLO\n");
});

echo "!: Listening on {$master->getAddress()}\n";

$time = time();
while (true) {
    if (time() - $time > 5 && $client) {
        $client->close();
        $client = null;
    }
    $select->dispatch();
    usleep(1000);
}
