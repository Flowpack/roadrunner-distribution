<?php
namespace Flowpack\Roadrunner;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\MediaTypeConverterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * NOTE: Copied from Flow to fix issues in parseRequestBody, because Nyholm\Psr7 is strict about what withParsedBody accepts (as the spec says)
 *
 * Parses the request body and adds the result to the ServerRequest instance.
 */
class RequestBodyParsingMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        if (!empty($request->getParsedBody())) {
            return $next->handle($request);
        }
        $parsedBody = $this->parseRequestBody($request);
        if ($parsedBody !== null) {
            file_put_contents('php://stderr', "Parsed request body for " . $request->getUri() . "\n");
            $request = $request->withParsedBody($parsedBody);
        } else {
            file_put_contents('php://stderr', "Did not parse request body for " . $request->getUri() . "\n");
        }
        return $next->handle($request);
    }

    /**
     * Parses the request body according to the media type.
     *
     * @param ServerRequestInterface $httpRequest
     * @return null|array|object
     */
    protected function parseRequestBody(ServerRequestInterface $httpRequest)
    {
        // Do not use getContents() since it returned an empty string for a request having a body (TODO why?)
        $requestBody = (string)$httpRequest->getBody();
        if ($requestBody === '') {
            return null;
        }

        /** @var MediaTypeConverterInterface $mediaTypeConverter */
        $mediaTypeConverter = $this->objectManager->get(MediaTypeConverterInterface::class);
        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverter($mediaTypeConverter);
        $requestedContentType = $httpRequest->getHeaderLine('Content-Type');
        $propertyMappingConfiguration->setTypeConverterOption(MediaTypeConverterInterface::class, MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE, $requestedContentType);
        // FIXME: The MediaTypeConverter returns an empty array for "error cases", which might be unintended
        return $this->propertyMapper->convert($requestBody, 'array', $propertyMappingConfiguration);
    }
}
