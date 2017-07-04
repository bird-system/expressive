<?php
/**
 * User: Allan Sun (allan.sun@bricre.com)
 * Date: 04/01/2016
 * Time: 17:28
 */

namespace BS\I18n\Translator;

use Zend\I18n\Translator\Translator;

trait TranslatorAwareTrait
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param $message
     *
     * @return string
     */
    public function t($message)
    {
        return $this->getTranslator()->translate($message);
    }

    /**
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @param Translator $translator
     *
     * @return $this
     */
    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;

        return $this;
    }
}