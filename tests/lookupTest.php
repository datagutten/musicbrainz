<?php

namespace datagutten\musicbrainz_tests;

use datagutten\musicbrainz\Lookup;
use datagutten\musicbrainz\seed\Label;
use datagutten\musicbrainz\seed\Release;
use PHPUnit\Framework\TestCase;

class LookupTest extends TestCase
{

    public function testLabel()
    {
        $lookup = new Lookup();
        $label = $lookup->label('36a73024-201f-4833-87b1-275bc609383e');
        $this->assertInstanceOf(Label::class, $label);
        $this->assertEquals('Norske albumklassikere', $label->name);
        $this->assertInstanceOf(Release::class, $label->releases[0]);
        $this->assertSame($label->releases[0]->labels[0], $label);
    }

    public function testReleaseLabel()
    {
        $lookup = new Lookup();
        $release = $lookup->release('3d38823d-40d0-4358-9759-82e67387f34a', ['labels']);
        $this->assertInstanceOf(Label::class, $release->labels[0]);
        $this->assertEquals('Norske albumklassikere', $release->labels[0]->name);
        $this->assertEquals('NACD090', $release->labels[0]->catalog_number);
    }
}
