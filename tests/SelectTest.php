<?php

namespace PE\Component\Stream\Tests;

use PE\Component\Stream\Exception\RuntimeException;
use PE\Component\Stream\Select;
use PE\Component\Stream\Stream;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

final class SelectTest extends TestCase
{
    use PHPMock;

    public function testSelectFailure()
    {
        $this->expectException(RuntimeException::class);

        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_select');
        $f->expects(self::once())->willReturnCallback(fn() => !trigger_error('ERR'));

        $select = new Select();
        $select->attachStreamRD(new Stream(fopen('php://temp', 'w+')), fn() => null);
        $select->dispatch();
    }

    public function testSelect()
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_select');
        $f->expects(self::once())->willReturn(2);

        $select = new Select();
        $select->attachStreamRD(new Stream(fopen('php://temp', 'w+')), fn() => self::assertTrue(true));
        $select->attachStreamWR(new Stream(fopen('php://temp', 'w+')), fn() => self::assertTrue(true));
        $select->dispatch();
    }

    /**
     * @runInSeparateProcess
     */
    public function testCleanup()
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_select');
        $f->expects(self::never());

        $select = new Select();
        $select->attachStreamRD($s1 = new Stream(fopen('php://temp', 'w+')), fn() => self::assertTrue(true));
        $select->attachStreamWR($s2 = new Stream(fopen('php://temp', 'w+')), fn() => self::assertTrue(true));
        $s1->close();
        $s2->close();
        $select->dispatch();
    }

    public function testDetach()
    {
        $f = $this->getFunctionMock('PE\Component\Stream', 'stream_select');
        $f->expects(self::never());

        $select = new Select();

        $select->attachStreamRD($rd = new Stream(fopen('php://temp', 'w+')), fn() => self::assertTrue(true));
        $select->attachStreamWR($wr = new Stream(fopen('php://temp', 'w+')), fn() => self::assertTrue(true));

        $select->detachStreamRD($rd);
        $select->detachStreamWR($wr);

        $select->dispatch();
    }
}