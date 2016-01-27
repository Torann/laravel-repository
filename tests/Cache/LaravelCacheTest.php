<?php

namespace Torann\LaravelRepository\Test\Repositories;

use Torann\LaravelRepository\Test\TestCase;

class LaravelCacheTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSetMinutes()
    {
        $cache = $this->makeLaravelCache();

        $cache->setMinutes(44);

        $this->assertEquals(44, $cache->getMinutes());
    }

    /**
     * @test
     */
    public function shouldGetTags()
    {
        $cache = $this->makeLaravelCache();

        $this->assertEquals(['test'], $cache->getTags());
    }

    /**
     * @test
     */
    public function shouldAddTags()
    {
        $cache = $this->makeLaravelCache();

        $this->assertEquals(['test'], $cache->getTags());

        $cache->addTags(['more_tests']);

        $this->assertEquals(['test', 'more_tests'], $cache->getTags());
    }
}