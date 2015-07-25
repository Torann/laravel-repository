<?php

namespace Torann\LaravelRepository;

use Illuminate\Validation\Factory;

abstract class AbstractValidator
{
    /**
     * The Validator instance
     *
     * @var \Illuminate\Validation\Factory
     */
    protected $validator;

    /**
     * Inject the Validator instance
     *
     * @param \Illuminate\Validation\Factory $validator
     */
    public function __construct(Factory $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Replace placeholders with attributes
     *
     * @return array
     */
    public function replace($rules, $data)
    {
        array_walk($rules, function(&$rule) use ($data)
        {
            preg_match_all('/\{(.*?)\}/', $rule, $matches);

            foreach($matches[0] as $key => $placeholder) {
                if(isset($data[$matches[1][$key]])) {
                    $rule = str_replace($placeholder, $data[$matches[1][$key]], $rule);
                }
            }
        });

        return $rules;
    }

    /**
     * Validates the data
     *
     * @param  string $method
     * @param  array $data
     *
     * @return boolean
     */
    public function validate($method, array $data)
    {
        $rules    = [];
        $property = lcfirst($method) . 'Rules';

        // Get general rules
        if (isset($this->rules) && is_array($this->rules)) {
            $rules = $this->replace($this->rules, $data);
        }

        // Get rules for method
        if (isset($this->$property) && is_array($this->$property)) {
            $rules = array_merge(
                $rules,
                $this->replace($this->$property, $data)
            );
        }

        $validator = $this->validator->make($data, $rules);

        if ($validator->passes()) {
            return true;
        }

        $this->errors = $validator->messages();
    }

    /**
     * Return errors
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function getErrors()
    {
        return $this->errors;
    }
}