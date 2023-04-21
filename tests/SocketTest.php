<?php

namespace PE\Component\Stream\Tests;

use PE\Component\Stream\Exception\RuntimeException;
use PE\Component\Stream\SelectInterface;
use PE\Component\Stream\Socket;
use PE\Component\Stream\StreamInterface;
use PHPUnit\Framework\TestCase;

final class SocketTest extends TestCase
{
    public function testConstruct()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())->method('setBlocking')->with(false);
        $stream->expects(self::once())->method('setBufferRD')->with(0);

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::once())->method('attachStreamRD')->with($stream, self::isType('callable'));

        new Socket($stream, $select);
    }

    public function testOnInputError()
    {
        $onSelect = null;

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())->method('recvData')->willThrowException($e = new RuntimeException());

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::once())->method('attachStreamRD')->willReturnCallback(function ($s, $h) use (&$onSelect) {
            $onSelect = $h;
        });

        $socket = new Socket($stream, $select);
        $socket->onError(fn($data) => self::assertSame($e, $data));

        $onSelect();
    }

    public function testOnInputClose()
    {
        $onSelect = null;

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())->method('recvData')->willReturn('');
        $stream->expects(self::once())->method('isEOF')->willReturn(true);
        $stream->expects(self::once())->method('close');

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::once())->method('attachStreamRD')->willReturnCallback(function ($s, $h) use (&$onSelect) {
            $onSelect = $h;
        });

        $socket = new Socket($stream, $select);
        $socket->onClose(fn($data) => self::assertStringStartsWith('Disconnect', $data));

        $onSelect();
    }

    public function testOnInput()
    {
        $onSelect = null;

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())->method('recvData')->willReturn('DATA');

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::once())->method('attachStreamRD')->willReturnCallback(function ($s, $h) use (&$onSelect) {
            $onSelect = $h;
        });

        $socket = new Socket($stream, $select);
        $socket->onInput(fn($data) => self::assertSame('DATA', $data));

        $onSelect();
    }

    public function testWriteClose()
    {
        $stream = $this->createMock(StreamInterface::class);
        $select = $this->createMock(SelectInterface::class);

        $socket = new Socket($stream, $select);
        $socket->onClose(fn($data) => self::assertStringStartsWith('Disconnect', $data));
        $socket->write('DATA');
    }

    public function testWriteEmpty()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())->method('getResource')->willReturn(fopen('php://temp', 'w+'));

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::never())->method('attachStreamWR');

        $socket = new Socket($stream, $select);
        $socket->write('');
    }

    public function testWriteError()
    {
        $onSelect = null;

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())->method('getResource')->willReturn(fopen('php://temp', 'w+'));
        $stream->expects(self::once())->method('sendData')->willThrowException($e = new RuntimeException());

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::once())->method('attachStreamWR')->willReturnCallback(function ($s, $h) use (&$onSelect) {
            $onSelect = $h;
        });

        $socket = new Socket($stream, $select);
        $socket->onError(fn($data) => self::assertSame($e, $data));
        $socket->write('DATA');

        $onSelect();
    }

    public function testWrite()
    {
        $onSelect = null;

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())->method('getResource')->willReturn(fopen('php://temp', 'w+'));
        $stream->expects(self::once())->method('sendData')->with('DATA')->willReturn(4);

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::once())->method('detachStreamWR');
        $select->expects(self::once())->method('attachStreamWR')->willReturnCallback(function ($s, $h) use (&$onSelect) {
            $onSelect = $h;
        });

        $socket = new Socket($stream, $select);
        $socket->onWrite(fn($data) => self::assertSame('DATA', $data));
        $socket->write('DATA');

        $onSelect();
    }
}