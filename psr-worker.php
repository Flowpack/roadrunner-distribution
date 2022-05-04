<?php

use Neos\Flow\Core\Booting\Scripts;
use Spiral\RoadRunner;
use Nyholm\Psr7;


$rootPath = isset($_SERVER['FLOW_ROOTPATH']) ? $_SERVER['FLOW_ROOTPATH'] : false;
if ($rootPath === false && isset($_SERVER['REDIRECT_FLOW_ROOTPATH'])) {
    $rootPath = $_SERVER['REDIRECT_FLOW_ROOTPATH'];
}
if ($rootPath === false) {
    $rootPath = dirname(__FILE__) . '/';
} elseif (substr($rootPath, -1) !== '/') {
    $rootPath .= '/';
}

$composerAutoloader = require($rootPath . 'Packages/Libraries/autoload.php');

$worker = RoadRunner\Worker::create();
$psrFactory = new Psr7\Factory\Psr17Factory();

$worker = new RoadRunner\Http\PSR7Worker($worker, $psrFactory, $psrFactory, $psrFactory);


$context = \Neos\Flow\Core\Bootstrap::getEnvironmentConfigurationSetting('FLOW_CONTEXT') ?: 'Development';
$bootstrap = new \Neos\Flow\Core\Bootstrap($context, $composerAutoloader);

// From \Neos\Flow\Core\Bootstrap::run
Scripts::initializeClassLoader($bootstrap);
Scripts::initializeSignalSlot($bootstrap);
Scripts::initializePackageManagement($bootstrap);

// From \Neos\Flow\Http\RequestHandler::boot
$sequence = $bootstrap->buildRuntimeSequence();
$sequence->invoke($bootstrap);

$requestHandler = new \Flowpack\Roadrunner\RequestHandler($bootstrap);
$bootstrap->setActiveRequestHandler($requestHandler);


$exceptionHandler = new \Flowpack\Roadrunner\ExceptionHandler();

while ($req = $worker->waitRequest()) {
    try {
        // file_put_contents('php://stderr', 'Processing request from worker: ' . posix_getpid());

        $resp = $requestHandler->processRequest($req);

        $worker->respond($resp);

        $requestHandler->reset();

        // file_put_contents('php://stderr', 'Finished request from worker: ' . posix_getpid());
    } catch (\Throwable $e) {
        // file_put_contents('php://stderr', 'Catched error from worker: ' . posix_getpid());

        // NOTE: This will cause the worker to be killed!
        // $worker->getWorker()->error((string)$e);

        $worker->respond($exceptionHandler->handleException($e));

        $requestHandler->reset();
    }
}
