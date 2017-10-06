<?php

namespace BS\I18n\Translator;

use Zend\I18n\Translator\Translator;

interface TranslatorAwareInterface
{
    /**
     * @param Translator $translator
     *
     * @return $this
     */
    public function setTranslator(Translator $translator);

    /**
     * @return Translator
     */
    public function getTranslator();

    /**
     * @param string $message
     *
     * @return string Translated message
     */
    public function t($message);
}