<?php

namespace BS\Tests;

use Bramus\Monolog\Formatter\ColoredLineFormatter;
use BS\I18n\Translator\TranslatorAwareInterface;
use BS\I18n\Translator\TranslatorAwareTrait;
use BS\Logger\Formatter\WildfireFormatter;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use BS\ServiceLocatorAwareInterface;
use BS\Traits\LoggerAwareTrait;
use BS\Traits\ServiceLocatorAwareTrait;
use Interop\Container\ContainerInterface;
use Monolog\Handler\StreamHandler;
use PHPUnit\Framework\TestCase;
use BS\Db\Adapter\Profiler\AutoLogProfiler;
use BS\Db\Adapter\Profiler\Profiler;

abstract class AbstractTestCase extends TestCase implements TranslatorAwareInterface, ServiceLocatorAwareInterface
{
    use LoggerAwareTrait, TranslatorAwareTrait, ServiceLocatorAwareTrait;

    /**
     * @see https://github.com/fzaninotto/Faker
     * @var Generator $faker
     */
    private static $faker;

    public function setUp(ContainerInterface $serviceLocator = null)
    {
        if (is_null($serviceLocator)) {
            $serviceLocator = require APP_ROOT . '/config/container.php';
        }
        $this->setServiceLocator($serviceLocator);
        $this->initLogger();
        $this->initDbProfiler();

        if ($this->getLogger() && $this->getName()) {
            $this->getLogger()->notice('============ [' . get_class($this) . '::' . $this->getName() . '] ===========');
        }

        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->developmentEnvironmentDbProfilerLog();
    }

    public function developmentEnvironmentDbProfilerLog()
    {
        $dbAdapter = $this->serviceLocator->get('db');
        $logger = $this->getLogger();
        $profiles = $dbAdapter->getProfiler()->getProfiles();
        if (!in_array(php_sapi_name(), ['cli', 'phpdbg'])) {
            // Our special formatter to add 'TABLE' format for logging SQL Queries in FirePHP
            $FirePHPHandler = $this->serviceLocator->get('logger')->popHandler();
            $FirePHPHandler->setFormatter(new WildfireFormatter());
            $logger->pushHandler($FirePHPHandler);

            $quries = [['Eslape', 'SQL Statement', 'Parameters']];
            foreach ($profiles as $profile) {
                $quries[] = [
                    round($profile['elapse'], 4),
                    $profile['sql'],
                    $profile['parameters'] ? $profile['parameters']->getNamedArray() : null,
                ];
            }

            $logger->info('Queries', ['table' => $quries]);
        } else {
            $logger->info('Total Number of Queries : ' . count($profiles));
        }
    }

    protected function initDbProfiler()
    {
        $dbAdapter = $this->serviceLocator->get('db');

        if (defined('PHPUNIT_COMPOSER_INSTALL') || defined('__PHPUNIT_PHAR__')) {
            $profiler = new AutoLogProfiler();
            $profiler->setServiceLocator($this->serviceLocator);
            $dbAdapter->setProfiler($profiler);
        } else {
            $dbAdapter->setProfiler(new Profiler());
        }
    }

    protected function initLogger()
    {
        $logger = $this->serviceLocator->get('logger');
        $handlers = $logger->getHandlers();
        foreach ($handlers as &$handler) {
            if ($handler instanceof StreamHandler) {
                //Make sure we reference the class directly so no error will be poped during production environment
                $Formatter = new ColoredLineFormatter(null, '%message% %context% %extra%');
                $Formatter->allowInlineLineBreaks(true);
                $Formatter->ignoreEmptyContextAndExtra(true);
                $handler->setFormatter($Formatter);
            }
        }

        $this->setLogger($logger);
    }

    /**
     * @return Generator
     */
    public function getFaker()
    {
        if (!self::$faker) {
            self::$faker = FakerFactory::create('en_GB');
        }

        return self::$faker;
    }

    protected function getResponseDelegate()
    {
        $ResponseDelegate = new ResponseDelegate();
        $ResponseDelegate->setServiceLocator($this->serviceLocator);
        return $ResponseDelegate;
    }
}