<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Repository namespace
    |--------------------------------------------------------------------------
    |
    | The namespace for the repository classes.
    |
    */

    'namespace' => '\\App\\Repositories',

    /*
     |--------------------------------------------------------------------------
     | Repository pagination
     |--------------------------------------------------------------------------
     |
     */

    'pagination' => [
        'limit' => 15
    ],

    /*
     |--------------------------------------------------------------------------
     | Cache Config
     |--------------------------------------------------------------------------
     |
     */

    'cache' => [

        /*
         |--------------------------------------------------------------------------
         | Cache Status
         |--------------------------------------------------------------------------
         |
         | Enable or disable cache
         |
         */

        'enabled' => true,

        /*
         |--------------------------------------------------------------------------
         | Cache Clean Listener
         |--------------------------------------------------------------------------
         |
         | create : Clear Cache on create Entry in repository
         | update : Clear Cache on update Entry in repository
         | delete : Clear Cache on delete Entry in repository
         |
         */

        'clean' => [
            'create' => true,
            'update' => true,
            'delete' => true,
        ],

        /*
         |--------------------------------------------------------------------------
         | Cache Clear Event
         |--------------------------------------------------------------------------
         |
         | The event to fire during the cache cleaning event.
         |
         */

        'clean_event' => \Torann\LaravelRepository\Events\RepositoryEvent::class,

        /*
         |--------------------------------------------------------------------------
         | Skip Cache Params
         |--------------------------------------------------------------------------
         |
         |
         | Ex: http://myapp.local/?search=lorem&skipCache=true
         |
         */

        'skipParam' => 'skipCache'
    ],
];