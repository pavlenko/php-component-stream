<?php

namespace PE\Component\Stream\Tests;

use PE\Component\Stream\Exception\RuntimeException;
use PE\Component\Stream\Stream;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

final class StreamTest extends TestCase
{
    use PHPMock;

    /**
     * @return resource
     */
    private function getResource()
    {
        return fopen('php://temp', 'w+');
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'get_resource_type');
        $f->expects(self::once())->willReturn('foo');

        new Stream($this->getResource());
    }

    public function testResource(): void
    {
        $resource = $this->getResource();
        $stream   = new Stream($resource);

        self::assertSame($resource, $stream->getResource());
        self::assertFalse($stream->isEOF());
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddressRemoteFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $stream = new Stream($this->getResource());
        self::assertNull($stream->getAddress(true));
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddressRemote(): void
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_get_name');
        $f->expects(self::once())->with(self::isType('resource'), true)->willReturn('address');

        $stream = new Stream($this->getResource());
        self::assertSame('address', $stream->getAddress(true));
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddressLocalFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $stream = new Stream($this->getResource());
        self::assertNull($stream->getAddress());
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddressLocal(): void
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_socket_get_name');
        $f->expects(self::once())->with(self::isType('resource'), false)->willReturn('address');

        $stream = new Stream($this->getResource());
        self::assertSame('address', $stream->getAddress());
    }

    public function testSetTimeoutFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_set_timeout');
        $f->expects(self::once())->willReturn(false);

        (new Stream($this->getResource()))->setTimeout(1);
    }

    public function testSetTimeoutSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_set_timeout');
        $f->expects(self::once())->willReturn(true);

        (new Stream($this->getResource()))->setTimeout(1);
    }

    public function testSetBlockingFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_set_blocking');
        $f->expects(self::once())->willReturn(false);

        (new Stream($this->getResource()))->setBlocking(true);
    }

    public function testSetBlockingSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_set_blocking');
        $f->expects(self::once())->willReturn(true);

        (new Stream($this->getResource()))->setBlocking(true);
    }

    public function testSetBufferRFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_set_read_buffer');
        $f->expects(self::once())->willReturn(1);

        (new Stream($this->getResource()))->setBufferRD(1);
    }

    public function testSetBufferRSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_set_read_buffer');
        $f->expects(self::once())->willReturn(0);

        (new Stream($this->getResource()))->setBufferRD(1);
    }

    public function testSetBufferWFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_set_write_buffer');
        $f->expects(self::once())->willReturn(1);

        (new Stream($this->getResource()))->setBufferWR(1);
    }

    public function testSetBufferWSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_set_write_buffer');
        $f->expects(self::once())->willReturn(0);

        (new Stream($this->getResource()))->setBufferWR(1);
    }

    public function testSetOptionsFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_context_set_option');
        $f->expects(self::once())->willReturn(false);

        (new Stream($this->getResource()))->setOptions([]);
    }

    public function testSetOptionsSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_context_set_option');
        $f->expects(self::once())->willReturn(true);

        (new Stream($this->getResource()))->setOptions([]);
    }

    public function testGetOptions(): void
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_context_get_options');
        $f->expects(self::once())->willReturn([]);

        (new Stream($this->getResource()))->getOptions();
    }

    public function testGetMetadata(): void
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_get_meta_data');
        $f->expects(self::once())->willReturn([]);

        (new Stream($this->getResource()))->getMetadata();
    }

    public function testCopyToFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_copy_to_stream');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Stream($this->getResource()))->copyTo(new Stream($this->getResource()));
    }

    public function testCopyToSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_copy_to_stream');
        $f->expects(self::once())->willReturn(1);

        (new Stream($this->getResource()))->copyTo(new Stream($this->getResource()), 1);
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadLineFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_get_line');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Stream($this->getResource()))->recvLine();
    }

    public function testReadLineSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_get_line');
        $f->expects(self::once())->willReturn('');

        (new Stream($this->getResource()))->recvLine();
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadDataFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_get_contents');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Stream($this->getResource()))->recvData();
    }

    public function testReadDataSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_get_contents');
        $f->expects(self::once())->willReturn('');

        (new Stream($this->getResource()))->recvData();
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendDataFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'fwrite');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        (new Stream($this->getResource()))->sendData('D');
    }

    public function testSendDataSuccess(): void
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'fwrite');
        $f->expects(self::once())->willReturn(1);

        (new Stream($this->getResource()))->sendData('D');
    }

    public function testCloseSkipped(): void
    {
        $f1 = $this->getFunctionMock('PE\Component\Stream', 'is_resource');
        $f1->expects(self::once())->willReturn(false);

        $f2 = $this->getFunctionMock('PE\Component\Stream', 'fclose');
        $f2->expects(self::never());

        (new Stream($this->getResource()))->close();
    }

    public function testCloseSuccess(): void
    {
        $f1 = $this->getFunctionMock('PE\Component\Stream', 'is_resource');
        $f1->expects(self::once())->willReturn(true);

        $f2 = $this->getFunctionMock('PE\Component\Stream', 'fclose');
        $f2->expects(self::once());

        (new Stream($this->getResource()))->close();
    }
}
