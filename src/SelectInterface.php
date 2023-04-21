<?php

namespace PE\Component\Stream;

interface SelectInterface
{
    public function attachStreamRD(StreamInterface $stream, callable $listener): void;

    public function detachStreamRD(StreamInterface $stream): void;

    public function attachStreamWR(StreamInterface $stream, callable $listener): void;

    public function detachStreamWR(StreamInterface $stream): void;

    public function dispatch(int $timeoutMs = null): int;
}