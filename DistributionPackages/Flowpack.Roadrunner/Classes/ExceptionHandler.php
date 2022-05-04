<?php

namespace Flowpack\Roadrunner;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Error\Debugger;
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
 * @Flow\Proxy(false)
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

        $message = htmlspecialchars($exception->getMessage());

        $backtraceCode = Debugger::getBacktraceCode($exception->getTrace());

        $body = "<!DOCTYPE html><html><body><h1>Got exception: $message</h1>$backtraceCode</body></html>";

        return new Response($statusCode, ["Content-Type" => "text/html"], $body);
    }
}
