<?php

namespace BS\Factory;

use Camel\CaseTransformer;
use Camel\Format\CamelCase;
use Camel\Format\SnakeCase;
use Camel\Format\SpinalCase;
use Camel\Format\StudlyCaps;

class CaseTransformerFactory
{
    const SNAKE_CASE = 'snake';
    const CAMEL_CASE = 'camel';
    const STUDLY_CASE = 'studly';
    const SPINAL_CASE = 'spinal';

    private static $formers = [];

    private static $caseKeyMap = [
        self::SNAKE_CASE => SnakeCase::class,
        self::CAMEL_CASE => CamelCase::class,
        self::STUDLY_CASE => StudlyCaps::class,
        self::SPINAL_CASE => SpinalCase::class
    ];

    /**
     * @param String $from
     * @param String $to
     * @return mixed
     */
    public static function getFormer(String $from, String $to)
    {
        if (!array_key_exists($from, self::$caseKeyMap) || !array_key_exists($to, self::$caseKeyMap)) {
            throw new \InvalidArgumentException('Invalid camel format name.');
        }

        $key = $from . '_' . $to;

        if (array_key_exists($key, self::$formers)) {
            return self::$formers[$key];
        } else {
            self::$formers[$key] = new CaseTransformer(new self::$caseKeyMap[$from], new self::$caseKeyMap[$to]);

            return self::$formers[$key];
        }
    }
}