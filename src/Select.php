<?php

namespace PE\Component\Stream;

use PE\Component\Stream\Exception\RuntimeException;

final class Select implements SelectInterface
{
    public const DEFAULT_TIMEOUT_MS = 1000;

    private ?int $timeoutMs;

    /**
     * @var StreamInterface[]
     */
    private array $rdStreams = [];

    /**
     * @var callable[]
     */
    private array $rdHandlers = [];

    /**
     * @var StreamInterface[]
     */
    private array $wrStreams = [];

    /**
     * @var callable[]
     */
    private array $wrHandlers = [];

    public function __construct(int $timeoutMs = null)
    {
        $this->timeoutMs = $timeoutMs ?: self::DEFAULT_TIMEOUT_MS;
    }

    public function attachStreamRD(StreamInterface $stream, callable $listener): void
    {
        $key = (int) $stream->getResource();
        $this->rdStreams[$key] = $stream;
        $this->rdHandlers[$key] = $listener;
    }

    public function detachStreamRD(StreamInterface $stream): void
    {
        $key = (int) $stream->getResource();
        unset($this->rdStreams[$key], $this->rdHandlers[$key]);
    }

    public function attachStreamWR(StreamInterface $stream, callable $listener): void
    {
        $key = (int) $stream->getResource();
        $this->wrStreams[$key] = $stream;
        $this->wrHandlers[$key] = $listener;
    }

    public function detachStreamWR(StreamInterface $stream): void
    {
        $key = (int) $stream->getResource();
        unset($this->wrStreams[$key], $this->wrHandlers[$key]);
    }

    public function dispatch(int $timeoutMs = null): int
    {
        $timeout = $timeoutMs ?: $this->timeoutMs;

        // Cleanup dead streams
        foreach ($this->rdStreams as $stream) {
            if (!is_resource($stream->getResource())) {
                //TODO unit test
                $this->detachStreamRD($stream);
            }
        }
        foreach ($this->wrStreams as $stream) {
            if (!is_resource($stream->getResource())) {
                //TODO unit test
                $this->detachStreamWR($stream);
            }
        }

        // Extract resource pointers
        $rd = array_map(fn(StreamInterface $s) => $s->getResource(), $this->rdStreams);
        $wr = array_map(fn(StreamInterface $s) => $s->getResource(), $this->wrStreams);

        if ($rd || $wr) {
            // @codeCoverageIgnoreStart
            $previous = set_error_handler(function ($errno, $error) use (&$previous) {
                // suppress warnings that occur when `stream_select()` is interrupted by a signal
                if (E_WARNING === $errno && false !== strpos($error, '[' . SOCKET_EINTR .']: ')) {
                    return true;
                }

                // forward any other error to registered error handler or print warning
                return null !== $previous
                    ? call_user_func_array($previous, func_get_args())
                    : false;
            });
            // @codeCoverageIgnoreEnd

            try {
                $ex  = [];
                $num = stream_select($rd, $wr, $ex, null === $timeout ? null : 0, $timeout);
                restore_error_handler();
            } catch (\Throwable $e) {
                restore_error_handler();
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }

            if ($num) {
                foreach ($rd as $resource) {
                    if (isset($this->rdHandlers[(int) $resource])) {
                        call_user_func($this->rdHandlers[(int) $resource], $this->rdStreams[(int) $resource], $this);
                    }
                }
                foreach ($wr as $resource) {
                    if (isset($this->wrHandlers[(int) $resource])) {
                        call_user_func($this->wrHandlers[(int) $resource], $this->wrStreams[(int) $resource], $this);
                    }
                }
            }
            return $num;
        }
        return 0;
    }
}