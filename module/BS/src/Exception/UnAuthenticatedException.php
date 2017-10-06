<?php

namespace BS\Exception;

class UnAuthenticatedException extends AbstractException
{
    function getTranslation()
    {
        return $this->translate('Please login first.');
    }
}