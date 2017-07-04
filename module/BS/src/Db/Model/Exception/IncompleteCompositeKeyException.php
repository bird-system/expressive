<?php

namespace BS\Db\Model\Exception;

use BS\Exception\AbstractException;

class IncompleteCompositeKeyException extends AbstractException
{
    public function getTranslation()
    {
        return $this->translate('Composite Key information is not complete');
    }
}
