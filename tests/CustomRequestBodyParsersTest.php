<?php

declare(strict_types=1);

namespace WyriHaximus\React\Tests\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Message\ServerRequest;
use RuntimeException;
use Throwable;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\Http\Middleware\CustomRequestBodyParsers;

use function assert;
use function Clue\React\Block\await;
use function RingCentral\Psr7\stream_for;
use function str_rot13;

/**
 * @internal
 */
final class CustomRequestBodyParsersTest extends AsyncTestCase
{
    public function testParseJson(): void
    {
        $jsonString    = '{"foo":"bar"}';
        $request       = (new ServerRequest('POST', 'https://example.com/'))->withHeader('Content-Type', 'application/json')->withBody(stream_for($jsonString));
        $parser        = new CustomRequestBodyParsers();
        $parsedRequest = $parser($request, static function (ServerRequestInterface $request): ServerRequestInterface {
            return $request;
        });
        assert($parsedRequest instanceof ServerRequestInterface);

        self::assertSame(['foo' => 'bar'], $parsedRequest->getParsedBody());
    }

    /**
     * @return iterable<string, array<string>>
     */
    public function provideXmlContentTypes(): iterable
    {
        yield 'application/xml' => ['application/xml'];
        yield 'text/XML' => ['text/xml'];
        yield 'application/xml; charset=UTF128' => ['application/xml; charset=UTF128'];
    }

    /**
     * @dataProvider provideXmlContentTypes
     */
    public function testParseXml(string $contentType): void
    {
        $xmlString     = '<?xml version="1.0" encoding="UTF-8"?><bar><foo>De Modelen</foo></bar>';
        $request       = (new ServerRequest('POST', 'https://example.com/'))->withHeader('Content-Type', $contentType)->withBody(stream_for($xmlString));
        $parser        = new CustomRequestBodyParsers();
        $parsedRequest = $parser($request, static function (ServerRequestInterface $request): ServerRequestInterface {
            return $request;
        });
        assert($parsedRequest instanceof ServerRequestInterface);

        self::assertSame(['foo' => 'De Modelen'], (array) $parsedRequest->getParsedBody());
    }

    public function testParseCustom(): void
    {
        $tacocatString = 'tacocat';
        $request       = (new ServerRequest('POST', 'https://example.com/'))->withHeader('Content-Type', 'animal/tacocat')->withBody(stream_for($tacocatString));
        $parser        = new CustomRequestBodyParsers();
        $parser->addType('animal/tacocat', static function (ServerRequestInterface $request): ServerRequestInterface {
            return $request->withParsedBody(['data' => str_rot13((string) $request->getBody())]);
        });
        $parsedRequest = $parser($request, static function (ServerRequestInterface $request): ServerRequestInterface {
            return $request;
        });
        assert($parsedRequest instanceof ServerRequestInterface);

        self::assertSame(['data' => 'gnpbpng'], $parsedRequest->getParsedBody());
    }

    public function testParseFailure(): void
    {
        self::expectException(Throwable::class);
        self::expectExceptionMessage('test failure');

        $tacocatString = 'tacocat';
        $request       = (new ServerRequest('POST', 'https://example.com/'))->withHeader('Content-Type', 'animal/tacocat')->withBody(stream_for($tacocatString));
        $parser        = new CustomRequestBodyParsers();
        $parser->addType('animal/tacocat', static function (ServerRequestInterface $request): void {
            throw new RuntimeException('test failure');
        });

        await($parser($request, static fn (ServerRequestInterface $request): ServerRequestInterface => $request), Factory::create());
    }
}
