<?php

namespace BS\Middleware;

use BS\Controller\Exception\AppException;
use BS\Exception\AbstractWithParamException;
use Interop\Container\ContainerInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use BS\Controller\AbstractController;
use BS\Factory\CaseTransformerFactory;
use Zend\Diactoros\Response\EmptyResponse;

class ControllerMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $serviceLocator;

    const SESSION_LOCALE = 'LOCALE';

    const ATTR_MODULE_NAME = 'module';
    const ATTR_CONTROLLER_NAME = 'controller';
    const ATTR_ACTION_NAME = 'action';

    public function __construct(ContainerInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $moduleName = $request->getAttribute(self::ATTR_MODULE_NAME);
        $controllerName = $request->getAttribute(self::ATTR_CONTROLLER_NAME);

        $TransFormer = CaseTransformerFactory::getFormer(
            CaseTransformerFactory::SPINAL_CASE,
            CaseTransformerFactory::CAMEL_CASE
        );

        $Controller = $this->getControllerInstance($moduleName, $TransFormer->transform($controllerName));

        if (!is_null($Controller)) {
            $actionName = $request->getAttribute(self::ATTR_ACTION_NAME);
            if (is_null($actionName)) {
                switch (strtolower($request->getMethod())) {
                    // DELETE
                    case 'delete':
                        $actionName = 'delete';
                        break;
                    // GET
                    case 'get':
                        $actionName = 'index';
                        break;
                    // POST or PUT
                    case 'post':
                    case 'put':
                        $actionName = 'post';
                        break;
                    case 'options':
                        return (new EmptyResponse())->withStatus(200);
                    // All others...
                    default:
                        return (new EmptyResponse())->withStatus(405);
                }
            }

            $actionName = $TransFormer->transform($actionName);
            $methodName = $actionName . 'Action';

            if (method_exists($Controller, $methodName)) {
                $response = null;
                try {
                    $Controller->setRequest($request);
                    $response = $Controller->$methodName();
                } catch (\Throwable $exception) {
                    $Connection = $this->getDbConnection();
                    if ($Connection->isConnected() && $Connection->inTransaction()) {
                        $Connection->rollback();
                    }

                    $translator = $this->serviceLocator->get('translator');

                    switch (true) {
                        case $exception instanceof AbstractWithParamException:
                            $message = vsprintf(
                                $translator->translate($exception->getMessage()),
                                $exception->getMessageParams()
                            );
                            break;
                        default:
                            $message = $translator->translate($exception->getMessage());
                            break;
                    }
                    $response = new AppException($message, 500, $exception);
                }

                if ($Controller->isHtml) {
                    $request = $request->withAttribute('display_json', false);
                }
                $request = $request->withAttribute('dispatch_result', $response);
            }
        }

        return $delegate->process($request);
    }

    /**
     * @return \Zend\Db\Adapter\Driver\AbstractConnection
     */
    protected function getDbConnection()
    {
        /** @var \Zend\Db\Adapter\Driver\AbstractConnection $connection */
        $connection = $this->serviceLocator->get('db')->getDriver()->getConnection();

        return $connection;
    }

    /**
     * @param $moduleName
     * @param $controllerName
     *
     * @return AbstractController
     */
    protected function getControllerInstance($moduleName, $controllerName)
    {
        $fullControllerName =
            ucfirst(strtolower($moduleName)) . '\\Controller\\' . ucfirst($controllerName) . 'Controller';

        if (class_exists($fullControllerName)) {
            return $this->serviceLocator->get($fullControllerName);
        }

        return null;
    }
}