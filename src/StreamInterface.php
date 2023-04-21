<?php

namespace PE\Component\Stream;

use PE\Component\Stream\Exception\RuntimeException;

interface StreamInterface
{
    /**
     * Get resource pointer if available
     *
     * @return resource|null
     * @internal DO NOT USE IN APP DIRECTLY
     */
    public function getResource();

    public function getAddress(bool $remote = false);

    /**
     * Set read/write timeout
     *
     * @param int $seconds
     * @param int $micros
     *
     * @return Stream
     */
    public function setTimeout(int $seconds, int $micros = 0): self;

    /**
     * Set blocking/non-blocking mode
     *
     * @param bool $enable
     *
     * @return Stream
     */
    public function setBlocking(bool $enable): self;

    /**
     * Set the read buffer
     *
     * @param int $size The number of bytes to buffer. If <b>$size</b> is 0 then operations are unbuffered
     */
    public function setBufferRD(int $size): void;

    /**
     * Set the write buffer
     *
     * @param int $size The number of bytes to buffer. If <b>$size</b> is 0 then operations are unbuffered
     */
    public function setBufferWR(int $size): void;

    /**
     * Retrieves header/metadata
     *
     * @return array
     */
    public function getMetadata(): array;

    /**
     * Get options in format:
     * <code>
     * $options = [
     *     'wrapper_name' => ['option_name' => $value, ...],
     *     ...
     * ]
     * </code>
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Set options in format:
     * <code>
     * $options = [
     *     'wrapper_name' => ['option_name' => $value, ...],
     *     ...
     * ]
     * </code>
     *
     * @param array $options
     */
    public function setOptions(array $options): void;

    /**
     * Check if stream closed by remote
     *
     * @return bool
     */
    public function isEOF(): bool;

    /**
     * Copy stream data to another one
     *
     * @param Stream $stream
     * @param int    $length
     * @param int    $offset
     * @return int
     * @throws RuntimeException
     */
    public function copyTo(self $stream, int $length = 0, int $offset = 0): int;

    /**
     * Read line from stream until reach $length or EOL or EOF
     *
     * @param int|null $length
     * @return string
     * @throws RuntimeException
     */
    public function recvLine(int $length = null): string;

    /**
     * Read data from stream until reach $limit or EOL
     *
     * @param int|null $length
     * @return string
     * @throws RuntimeException
     */
    public function recvData(int $length = null): string;

    /**
     * Send data to stream (can be truncated if length greater than $length)
     *
     * @param string   $data
     * @param int|null $length
     *
     * @return int
     */
    public function sendData(string $data, int $length = null): int;

    /**
     * Close stream
     */
    public function close(): void;
}