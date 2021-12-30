<?php

namespace seed;

use datagutten\musicbrainz\seed;
use PHPUnit\Framework\TestCase;

class ElementTest extends TestCase
{
    /**
     * @dataProvider dataSetNoData
     * @param string $class
     */
    public function testNoData(string $class)
    {
        $class = new $class([]);
        $this->assertIsArray($class->save('test'));
    }

    /**
     * @dataProvider dataSetLink
     * @param string $class
     */
    public function testURL(string $class)
    {
        $instance = new $class(['id' => 'test']);
        $this->assertTrue(isset($instance->id), sprintf('%s does not have id', $class));
        $this->assertEquals(sprintf('https://musicbrainz.org/%s/test', $instance::$entity), $instance->link());
    }

    public function dataSetLink(): array
    {
        return ['artist' => [seed\Artist::class], 'label' => [seed\Label::class],
            'release' => [seed\Release::class], 'track' => [seed\Track::class], 'url' => [seed\URL::class]];
    }

    public function dataSetNoData(): array
    {
        return ['artist' => [seed\Artist::class], 'label' => [seed\Label::class],
            'release' => [seed\Release::class], 'track' => [seed\Track::class], 'url' => [seed\URL::class], 'medium' => [seed\Medium::class]];
    }
}
