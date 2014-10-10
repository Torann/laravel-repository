<?php namespace Torann\LaravelRepository;

use Torann\LaravelRepository\Extenders\NameValidator;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register
     *
     * @return void
     */
    public function register() {}

    /**
     * Boot
     *
     * @return void
     */
    public function boot()
    {
        // Register the package namespace
        $this->package('torann/laravel-repository');

        // Get config
        $config = $this->app->config->get('laravel-repository::config', array());

        // Are reserved names enabled
        if ( !empty($config['reserved_names'])) {
            $this->setNameValidator($config['reserved_names']);
        }

        // Is the honeypot enabled?
        if ($config['enable_honeypot'] == true) {
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

        // Add a custom honeypot macro to Laravel's forms
        $this->app->form->macro('honeypot', function($honey_name)
        {
            // Create element ID
            $honey_id = $this->slugify($honey_name);

            return "<div id=\"{$honey_id}_wrap\" style=\"display:none;\">\n<input id=\"{$honey_id}\" name=\"{$honey_name}\" type=\"text\" value=\"\">\n</div>";
        });
    }

    /**
     * Create an ID for the honeypot HTML element
     *
     * @param  string $text
     * @return string
     */
    public function slugify($text)
    {
        // replace non letter or digits by _
        $text = preg_replace('~[^\\pL\d]+~u', '_', $text);

        // trim
        $text = trim($text, '_');

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // return lowercase
        return $text;
    }
}
