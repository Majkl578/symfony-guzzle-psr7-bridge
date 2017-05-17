<?php

declare(strict_types=1);

namespace Majkl578\SymfonyGuzzlePsr7Bridge\Factory;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory as BaseHttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class HttpFoundationFactory extends BaseHttpFoundationFactory implements HttpFoundationFactoryInterface
{
    public function createRequest(ServerRequestInterface $psrRequest) : Request
    {
        return parent::createRequest($psrRequest);
    }

    public function createResponse(ResponseInterface $psrResponse) : Response
    {
        return parent::createResponse($psrResponse);
    }
}
