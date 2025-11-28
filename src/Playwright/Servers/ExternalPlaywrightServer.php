<?php

declare(strict_types=1);

namespace Pest\Browser\Playwright\Servers;

use Pest\Browser\Contracts\PlaywrightServer;
use RuntimeException;

/**
 * @internal
 */
final class ExternalPlaywrightServer implements PlaywrightServer
{
    private static ?PlaywrightServer $instance = null;

    private function __construct(private string $host, private int $port)
    {
        //
    }

    /**
     * Gets the singleton instance of the external Playwright server.
     */
    public static function instance(): self
    {
        if (! self::$instance) {
            throw new RuntimeException('An external Playwright server has not been defined.');
        }

        return self::$instance;
    }

    /**
     * Instruct Pest to use an external Playwright server.
     */
    public static function use(string $host, int $port): void
    {
        self::$instance = new self($host, $port);
    }

    /**
     * Determines whether an external Playwright server has been defined.
     */
    public static function isDefined(): bool
    {
        return isset(self::$instance);
    }

    /**
     * Starts the process until the given "output" condition is met.
     */
    public function start(): void
    {
        //
    }

    /**
     * Stops the process if it is running.
     */
    public function stop(): void
    {
        //
    }

    /**
     * Returns the URL of the process.
     */
    public function url(): string
    {
        return sprintf('%s:%d', $this->host, $this->port);
    }
}
