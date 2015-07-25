<?php

namespace Torann\LaravelRepository\Extenders;

use Illuminate\Validation\Validator;

class NameValidator extends Validator
{
    /**
     * An array of reserved names
     *
     * @var array
     */
    protected $reserved = [];

    /**
     * Set reserved names
     *
     * @param array $names
     */
    public function setNames($names = array())
    {
        $this->reserved = $names;
    }

    /**
     * Validate a name against an array of reserved names
     *
     * @param  string  $attribute
     * @param  string  $value
     * @param  array   $parameters
     * @return bool
     */
    public function validateName($attribute, $value, $parameters)
    {
        return in_array($value, $this->reserved) ? false : true;
    }
}
