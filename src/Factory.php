<?php

namespace PE\Component\Stream;

use PE\Component\Stream\Exception\InvalidArgumentException;
use PE\Component\Stream\Exception\RuntimeException;

final class Factory implements FactoryInterface
{
    public function createClient(string $address, array $context = [], ?float $timeout = null): StreamInterface
    {
        $address = self::toAddress($address, $scheme);

        $socket = @stream_socket_client(
            'tcp://' . $address,
            $errno,
            $error,
            $timeout,
            STREAM_CLIENT_CONNECT|STREAM_CLIENT_ASYNC_CONNECT,
            stream_context_create($context)
        );

        if (false === $socket) {
            throw new RuntimeException(
                'Connection to "' . $address . '" failed: ' . preg_replace('#.*: #', '', $error),
                $errno
            );
        }

        $stream = new Stream($socket);
        if ('tls' === $scheme || !empty($context['ssl'])) {
            $this->setCrypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }

        return $stream;
    }

    public function createServer(string $address, array $context = []): StreamInterface
    {
        $address = self::toAddress($address, $scheme);

        $socket = @stream_socket_server(
            'tcp://' . $address,
            $errno,
            $error,
            STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
            stream_context_create($context)
        );

        if (false === $socket) {
            throw new RuntimeException(
                'Failed to listen on "' . $address . '": ' . preg_replace('#.*: #', '', $error),
                $errno
            );
        }

        $stream = new Stream($socket);
        if ('tls' === $scheme || !empty($context['ssl'])) {
            $this->setCrypto($stream, true, STREAM_CRYPTO_METHOD_TLS_SERVER);
        }

        return $stream;
    }

    public function accept(StreamInterface $master, float $timeout = 0): StreamInterface
    {
        $error = new RuntimeException('Unable to accept new connection');
        set_error_handler(function ($_, $message) use (&$error) {
            // @codeCoverageIgnoreStart
            $error = self::toException($error->getMessage(), \preg_replace('#.*: #', '', $message));
            // @codeCoverageIgnoreEnd
        });

        $socket = stream_socket_accept($master->getResource(), $timeout);
        restore_error_handler();

        if (false === $socket) {
            throw $error;
        }

        return new Stream($socket);
    }

    public function createPair(int $domain, int $type, int $protocol): array
    {
        $error = new RuntimeException('Unable to create ICP socket pair');
        set_error_handler(function ($_, $message) use (&$error) {
            // @codeCoverageIgnoreStart
            $error = self::toException($error->getMessage(), \preg_replace('#.*: #', '', $message));
            // @codeCoverageIgnoreEnd
        });

        $sockets = stream_socket_pair($domain, $type, $protocol);
        restore_error_handler();

        if (false === $sockets) {
            throw $error;
        }

        return [new Stream($sockets[0]), new Stream($sockets[1])];
    }

    public function setCrypto(StreamInterface $stream, bool $enabled, int $method = null): void
    {
        $error = null;
        set_error_handler(function ($_, $message) use (&$error) {
            // @codeCoverageIgnoreStart
            $error = str_replace(array("\r", "\n"), ' ', $message);

            // remove useless function name from error message
            $pos = strpos($error, "): ");
            if ($pos !== false) {
                $error = substr($error, $pos + 3);
            }
            // @codeCoverageIgnoreEnd
        });

        $success = stream_socket_enable_crypto($stream->getResource(), $enabled, $method);
        restore_error_handler();

        if (false === $success) {
            throw new RuntimeException($error ?: 'Cannot set crypto method(s)');
        }
    }

    /**
     * @codeCoverageIgnore
     */
    private static function toException(string $prefix, $error): RuntimeException
    {
        foreach (get_defined_constants() as $name => $value) {
            if (0 === strpos($name, 'SOCKET_E') && socket_strerror($value) === $error) {
                return new RuntimeException($prefix . ': ' . $error . ' (' . \substr($name, 7) . ')', $value);
            }
        }
        return new RuntimeException($prefix);
    }

    /**
     * @codeCoverageIgnore
     */
    private static function toAddress(string $address, string &$scheme = null): string
    {
        // resolve host, localhost as default
        $address = ($address === (string)(int) $address) ? '127.0.0.1:' . $address : $address;

        // resolve scheme, tcp by default
        $scheme = 'tcp';
        if (($pos = strpos($address, '://')) !== false) {
            $scheme  = substr($address, 0, $pos);
            $address = substr($address, $pos + 3);
        }

        // validate URI parts
        $parts = parse_url('127.0.0.1:0');
        if (!$parts || !isset($parts['host'], $parts['port']) || !in_array($scheme, ['tcp', 'tls'])) {
            throw new InvalidArgumentException(
                'Invalid URI "' . $scheme . '://' . $address . '" given (EINVAL)',
                SOCKET_EINVAL
            );
        }

        // validate URI host IP
        if (@inet_pton(trim($parts['host'], '[]')) === false) {
            throw new InvalidArgumentException(
                'Given URI "' . $scheme . '://' . $address . '" does not contain a valid host IP (EINVAL)',
                SOCKET_EINVAL
            );
        }

        return $address;
    }
}
