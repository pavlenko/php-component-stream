<?php

namespace PE\Component\Stream\Tests;

use PE\Component\Stream\Exception\RuntimeException;
use PE\Component\Stream\Factory;
use PE\Component\Stream\Stream;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

final class FactoryTest extends TestCase
{
    use PHPMock;

    public function testCreateClientFailure()
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_client');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Factory())->createClient('127.0.0.1:9999');
    }

    public function testCreateClient()
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_client');
        $f->expects(self::once())->willReturn($r = fopen('php://temp', 'w+'));

        $stream = (new Factory())->createClient('127.0.0.1:9999');
        self::assertSame($r, $stream->getResource());
    }

    public function testCreateClientSecure()
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_client');
        $f->expects(self::once())->willReturn($r = fopen('php://temp', 'w+'));

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_enable_crypto');
        $f->expects(self::once())->willReturn(true);

        $stream = (new Factory())->createClient('tls://127.0.0.1:9999');
        self::assertSame($r, $stream->getResource());
    }

    public function testCreateServerFailure()
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_server');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Factory())->createServer('127.0.0.1:9999');
    }

    public function testCreateServer()
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_server');
        $f->expects(self::once())->willReturn($r = fopen('php://temp', 'w+'));

        $stream = (new Factory())->createServer('127.0.0.1:9999');
        self::assertSame($r, $stream->getResource());
    }

    public function testCreateServerSecure()
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_server');
        $f->expects(self::once())->willReturn($r = fopen('php://temp', 'w+'));

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_enable_crypto');
        $f->expects(self::once())->willReturn(true);

        $stream = (new Factory())->createServer('tls://127.0.0.1:9999');
        self::assertSame($r, $stream->getResource());
    }

    public function testAcceptFailure()
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_accept');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Factory())->accept(new Stream(fopen('php://temp', 'w+')));
    }

    public function testAccept()
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_accept');
        $f->expects(self::once())->willReturn($r = fopen('php://temp', 'w+'));

        $stream = (new Factory())->accept(new Stream(fopen('php://temp', 'w+')));
        self::assertSame($r, $stream->getResource());
    }

    public function testCreatePairFailure()
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_pair');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Factory())->createPair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    }

    public function testCreatePair()
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_pair');
        $f->expects(self::once())->willReturn([
            $r1 = fopen('php://temp', 'w+'),
            $r2 = fopen('php://temp', 'w+'),
        ]);

        $pair = (new Factory())->createPair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertSame($r1, $pair[0]->getResource());
        self::assertSame($r2, $pair[1]->getResource());
    }

    public function testSetCryptoFailure()
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_enable_crypto');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Factory())->setCrypto(new Stream(fopen('php://temp', 'w+')), true);
    }

    public function testSetCrypto()
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_enable_crypto');
        $f->expects(self::once())->willReturn(true);

        (new Factory())->setCrypto(new Stream(fopen('php://temp', 'w+')), true);
    }
}