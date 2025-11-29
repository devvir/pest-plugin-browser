<?php

declare(strict_types=1);

namespace Pest\Browser;

use Pest\Browser\Contracts\HttpServer;
use Pest\Browser\Contracts\PlaywrightServer;
use Pest\Browser\Drivers\LaravelHttpServer;
use Pest\Browser\Drivers\NullableHttpServer;
use Pest\Browser\Playwright\Servers\AlreadyStartedPlaywrightServer;
use Pest\Browser\Playwright\Servers\ExternalPlaywrightServer;
use Pest\Browser\Playwright\Servers\PlaywrightNpmServer;
use Pest\Browser\Support\PackageJsonDirectory;
use Pest\Browser\Support\Port;
use Pest\Plugins\Parallel;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
final class ServerManager
{
    /**
     * The default host for the server.
     */
    public const string DEFAULT_HOST = '127.0.0.1';

    /**
     * For external Playwright server, bind to all network interfaces.
     */
    public const string PUBLIC_HOST = '0.0.0.0';

    /**
     * The singleton instance of the server manager.
     */
    private static ?ServerManager $instance = null;

    /**
     * The Playwright server process.
     */
    private ?PlaywrightServer $playwright = null;

    /**
     * The HTTP server process.
     */
    private ?HttpServer $http = null;

    /**
     * Gets the singleton instance of the server manager.
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Determines whether Playwright is running as an external dependency.
     */
    public function isPlaywrightExternal(): bool
    {
        return ExternalPlaywrightServer::isDefined();
    }

    /**
     * Returns the Playwright server process instance.
     */
    public function playwright(): PlaywrightServer
    {
        if ($this->isPlaywrightExternal()) {
            return ExternalPlaywrightServer::instance();
        }

        if (Parallel::isWorker()) {
            return AlreadyStartedPlaywrightServer::fromPersisted();
        }

        $port = Port::find();

        $this->playwright ??= PlaywrightNpmServer::create(
            PackageJsonDirectory::find(),
            '.'.DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR.'.bin'.DIRECTORY_SEPARATOR.'playwright run-server --host %s --port %d --mode launchServer',
            self::DEFAULT_HOST,
            $port,
            'Listening on',
        );

        AlreadyStartedPlaywrightServer::persist(
            self::DEFAULT_HOST,
            $port,
        );

        return $this->playwright;
    }

    /**
     * Returns the HTTP server process instance.
     */
    public function http(): HttpServer
    {
        return $this->http ??= match (function_exists('app_path')) {
            true => new LaravelHttpServer(
                host: $this->isPlaywrightExternal() ? self::PUBLIC_HOST : self::DEFAULT_HOST,
                port: Port::find(),
                publicHost: $this->isPlaywrightExternal() ? gethostbyname(gethostname()) : null,
            ),
            default => new NullableHttpServer(),
        };
    }
}
