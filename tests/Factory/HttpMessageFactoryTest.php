<?php

declare(strict_types=1);

namespace Majkl578\SymfonyGuzzlePsr7Bridge\Factory;

use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HttpMessageFactoryTest extends TestCase
{
    private const DEFAULT_PROTOCOL_VERSION = '1.1';
    private const DEFAULT_BODY = '';
    private const DEFAULT_RESPONSE_STATUS_CODE = 200;
    private const DEFAULT_RESPONSE_HEADERS = [];

    /** @var HttpMessageFactory */
    private $factory;

    protected function setUp() : void
    {
        $this->factory = new HttpMessageFactory();
    }

    public function testEmptyRequest() : void
    {
        $symfonyRequest = $this->createRequestMock(
            'GET',
            '/',
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            ''
        );

        $psrRequest = $this->factory->createRequest($symfonyRequest);

        $this->assertSame('GET', $psrRequest->getMethod());
        $this->assertSame('/', $psrRequest->getUri()->__toString());
        $this->assertSame('1.1', $psrRequest->getProtocolVersion());
        $this->assertSame([], $psrRequest->getAttributes());
        $this->assertSame([], $psrRequest->getQueryParams());
        $this->assertSame([], $psrRequest->getParsedBody());
        $this->assertSame([], $psrRequest->getUploadedFiles());
        $this->assertSame([], $psrRequest->getCookieParams());
        $this->assertSame([], $psrRequest->getServerParams());
        $this->assertSame([], $psrRequest->getHeaders());
        $this->assertSame('', $psrRequest->getBody()->getContents());
    }

    public function testRequestDetectsProtocolVersion() : void
    {
        $symfonyRequest = $this->createRequestMock(
            'GET',
            '/',
            [],
            [],
            [],
            [],
            [],
            ['SERVER_PROTOCOL' => 'HTTP/2.0'],
            [],
            ''
        );

        $psrRequest = $this->factory->createRequest($symfonyRequest);

        $this->assertSame('2.0', $psrRequest->getProtocolVersion());
    }

    public function testRequestDetectsMalformedProtocolVersion() : void
    {
        $symfonyRequest = $this->createRequestMock(
            'GET',
            '/',
            [],
            [],
            [],
            [],
            [],
            ['SERVER_PROTOCOL' => 'HTTP/X.Y'],
            [],
            ''
        );

        $psrRequest = $this->factory->createRequest($symfonyRequest);

        $this->assertSame('1.1', $psrRequest->getProtocolVersion());
    }

    public function testRequestWithDifferentMethod() : void
    {
        $symfonyRequest = $this->createRequestMock(
            'OPTIONS',
            '/',
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            ''
        );

        $psrRequest = $this->factory->createRequest($symfonyRequest);

        $this->assertSame('OPTIONS', $psrRequest->getMethod());
    }

    public function testRequestWithUri() : void
    {
        $symfonyRequest = $this->createRequestMock(
            'GET',
            '/foo/bar',
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            ''
        );

        $psrRequest = $this->factory->createRequest($symfonyRequest);

        $this->assertSame('/foo/bar', $psrRequest->getUri()->__toString());
    }

    public function testRequestWithContent() : void
    {
        $symfonyRequest = $this->createRequestMock(
            'GET',
            '/',
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            'test'
        );

        $psrRequest = $this->factory->createRequest($symfonyRequest);

        $this->assertSame('test', $psrRequest->getBody()->getContents());
    }

    public function testRequestWithUploadedFiles() : void
    {
        $symfonyRequest = $this->createRequestMock(
            'GET',
            '/',
            [],
            [],
            [],
            [$this->createUploadedFileMock(__FILE__, 'foo.test', 10, 'type', UPLOAD_ERR_OK)],
            [],
            [],
            [],
            'test'
        );

        $psrRequest = $this->factory->createRequest($symfonyRequest);

        $this->assertCount(1, $psrRequest->getUploadedFiles());
        $this->assertSame(10, $psrRequest->getUploadedFiles()[0]->getSize());
        $this->assertSame('foo.test', $psrRequest->getUploadedFiles()[0]->getClientFilename());
        $this->assertSame('type', $psrRequest->getUploadedFiles()[0]->getClientMediaType());
        $this->assertSame(UPLOAD_ERR_OK, $psrRequest->getUploadedFiles()[0]->getError());
        $this->assertSame(file_get_contents(__FILE__), $psrRequest->getUploadedFiles()[0]->getStream()->getContents());
    }

    public function testRequestArrayVariables() : void
    {
        $symfonyRequest = $this->createRequestMock(
            'GET',
            '/',
            ['attribute' => 'foo'],
            ['request' => 'foo'],
            ['query' => 'foo'],
            [],
            ['cookie' => 'foo'],
            ['SERVER_PROTOCOL' => 'HTTP/1.1'],
            ['Header' => ['foo']],
            ''
        );

        $psrRequest = $this->factory->createRequest($symfonyRequest);

        $this->assertSame(['attribute' => 'foo'], $psrRequest->getAttributes());
        $this->assertSame(['request' => 'foo'], $psrRequest->getParsedBody());
        $this->assertSame(['query' => 'foo'], $psrRequest->getQueryParams());
        $this->assertSame(['cookie' => 'foo'], $psrRequest->getCookieParams());
        $this->assertSame(['SERVER_PROTOCOL' => 'HTTP/1.1'], $psrRequest->getServerParams());
        $this->assertSame(['Header' => ['foo']], $psrRequest->getHeaders());
    }

    public function testCleanResponse() : void
    {
        $symfonyResponse = $this->createResponse();

        $psrResponse = $this->factory->createResponse($symfonyResponse);

        $this->assertSame(self::DEFAULT_RESPONSE_STATUS_CODE, $psrResponse->getStatusCode());
        $this->assertSame(self::DEFAULT_BODY, $psrResponse->getBody()->getContents());
        $this->assertSame(self::DEFAULT_RESPONSE_HEADERS, $psrResponse->getHeaders());
        $this->assertSame(self::DEFAULT_PROTOCOL_VERSION, $psrResponse->getProtocolVersion());
    }

    public function testResponseWithContent() : void
    {
        $symfonyResponse = $this->createResponse();
        $symfonyResponse->setContent('foo');

        $psrResponse = $this->factory->createResponse($symfonyResponse);

        $this->assertSame('foo', $psrResponse->getBody()->getContents());
    }

    public function testBinaryFileResponse() : void
    {
        $symfonyResponse = $this->createBinaryFileResponse(__FILE__);

        $psrResponse = $this->factory->createResponse($symfonyResponse);

        $this->assertSame(self::DEFAULT_RESPONSE_STATUS_CODE, $psrResponse->getStatusCode());
        $this->assertSame(file_get_contents(__FILE__), $psrResponse->getBody()->getContents());
        $this->assertSame(self::DEFAULT_RESPONSE_HEADERS, $psrResponse->getHeaders());
        $this->assertSame(self::DEFAULT_PROTOCOL_VERSION, $psrResponse->getProtocolVersion());
    }

    public function testStreamedResponse() : void
    {
        $symfonyResponse = $this->createStreamedResponse(function () : void {
            echo 'test';
        });

        $psrResponse = $this->factory->createResponse($symfonyResponse);

        $this->assertSame(self::DEFAULT_RESPONSE_STATUS_CODE, $psrResponse->getStatusCode());
        $this->assertSame('test', $psrResponse->getBody()->getContents());
        $this->assertSame(self::DEFAULT_RESPONSE_HEADERS, $psrResponse->getHeaders());
        $this->assertSame(self::DEFAULT_PROTOCOL_VERSION, $psrResponse->getProtocolVersion());
    }

    public function testResponseWithDifferentCode() : void
    {
        $symfonyResponse = $this->createResponse();
        $symfonyResponse->setStatusCode(404);

        $psrResponse = $this->factory->createResponse($symfonyResponse);

        $this->assertSame(404, $psrResponse->getStatusCode());
    }

    public function testResponseHeaders() : void
    {
        $symfonyResponse = $this->createResponse();
        $symfonyResponse->headers->set('Foo', ['1']);
        $symfonyResponse->headers->set('B-A-R', ['22', '333']);

        $psrResponse = $this->factory->createResponse($symfonyResponse);

        $this->assertSame(
            [
                'Foo' => ['1'],
                'B-A-R' => ['22', '333'],
            ],
            $psrResponse->getHeaders()
        );
    }

    public function testResponseCookies() : void
    {
        $symfonyResponse = $this->createResponse();
        $symfonyResponse->headers->setCookie(new Cookie('foo', 'bar', 0, '/', null, false, false, false, null));
        $symfonyResponse->headers->setCookie(new Cookie('bar', 'baz', 10, '/foo', '.example.com', true, true, false, null));

        $psrResponse = $this->factory->createResponse($symfonyResponse);

        $this->assertSame(
            [
                'Set-Cookie' => [
                    'foo=bar; path=/',
                    'bar=baz; expires=Thu, 01-Jan-1970 00:00:10 GMT; path=/foo; domain=.example.com; secure; httponly'
                ],
            ],
            $psrResponse->getHeaders()
        );
    }

    /**
     * @return MockInterface|Request
     */
    private function createRequestMock(
        string $method,
        string $uri,
        array $attributes,
        array $request,
        array $query,
        array $files,
        array $cookies,
        array $server,
        array $headers,
        string $content
    ) : MockInterface {
        /** @var MockInterface|Request $symfonyRequest */
        $symfonyRequest = \Mockery::mock(Request::class);

        $symfonyRequest->attributes = \Mockery::mock(ParameterBag::class);
        $symfonyRequest->request = \Mockery::mock(ParameterBag::class);
        $symfonyRequest->query = \Mockery::mock(ParameterBag::class);
        $symfonyRequest->files = \Mockery::mock(FileBag::class);
        $symfonyRequest->cookies = \Mockery::mock(ParameterBag::class);
        $symfonyRequest->server = \Mockery::mock(ServerBag::class);
        $symfonyRequest->headers = \Mockery::mock(HeaderBag::class);

        $symfonyRequest->shouldReceive('getMethod')
            ->andReturn($method);
        $symfonyRequest->shouldReceive('getUri')
            ->andReturn($uri);
        $symfonyRequest->shouldReceive('getContent')
            ->with(true)
            ->andReturnUsing(function () use ($content) {
                $handle = fopen('php://temp', 'r+');
                fwrite($handle, $content);
                fseek($handle, 0);
                return $handle;
            });

        $variableMap = [
            'attributes' => $attributes,
            'request' => $request,
            'query' => $query,
            'files' => $files,
            'cookies' => $cookies,
            'server' => $server,
            'headers' => $headers,
        ];

        foreach ($variableMap as $variable => $items) {
            $symfonyRequest->$variable->shouldReceive('all')
                ->andReturn($items);
            $symfonyRequest->$variable->shouldReceive('getIterator')
                ->andReturnUsing(function () use ($items) : \Iterator {
                    return new \ArrayIterator($items);
                });
            $symfonyRequest->$variable->shouldReceive('has')
                ->andReturnUsing(function (string $name) use ($items) : bool {
                    return array_key_exists($name, $items);
                });
            $symfonyRequest->$variable->shouldReceive('get')
                ->andReturnUsing(function (string $name) use ($items) {
                    return $items[$name];
                });
        }

        return $symfonyRequest;
    }

    private function createResponse() : Response
    {
        $response = new Response(self::DEFAULT_BODY, self::DEFAULT_RESPONSE_STATUS_CODE, []);
        $this->clearResponse($response);
        return $response;
    }

    private function createBinaryFileResponse(string $path) : BinaryFileResponse
    {
        $response = new BinaryFileResponse(
            $path,
            self::DEFAULT_RESPONSE_STATUS_CODE,
            self::DEFAULT_RESPONSE_HEADERS,
            false,
            null,
            false,
            false
        );
        $this->clearResponse($response);
        return $response;
    }

    private function createStreamedResponse(callable $callback) : StreamedResponse
    {
        $response = new StreamedResponse($callback, self::DEFAULT_RESPONSE_STATUS_CODE, self::DEFAULT_RESPONSE_HEADERS);
        $this->clearResponse($response);
        return $response;
    }

    private function clearResponse(Response $response) : void
    {
        $response->setProtocolVersion(self::DEFAULT_PROTOCOL_VERSION);
        $response->headers->replace(self::DEFAULT_RESPONSE_HEADERS);
        $response->headers->remove('Cache-Control');
    }

    /**
     * @return MockInterface|UploadedFile
     */
    private function createUploadedFileMock(string $path, string $name, int $size, string $mime, int $error) : MockInterface
    {
        $file = \Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getRealPath')->andReturn($path);
        $file->shouldReceive('getClientSize')->andReturn($size);
        $file->shouldReceive('getError')->andReturn($error);
        $file->shouldReceive('getClientOriginalName')->andReturn($name);
        $file->shouldReceive('getClientMimeType')->andReturn($mime);

        return $file;
    }
}
