<?php

namespace BS\Tests\Controller;

use BS\Middleware\ControllerMiddleware;
use BS\Tests\AbstractTestCase;
use BS\Tests\Db\TableGateway\AbstractTableGatewayTest;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Expressive\Delegate\NotFoundDelegate;
use Zend\ServiceManager\ServiceManager;

/**
 * Class AbstractControllerTestCase
 *
 * @package BS\Tests\Controller
 * @property ServiceManager $serviceLocator
 */
abstract class AbstractControllerTestCase extends AbstractTestCase
{
    /**
     * @var string TestCase class for TableGateway used in this controller
     */
    protected $tableGatewayTestClass;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var $moduleContext
     */
    protected $moduleContext;

    public function setUp(ContainerInterface $serviceLocator = null)
    {
        parent::setUp($serviceLocator);

        //init the moduleContext
        $className = get_called_class();
        $module    = substr($className, 0, strpos($className, '\\'));

        if (!$this->serviceLocator->has('AuthService')) {
            $mockedAuthServiceClassName = $module . '\Tests\MockedAuthService';
            if (class_exists($mockedAuthServiceClassName)) {
                $this->serviceLocator->setService('AuthService', new $mockedAuthServiceClassName());
            }
        }

        $moduleContext = strtolower($module);
        if (in_array($moduleContext, ['admin', 'client', 'manage', 'warehouse'])) {
            $this->moduleContext = $moduleContext;
        }
    }

    public function dispatch($url, $method = 'GET', array $params = [])
    {
        $request = new ServerRequest();
        $request = $request->withMethod($method);
        /** @var ServerRequest $request */
        if (strtoupper($method) == 'GET') {
            $request = $request->withQueryParams($params);
        } else {
            $request = $request->withParsedBody($params);
        }

        $arrUrl = array_values(array_filter(explode('/', $url)));

        $request = $request->withAttribute('module', $arrUrl[0] ?? null);
        $request = $request->withAttribute('controller', $arrUrl[1] ?? null);

        if (!empty($arrUrl[2])) {
            if (preg_match('/\d+/', $arrUrl[2])) {
                $request = $request->withAttribute('id', $arrUrl[2]);
            } else {
                $request = $request->withAttribute('action', $arrUrl[2]);
            }
        }

        $controllerMiddleware = $this->serviceLocator->get(ControllerMiddleware::class);
        $this->response = $controllerMiddleware->process($request, $this->getResponseDelegate());
    }

    public function assertResponseStatusCode($code)
    {
        self::assertEquals($code, $this->response->getStatusCode());
    }

    public function assertControllerException($exception)
    {
        self::assertEquals($exception, $this->getResponse()->getHeader('exception')[0]);
    }

    public function assertExceptionParams($params)
    {
        self::assertEquals($params, $this->getResponse()->getHeader('exception_params'));
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @param null $class
     *
     * @return AbstractTableGatewayTest
     */
    public function getTableGatewayTest($class = null)
    {
        /**
         * @var AbstractTableGatewayTest $tableGatewayTest
         */
        if ($class) {
            $tableGatewayTest = $this->serviceLocator->get($class);
        } else {
            $tableGatewayTest = new $this->tableGatewayTestClass;
            $tableGatewayTest->setUp($this->getServiceLocator());
        }

        return $tableGatewayTest;
    }

    protected function checkJsonResponse(ResponseInterface $response)
    {
        $json = json_decode($response->getBody(), true);
        $this->assertNotFalse($json);
        $this->assertArrayHasKey('total', $json);
        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('success', $json);

        $this->assertTrue(is_numeric($json['total']));
        $this->assertTrue($json['success']);
        $this->assertTrue(is_array($json['data']));

        return $json;
    }
}