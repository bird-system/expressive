<?php
/**
 * User: Allan Sun (allan.sun@bricre.com)
 * Date: 24/12/2015
 * Time: 02:04
 */

namespace BS\Db\Adapter\Profiler;

use BS\ServiceLocatorAwareInterface;
use BS\Traits\ServiceLocatorAwareTrait;
use Zend\Db\Adapter\Profiler\Profiler as ZendProfiler;
use Zend\Db\Adapter\StatementContainerInterface;

/**
 * @codeCoverageIgnore
 */
class AutoLogProfiler extends ZendProfiler implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait, ProfilerTraits;

    /**
     * @inheritDoc
     */
    public function profilerStart($target)
    {
        if (is_a($target, StatementContainerInterface::class)) {
            $sql = $target->getSql();
            $params = $target->getParameterContainer()->getNamedArray();
        } else {
            if (is_string($target)) {
                $sql = $target;
                $params = [];
            } else {
                return $this;
            }
        }
        $this->serviceLocator->get('logger')
            ->debug($this->interpolateQuery($sql, $params));
        $this->profiles[$this->currentIndex] = $sql;
        $this->currentIndex++;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function profilerFinish()
    {
        return $this;
    }

}