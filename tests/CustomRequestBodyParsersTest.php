<?php declare(strict_types=1);

namespace WyriHaximus\React\Tests\Http\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\ServerRequest;
use WyriHaximus\React\Http\Middleware\CustomRequestBodyParsers;
use function RingCentral\Psr7\stream_for;

final class CustomRequestBodyParsersTest extends TestCase
{
    public function testParseJson()
    {
        $jsonString = '{"foo":"bar"}';
        $request = (new ServerRequest('POST', 'https://example.com/'))->withHeader('Content-Type', 'application/json')->withBody(stream_for($jsonString));
        $parser = new CustomRequestBodyParsers();
        /** @var ServerRequestInterface $parsedRequest */
        $parsedRequest = $parser($request, function (ServerRequestInterface $request) {
            return $request;
        });

        self::assertSame([
            'foo' => 'bar',
        ], $parsedRequest->getParsedBody());
    }

    public function provideXmlContentTypes()
    {
        yield ['application/xml'];
        yield ['text/xml'];
        yield ['application/xml; charset=UTF128'];
    }

    /**
     * @dataProvider provideXmlContentTypes
     */
    public function testParseXml(string $contentType)
    {
        $xmlString = '<?xml version="1.0" encoding="UTF-8"?><bar><foo>De Modelen</foo></bar>';
        $request = (new ServerRequest('POST', 'https://example.com/'))->withHeader('Content-Type', $contentType)->withBody(stream_for($xmlString));
        $parser = new CustomRequestBodyParsers();
        /** @var ServerRequestInterface $parsedRequest */
        $parsedRequest = $parser($request, function (ServerRequestInterface $request) {
            return $request;
        });

        self::assertSame([
            'foo' => 'De Modelen',
        ], (array)$parsedRequest->getParsedBody());
    }

    public function testParseCustom()
    {
        $tacocatString = 'tacocat';
        $request = (new ServerRequest('POST', 'https://example.com/'))->withHeader('Content-Type', 'animal/tacocat')->withBody(stream_for($tacocatString));
        $parser = new CustomRequestBodyParsers();
        $parser->addType('animal/tacocat', function (ServerRequestInterface $request) {
            return $request->withParsedBody(str_rot13((string)$request->getBody()));
        });
        /** @var ServerRequestInterface $parsedRequest */
        $parsedRequest = $parser($request, function (ServerRequestInterface $request) {
            return $request;
        });

        self::assertSame('gnpbpng', $parsedRequest->getParsedBody());
    }
}