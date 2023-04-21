<?php

namespace PE\Component\Stream;

use PE\Component\Stream\Exception\RuntimeException;

final class Socket implements SocketInterface
{
    private \Closure $onError;
    private \Closure $onInput;
    private \Closure $onWrite;
    private \Closure $onClose;

    private array $buffer = [];

    private StreamInterface $stream;
    private SelectInterface $select;

    public function __construct(StreamInterface $stream, SelectInterface $select)
    {
        $this->stream = $stream;
        $this->stream->setBlocking(false);
        $this->stream->setBufferRD(0);

        $this->select = $select;
        $this->select->attachStreamRD($stream, function () {
            try {
                $data = $this->stream->recvData();
            } catch (RuntimeException $exception) {
                call_user_func($this->onError, $exception);
                return;
            }

            if ($data !== '') {
                call_user_func($this->onInput, $data);
            } elseif ($this->stream->isEOF()) {
                $this->close('Disconnected on RD');
            }
        });

        $this->onError = fn() => null;// Dummy callback
        $this->onInput = fn() => null;// Dummy callback
        $this->onWrite = fn() => null;// Dummy callback
        $this->onClose = fn() => null;// Dummy callback
    }

    public function onError(callable $handler): void
    {
        $this->onError = \Closure::fromCallable($handler);
    }

    public function onInput(callable $handler): void
    {
        $this->onInput = \Closure::fromCallable($handler);
    }

    public function onWrite(callable $handler): void
    {
        $this->onWrite = \Closure::fromCallable($handler);
    }

    public function onClose(callable $handler): void
    {
        $this->onClose = \Closure::fromCallable($handler);
    }

    public function write(string $data): void
    {
        if (!is_resource($this->stream->getResource())) {
            $this->close('Disconnected on WR');
            return;
        }

        if (empty($data)) {
            return;
        }

        $this->buffer[] = $data;
        $this->select->attachStreamWR($this->stream, function () {
            $data = array_shift($this->buffer);
            call_user_func($this->onWrite, $data);

            try {
                $this->stream->sendData($data);
            } catch (RuntimeException $exception) {
                call_user_func($this->onError, $exception);
                return;
            }

            if (empty($this->buffer)) {
                $this->select->detachStreamWR($this->stream);
            }
        });
    }

    public function close(string $message = null): void
    {
        call_user_func($this->onClose, $message ?: 'Disconnected');
        $this->stream->close();
    }
}