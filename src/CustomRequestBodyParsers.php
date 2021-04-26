<?php

declare(strict_types=1);

namespace WyriHaximus\React\Http\Middleware;

use Ancarda\Psr7\StringStream\StringStream;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

use function assert;
use function explode;
use function is_array;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function React\Promise\reject;
use function Safe\json_decode;
use function Safe\simplexml_load_string;
use function strtolower;

final class CustomRequestBodyParsers
{
    private const XML_PARSE_FAILED = false;

    /** @var array<mixed> */
    private array $types = [];

    public function __construct()
    {
        /**
         * Via: https://github.com/reactphp/http/pull/220#discussion_r140863176.
         */
        $this->addType('application/json', static function (ServerRequestInterface $request): ServerRequestInterface {
            $body   = (string) $request->getBody();
            $result = json_decode($body, true);
            if (! is_array($result)) {
                return $request;
            }

            return $request->withParsedBody($result)->withBody(new StringStream($body));
        });

        /**
         * Via: https://github.com/reactphp/http/pull/220#discussion_r140863176.
         */
        $xmlParser = static function (ServerRequestInterface $request): ServerRequestInterface {
            $body         = (string) $request->getBody();
            $backupErrors = libxml_use_internal_errors(true);
            $result       = simplexml_load_string($body);
            libxml_clear_errors();
            libxml_use_internal_errors($backupErrors);

            /**
             * @phpstan-ignore-next-line
             * @psalm-suppress TypeDoesNotContainType
             */
            if ($result === self::XML_PARSE_FAILED) {
                return $request;
            }

            return $request->withParsedBody($result)->withBody(new StringStream($body));
        };
        $this->addType('application/xml', $xmlParser);
        $this->addType('text/xml', $xmlParser);
    }

    /**
     * @phpstan-ignore-next-line
     * @psalm-suppress MissingParamType
     */
    public function __invoke(ServerRequestInterface $request, $next) // phpcs:disabled
    {
        $type   = strtolower($request->getHeaderLine('Content-Type'));
        [$type] = explode(';', $type);

        if (! array_key_exists($type, $this->types)) {
            return $next($request);
        }

        try {
            $parser  = $this->types[$type];
            $request = $parser($request);
            assert($request instanceof ServerRequestInterface);
        } catch (Throwable $t) {/** @phpstan-ignore-line */
            return reject($t);
        }

        return $next($request);
    }

    public function addType(string $type, callable $callback): void
    {
        $this->types[$type] = $callback;
    }
}
