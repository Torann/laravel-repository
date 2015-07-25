<?php

namespace Torann\LaravelRepository;

use Torann\LaravelRepository\Extenders\NameValidator;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register
     *
     * @return void
     */
    public function register() {
        //
    }

    /**
     * Boot
     *
     * @return void
     */
    public function boot()
    {
        // Load helpers
        include __DIR__.'/../helpers.php';

        $this->publishes([
            __DIR__.'/../../config/repositories.php' => config_path('repositories.php'),
        ]);

        // Get config
        $config = $this->app->config->get('repositories', array());

        // Are reserved names enabled
        if (array_get($config, 'reserved_names')) {
            $this->setNameValidator($config['reserved_names']);
        }

        // Is the honeypot enabled?
        if (array_get($config, 'enable_honeypot', false) === true) {
            $this->setHoneypotValidator();
        }
    }

    /**
     * Register the reserved name validator
     *
     * @param  array  $names
     * @return void
     */
    public function setNameValidator($names)
    {
        // Reserved name validation
        $this->app->validator->resolver(function($translator, $data, $rules, $messages) use ($names)
        {
            $nameValidator = new NameValidator($translator, $data, $rules, $messages);

            // Set reserved names
            $nameValidator->setNames($names);

            return $nameValidator;
        });
    }

    /**
     * Register the honeypot validator
     *
     * @return void
     */
    public function setHoneypotValidator()
    {
        // Extend Laravel's validator (rule, function, messages)
        $this->app->validator->extend(
            'honeypot',
            'Torann\LaravelRepository\Extenders\HoneypotValidator@validate',
            $this->app->translator->get('validation.honeypot')
        );
    }
}
