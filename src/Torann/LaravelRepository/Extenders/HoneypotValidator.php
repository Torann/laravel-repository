<?php namespace Torann\LaravelRepository\Extenders;

class HoneypotValidator {

    /**
     * Validate honeypot
     *
     * @param string $attribute
     * @param string $value
     * @param array $parameters
     * @param \Illuminate\Validation\Validator $validator
     * @return bool
     */
    public function validate($attribute, $value, $parameters, $validator)
    {
        return $value == '';
    }
}
