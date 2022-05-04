<?php
namespace Flowpack\Roadrunner;

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Neos\Flow\Http\Middleware;

/**
 * A request handler which can handle Roadrunner worker requests
 */
class RequestHandler implements \Neos\Flow\Http\HttpRequestHandlerInterface
{

    private Bootstrap $bootstrap;

    private ServerRequestInterface $httpRequest;

    private ObjectManager $objectManager;

    private array $initialObjectConfiguration;

    /**
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;

        /** @var ObjectManager $objectManager */
        $objectManager = $this->bootstrap->getObjectManager();
        $this->objectManager = $objectManager;

        // Store the initial object configuration after bootstrap
        $this->initialObjectConfiguration = $this->objectManager->getAllObjectConfigurations();
    }

    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->httpRequest = $request;

        $middlewaresChain = $this->objectManager->get(Middleware\MiddlewaresChain::class);

        $middlewaresChain->onStep(function (ServerRequestInterface $request) {
            $this->httpRequest = $request;
        });
        $httpResponse = $middlewaresChain->handle($this->httpRequest);

        // TODO Check if we need shutdown behaviour from Bootstrap in workers!
        // $this->bootstrap->shutdown(Bootstrap::RUNLEVEL_RUNTIME);

        // For now only shutdown objects
        $this->objectManager->shutdown();

        // Reset objects to initial configuration - this is a pretty rough approach
        //
        // One idea to have this more "soft" is to opt-in to a soft-reset by singletons and either reset these or unset singleton instances.
        // What to do with transitive dependencies (i.e. a singleton can be resetted but depends on a non-resettable singleton) is to be figured out. Maybe we can statically detect this.
        $this->objectManager->setObjects($this->initialObjectConfiguration);

        return $httpResponse;
    }

    /**
     * Returns the currently handled HTTP request
     *
     * @return ServerRequestInterface
     * @api
     */
    public function getHttpRequest()
    {
        return $this->httpRequest;
    }

    public function canHandleRequest()
    {
        return true;
    }

    public function getPriority()
    {
        return -1;
    }

    public function handleRequest()
    {
        throw new \Neos\Flow\Exception("must use processRequest to handle request");
    }
}
