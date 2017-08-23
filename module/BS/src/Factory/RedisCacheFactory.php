<?php
namespace BS\Factory;

use BS\Traits\InjectServiceFromAwareInterfaceTrait;
use Interop\Container\ContainerInterface;
use Zend\Cache\Storage\Adapter\Redis;
use Zend\Cache\Storage\Adapter\RedisOptions;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @codeCoverageIgnore
 */
class RedisCacheFactory implements FactoryInterface
{
    use InjectServiceFromAwareInterfaceTrait;

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $instance = new $requestedName();

        $this->checkAwareInterface($instance, $container);

        return $instance;
    }

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /**
         * This fetches the configuration array we created above
         */
        $config = $serviceLocator->get('Config');
        $config = $config['redis'];

        /**
         * The configuration options are encapsulated in a class called RedisOptions
         * Here we setup the server configuration using the values from our config file
         */
        $redis_options = new RedisOptions();
        $redis_options->setServer(array(
            'host'    => $config['host'],
            'port'    => $config['port'],
            'timeout' => 30
        ));

        /**
         * The database is used for allow multi application use a single redis server with multi database
         */
        if (getenv('CACHE_SERVER_DATABASE')) {
            $redis_options->setDatabase(getenv('CACHE_SERVER_DATABASE'));
        }

        /**
         * This is not required, although it will allow to store anything that can be serialized by PHP in Redis
         */
        $redis_options->setLibOptions(array(
            \Redis::OPT_SERIALIZER => \Redis::SERIALIZER_PHP
        ));

        /**
         * We create the cache passing the RedisOptions instance we just created
         */
        $redis_cache = new Redis($redis_options);

        return $redis_cache;
    }

}
