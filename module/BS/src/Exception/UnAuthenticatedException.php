<?php

namespace BS\Exception;

class UnAuthenticatedException extends AbstractException
{
    function getTranslation()
    {
        $this->translate('Please login first.');
    }
}