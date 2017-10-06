<?php
namespace BS\Exception;

class MissRequiredParamException extends AbstractWithParamException
{
    protected $code = 100102;

    public function getTranslation()
    {
        return $this->translate('Missing required param [%s].');
    }
}