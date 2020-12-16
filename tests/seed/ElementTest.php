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

    public function dataSetNoData(): array
    {
        return [[seed\Artist::class], [seed\Label::class],
            [seed\Medium::class], [seed\Release::class], [seed\Track::class], [seed\URL::class]];
    }
}
