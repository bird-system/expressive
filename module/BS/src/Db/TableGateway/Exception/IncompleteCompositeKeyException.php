<?php
/**
 * User: Allan Sun (allan.sun@bricre.com)
 * Date: 27/12/2015
 * Time: 15:19
 */

namespace BS\Db\TableGateway\Exception;

use BS\Exception\AbstractException;

class IncompleteCompositeKeyException extends AbstractException
{
    public function getTranslation()
    {
        return $this->translate('Values given do not fulfill all composite keys');
    }
}
