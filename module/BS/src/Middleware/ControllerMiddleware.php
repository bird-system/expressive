<?php

namespace BS\Middleware;

use BS\Exception\AbstractWithParamException;
use BS\Exception\UnAuthenticatedException;
use Interop\Container\ContainerInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use BS\Controller\AbstractController;
use BS\Factory\CaseTransformerFactory;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Session\Config\SessionConfig;
use Zend\Session\Container;
use Zend\Session\SessionManager;

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
                    // All others...
                    default:
                        return (new EmptyResponse())->withStatus(405);
                }
            }

            $actionName = $TransFormer->transform($actionName);
            $methodName = $actionName . 'Action';

            if (method_exists($Controller, $methodName)) {
                try {
                    if ($Controller->requireLogin) {
                        $AuthService = $this->serviceLocator->get('AuthService');
                        if (!$AuthService->hasIdentity()) {
                            throw new UnAuthenticatedException();
                        }
                    }

                    $Controller->setRequest($request);
                    $this->initSession();
                    $this->initLocale();

                    $response = $Controller->$methodName();
                } catch (\Throwable $exception) {
                    // Rollback DB Transactions
                    $Connection = $this->getDbConnection();
                    if ($Connection->isConnected() && $Connection->inTransaction()) {
                        $Connection->rollback();
                    }

                    $translator = $this->serviceLocator->get('translator');

                    switch (true) {
                        case $exception instanceof AbstractWithParamException:
                            $message = vsprintf($translator->translate($exception->getMessage()), $exception->getMessageParams());
                            break;
                        default:
                            $message = $translator->translate($exception->getMessage());
                            break;
                    }

                    $response = new JsonResponse(['success' => false, 'code' => 500, 'message' => $message]);
                }

                if ($response instanceof ResponseInterface) {
                    return $response;
                }
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

    protected function initSession()
    {
        $config = $this->serviceLocator->get('config');

        if (getenv('SESSION_SERVER')) {
            ini_set('session.save_handler', 'redis');
            if (getenv('SESSION_SERVER_DATABASE')) {
                $dataBase = getenv('SESSION_SERVER_DATABASE');
            } else {
                $dataBase = '0';
            }
            ini_set('session.save_path', 'tcp://' . gethostbyname(getenv('SESSION_SERVER')) . ':6379?database=' . $dataBase);
        }
        $sessionConfig = new SessionConfig();
        $sessionConfig->setOptions($config['session']);
        $sessionManager = new SessionManager($sessionConfig);

        $sessionManager->start();

        /**
         * Optional: If you later want to use namespaces, you can already store the
         * Manager in the shared (static) Container (=namespace) field
         */
        Container::setDefaultManager($sessionManager);
    }

    protected function initLocale()
    {
        $Locale = new Container(self::SESSION_LOCALE);
        if ($Locale->{self::SESSION_LOCALE}) {
            $transaltor = $this->serviceLocator->get(TranslatorInterface::class);
            $transaltor->setLocale($Locale->{self::SESSION_LOCALE});
            \Locale::setDefault($Locale->{self::SESSION_LOCALE});
        } else {
            $Locale->{self::SESSION_LOCALE} = 'en_GB';
        }

        return $Locale;
    }

    /**
     * @param $moduleName
     * @param $controllerName
     *
     * @return AbstractController
     */
    protected function getControllerInstance($moduleName, $controllerName)
    {
        $fullControllerName = ucfirst(strtolower($moduleName)) . '\\Controller\\' . ucfirst($controllerName) . 'Controller';

        if (class_exists($fullControllerName)) {
            return $this->serviceLocator->get($fullControllerName);
        }

        return null;
    }
}