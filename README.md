# Custom request body parsers middleware

[![Build Status](https://travis-ci.org/WyriHaximus/reactphp-http-middleware-custom-request-body-parsers.svg?branch=master)](https://travis-ci.org/WyriHaximus/reactphp-http-middleware-custom-request-body-parsers)
[![Latest Stable Version](https://poser.pugx.org/WyriHaximus/react-http-middleware-custom-request-body-parsers/v/stable.png)](https://packagist.org/packages/WyriHaximus/react-http-middleware-custom-request-body-parsers)
[![Total Downloads](https://poser.pugx.org/WyriHaximus/react-http-middleware-custom-request-body-parsers/downloads.png)](https://packagist.org/packages/WyriHaximus/react-http-middleware-custom-request-body-parsers)
[![Code Coverage](https://scrutinizer-ci.com/g/WyriHaximus/reactphp-http-middleware-custom-request-body-parsers/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/WyriHaximus/reactphp-http-middleware-custom-request-body-parsers/?branch=master)
[![License](https://poser.pugx.org/WyriHaximus/react-http-middleware-custom-request-body-parsers/license.png)](https://packagist.org/packages/WyriHaximus/react-http-middleware-custom-request-body-parsers)
[![PHP 7 ready](http://php7ready.timesplinter.ch/WyriHaximus/reactphp-http-middleware-custom-request-body-parsers/badge.svg)](https://travis-ci.org/WyriHaximus/reactphp-http-middleware-custom-request-body-parsers)

# Install

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `^`.

```
composer require wyrihaximus/react-http-middleware-custom-request-body-parsers
```

This middleware parser allows you to define custom parsers for content types. For example the following 
example adds a parser for the type `str/rot13`. So when a request comes in with the `Content-Type` header
`str/rot13` the passed parser will be executed:  

# Usage

```php
$parsersMiddleware = new CustomRequestBodyParsers();
$parsersMiddleware->addType('str/rot13', function (ServerRequestInterface $request) {
    $body = (string)$request->getBody());
    return $request->withParsedBody(str_rot13($body));
});
$server = new Server(new MiddlewareRunner([
    /** Other middleware */
    new RequestBodyBufferMiddleware(),
    $parsersMiddleware,
    /** Other middleware */
]));
```

# License

The MIT License (MIT)

Copyright (c) 2017 Cees-Jan Kiewiet

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
