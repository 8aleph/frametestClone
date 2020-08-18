<?php

namespace Example\Tests\Traits;

use Faker\Factory;

/**
 * Trait for using the Faker package. This allows for
 * generating test seed data.
 *
 * Reference: https://github.com/fzaninotto/Faker#basic-usage
 */
trait FakerTrait
{
    /**
     * Faker instance.
     *
     * @var Faker\Generator|null
     */
    protected $faker = null;

    /**
     * Setup up the Faker instance.
     *
     * @return void
     */
    protected function setUpFaker(): void
    {
        $this->faker = Factory::create();
    }

    /**
     * Generate a string of a certain type for a certain length.
     *
     * Type list:
     * ? -> random letters
     * # -> random numbers
     * * -> random letters/numbers
     * 
     * @param int    $length       length of the string
     * @param string $typeOfString type of patterned string to generate
     * 
     * @return string faked string
     */
    protected function fakeString(int $length, string $typeOfString = '*'): string
    {
        return $this->faker->bothify(str_repeat($typeOfString, $length));
    }
}
