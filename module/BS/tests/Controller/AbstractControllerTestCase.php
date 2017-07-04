<?php

namespace BS\Tests\Controller;

use BS\Middleware\ControllerMiddleware;
use BS\Tests\AbstractTestCase;
use BS\Tests\MockedAuthService;
use Psr\Http\Message\ResponseInterface;
use BS\Tests\Db\TableGateway\AbstractTableGatewayTest;
use Zend\Diactoros\ServerRequest;
use Zend\Expressive\Delegate\NotFoundDelegate;
use Zend\ServiceManager\ServiceManager;

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

    function setUp()
    {
        parent::setUp();

        if (!$this->serviceLocator->has('AuthService')) {
            if ($this->serviceLocator instanceof ServiceManager) {
                $this->serviceLocator->setService('AuthService', new MockedAuthService());
            }
        }
    }

    public function dispatch($url, $method = 'GET', array $params = [])
    {
        $request = new ServerRequest();
        $request = $request->withMethod($method);
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
        $this->response = $controllerMiddleware->process($request, $this->serviceLocator->get(NotFoundDelegate::class));
    }

    public function assertResponseStatusCode($code)
    {
        self::assertEquals($this->response->getStatusCode(), $code);
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
            $tableGatewayTest->setUp();
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