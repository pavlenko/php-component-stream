<?php

namespace PE\Component\Stream;

use PE\Component\Stream\Exception\InvalidArgumentException;
use PE\Component\Stream\Exception\RuntimeException;

interface FactoryInterface
{
    /**
     * Create client socket
     *
     * @param string     $address Address to the socket to connect to.
     * @param array      $context Stream transport related context.
     * @param float|null $timeout Connection timeout.
     * @return Stream
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createClient(string $address, array $context = [], ?float $timeout = null): StreamInterface;

    /**
     * Create server socket
     *
     * @param string $address Address to the socket to listen to.
     * @param array  $context Stream transport related context.
     * @return StreamInterface
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createServer(string $address, array $context = []): StreamInterface;

    /**
     * Accept incoming connection on master stream, must be used immediately after stream_select() call
     *
     * @param StreamInterface $master
     * @param float $timeout
     * @return StreamInterface
     * @throws RuntimeException
     */
    public function accept(StreamInterface $master, float $timeout = 0): StreamInterface;

    /**
     * Create socket pair
     *
     * @param int $domain The protocol family to be used:<br>
     *   <b>STREAM_PF_INET</b>,<br>
     *   <b>STREAM_PF_INET6</b> or<br>
     *   <b>STREAM_PF_UNIX</b>
     * @param int $type The type of communication to be used:<br>
     *   <b>STREAM_SOCK_DGRAM</b>,<br>
     *   <b>STREAM_SOCK_RAW</b>,<br>
     *   <b>STREAM_SOCK_RDM</b>,<br>
     *   <b>STREAM_SOCK_SEQPACKET</b> or<br>
     *   <b>STREAM_SOCK_STREAM</b>
     * @param int $protocol The protocol to be used:<br>
     *   <b>STREAM_IPPROTO_ICMP</b>,<br>
     *   <b>STREAM_IPPROTO_IP</b>,<br>
     *   <b>STREAM_IPPROTO_RAW</b>,<br>
     *   <b>STREAM_IPPROTO_TCP</b> or<br>
     *   <b>STREAM_IPPROTO_UDP</b>
     *
     * @return StreamInterface[]
     * @throws RuntimeException
     */
    public function createPair(int $domain, int $type, int $protocol): array;

    /**
     * Turns encryption on/off
     *
     * @param StreamInterface $stream
     * @param bool $enabled
     * @param int|null $method
     * @throws RuntimeException
     */
    public function setCrypto(StreamInterface $stream, bool $enabled, int $method = null): void;
}