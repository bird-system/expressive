<?php

namespace BS\Tests\Db\Model;

use BS\Db\Model\AbstractModel;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

abstract class AbstractModelTest extends TestCase
{
    /**
     * @var string Model class name to be tested
     */
    protected $modelClass;

    /**
     * @var Generator
     */
    protected $faker;

    public function setUp()
    {
        parent::setUp();
        if (!$this->faker) {
            $this->faker = Factory::create();
        }
    }

    public function testExtraFieldOverridenProperty()
    {
        /** @var AbstractModel $Model */
        $Model = new $this->modelClass;
        $Reflection = new \ReflectionObject($Model);

        if (empty($Model->getExtraFields())) {
            self::assertEmpty($Model->getExtraFields());
        }

        foreach ($Model->getExtraFields() as $field) {
            self::assertFalse(
                $Reflection->hasProperty($field),
                "ExtraField [${field}] is already defined in Base Model."
            );
        }
    }
}
