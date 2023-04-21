<?php

namespace PE\Component\Stream;

use PE\Component\Stream\Exception\RuntimeException;

final class Stream implements StreamInterface
{
    private $resource;

    /**
     * Create stream with specified resource
     *
     * @param resource $resource
     */
    public function __construct($resource)
    {
        if (!is_resource($resource) || get_resource_type($resource) !== 'stream') {
            throw new RuntimeException('First parameter must be a valid stream resource');
        }
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getAddress(bool $remote = false): string
    {
        $name = stream_socket_get_name($this->resource, $remote);
        $type = $this->getMetadata()['stream_type'];

        if (false === $name) {
            $error = 'Cannot get stream socket name of type ' . $type;
            $errno = 0;
            if ($type === 'tcp_socket/ssl') {
                // @codeCoverageIgnoreStart
                $socket = socket_import_stream($this->resource);
                $errno  = socket_get_option($socket, SOL_SOCKET, SO_ERROR);
                $error  = socket_strerror($errno);
                // @codeCoverageIgnoreEnd
            }

            $this->close();
            throw new RuntimeException($error, $errno);
        }

        return $name;
    }

    public function setTimeout(int $seconds, int $micros = 0): self
    {
        if (!stream_set_timeout($this->resource, $seconds, $micros)) {
            throw new RuntimeException('Cannot set read/write timeout');
        }
        return $this;
    }

    public function setBlocking(bool $enable): self
    {
        if (!stream_set_blocking($this->resource, $enable)) {
            throw new RuntimeException('Cannot set blocking mode');
        }
        return $this;
    }

    public function setBufferRD(int $size): void
    {
        if (0 !== stream_set_read_buffer($this->resource, $size)) {
            throw new RuntimeException('Cannot set read buffer');
        }
    }

    public function setBufferWR(int $size): void
    {
        if (0 !== stream_set_write_buffer($this->resource, $size)) {
            throw new RuntimeException('Cannot set write buffer');
        }
    }

    public function getMetadata(): array
    {
        return stream_get_meta_data($this->resource);
    }

    public function getOptions(): array
    {
        return stream_context_get_options($this->resource);
    }

    public function setOptions(array $options): void
    {
        if (!stream_context_set_option($this->resource, $options)) {
            throw new RuntimeException('Cannot set options');
        }
    }

    public function isEOF(): bool
    {
        return feof($this->resource);
    }

    public function copyTo(StreamInterface $stream, int $length = 0, int $offset = 0): int
    {
        $pos = 0;
        while (!feof($this->resource) && (0 === $length || $pos < $length)) {
            $error = null;
            set_error_handler(function ($code, $message) use (&$error) {
                $error = new RuntimeException('Cannot copy stream data: ' . $message, $code);
            });

            $num = stream_copy_to_stream($this->resource, $stream->resource, 8192, $offset + $pos);
            restore_error_handler();

            if (null !== $error) {
                throw $error;
            }

            $pos += $num;
        }
        return $pos;
    }

    public function recvLine(int $length = null): string
    {
        $error = null;
        set_error_handler(function ($code, $message) use (&$error) {
            $error = new RuntimeException('Unable to read from stream: ' . $message, $code);
        });

        $data = stream_get_line($this->resource, $length);
        restore_error_handler();

        if (null !== $error) {
            $this->close();
            throw $error;
        }

        return $data;
    }

    public function recvData(int $length = null): string
    {
        $error = null;
        set_error_handler(function ($code, $message) use (&$error) {
            $error = new RuntimeException('Unable to read from stream: ' . $message, $code);
        });

        $data = stream_get_contents($this->resource, $length);
        restore_error_handler();

        if (null !== $error) {
            $this->close();
            throw $error;
        }

        return $data;
    }

    public function sendData(string $data, int $length = null): int
    {
        $error = null;
        set_error_handler(function ($_, $message) use (&$error) {
            $error = new RuntimeException('Unable to write to stream: ' . $message);
        });

        $num = @fwrite($this->resource, $data, $length ?: strlen($data));
        restore_error_handler();

        if (($num === 0 || $num === false) && $error !== null) {
            $this->close();
            throw $error;
        }

        return $num;
    }

    public function close(): void
    {
        if (is_resource($this->resource)) {
            @stream_socket_shutdown($this->resource, STREAM_SHUT_RDWR);
            @fclose($this->resource);
        }
    }
}
