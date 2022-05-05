<?php
namespace Flowpack\Roadrunner;

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\Configuration\Configuration as ObjectConfiguration;
use Neos\Flow\ObjectManagement\ImmutableInstance;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\ObjectManagement\ResettableInstance;
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

    private array $initialObjectNames = [];

    /**
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;

        /** @var ObjectManager $objectManager */
        $objectManager = $this->bootstrap->getObjectManager();
        $this->objectManager = $objectManager;

        // Store the names of objects having an initial instance
        // TODO Do we need this?
        foreach ($this->objectManager->getAllObjectConfigurations() as $objectName => $objectConfiguration) {
            if (isset($objectConfiguration['i'])) {
                $this->initialObjectNames[$objectName] = true;
            }
        }
    }

    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->httpRequest = $request;

        $middlewaresChain = $this->objectManager->get(Middleware\MiddlewaresChain::class);

        $middlewaresChain->onStep(function (ServerRequestInterface $request) {
            $this->httpRequest = $request;
        });
        $httpResponse = $middlewaresChain->handle($this->httpRequest);


        // TODO Check if we need shutdown behavior from Bootstrap in workers!
        // $this->bootstrap->shutdown(Bootstrap::RUNLEVEL_RUNTIME);

        // We need to shutdown objects before responding!
        // For now only shutdown objects
        $this->objectManager->shutdown();

        return $httpResponse;
    }

    public function reset(): void
    {
        // Forget instances of new object instances
        foreach ($this->objectManager->getAllObjectConfigurations() as $objectName => $objectConfiguration) {
            if (!isset($this->initialObjectNames[$objectName]) && isset($objectConfiguration['i'])) {
                if ($objectConfiguration['i'] instanceof ImmutableInstance) {
                    // file_put_contents('php://stderr', "Leaving object instance $objectName (is immutable)\n");
                } elseif ($objectConfiguration['i'] instanceof ResettableInstance) {
                    // file_put_contents('php://stderr', "Resetting object instance $objectName (is resettable)\n");
                    $objectConfiguration['i']->resetInstance();
                } elseif ($objectName === 'Doctrine\ORM\EntityManagerInterface') {
                    // NOOP
                    // FIXME Make this configurable by adding immutable option to Objects.yaml
                } else {
                    // TODO Throw if object has scope singleton
                    // file_put_contents('php://stderr', "Forgetting object instance $objectName\n");
                    echo "[RequestHandler] forgetInstance $objectName\n";
                    $this->objectManager->forgetInstance($objectName);
                }
            }
        }
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
