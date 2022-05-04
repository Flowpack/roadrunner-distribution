<?php

namespace Flowpack\Roadrunner;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Error\WithHttpStatusInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\MediaTypeConverterInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * An exception handler for Roadrunner
 */
class ExceptionHandler
{

    public function handleException(\Throwable $exception): ResponseInterface
    {
        $statusCode = 500;

        if ($exception instanceof WithHttpStatusInterface) {
            $statusCode = $exception->getStatusCode();
        }

        file_put_contents('php://stderr', get_class($exception));

        $body = "Got exception: " . $exception->getMessage();

        return new Response($statusCode, [], $body);
    }
}
