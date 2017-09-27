<?php declare(strict_types=1);

namespace WyriHaximus\React\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use function RingCentral\Psr7\stream_for;

final class CustomRequestBodyParsers
{
    /**
     * @var array
     */
    private $types = [];

    public function __construct()
    {
        /**
         * Via: https://github.com/reactphp/http/pull/220#discussion_r140863176.
         */
        $this->addType('application/json', function (ServerRequestInterface $request) {
            $body = (string)$request->getBody();
            $result = json_decode($body, true);
            if (!is_array($result)) {
                return $request;
            }

            return $request->withParsedBody($result)->withBody(stream_for($body));
        });

        /**
         * Via: https://github.com/reactphp/http/pull/220#discussion_r140863176.
         */
        $xmlParser = function (ServerRequestInterface $request) {
            $body = (string)$request->getBody();
            $backup = libxml_disable_entity_loader(true);
            $backup_errors = libxml_use_internal_errors(true);
            $result = simplexml_load_string($body);
            libxml_disable_entity_loader($backup);
            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);
            if ($result === false) {
                return $request;
            }

            return $request->withParsedBody($result)->withBody(stream_for($body));
        };
        $this->addType('application/xml', $xmlParser);
        $this->addType('text/xml', $xmlParser);
    }

    public function __invoke(ServerRequestInterface $request, $next)
    {
        $type = strtolower($request->getHeaderLine('Content-Type'));
        list($type) = explode(';', $type);

        if (!isset($this->types[$type])) {
            return $next($request);
        }

        try {
            $parser = $this->types[$type];
            /** @var ServerRequestInterface $request */
            $request = $parser($request);
        } catch (\Exception $e) {
            return $next($request);
        } catch (\Throwable $t) {
            return $next($request);
        }

        return $next($request);
    }

    public function addType($type, $callback)
    {
        $this->types[$type] = $callback;
    }
}
