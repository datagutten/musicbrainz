<?php

namespace seed;

use datagutten\musicbrainz\seed;
use datagutten\musicbrainz\seed\Release;
use DateTime;
use PHPUnit\Framework\TestCase;

class ReleaseTest extends TestCase
{

    public function testMedium()
    {
        $release = new Release([]);
        $medium = $release->medium(['position'=>1, 'name'=>'test']);
        $this->assertInstanceOf(seed\Medium::class, $medium);
        $this->assertSame(1, $medium->position);
        $this->assertSame('test', $medium->name);
        $this->assertArrayHasKey('mediums.0.name', $release->save());
    }

    public function testUrl()
    {
        $release = new Release([]);
        $url = $release->url(['url'=>'https://tidal.com', 'link_type'=>seed\URL::stream_for_free]);
        $this->assertInstanceOf(seed\URL::class, $url);
        $this->assertSame(85, $url->link_type);
        $this->assertArrayHasKey('urls.0.url', $release->save());
    }

    public function testLabel()
    {
        $release = new Release([]);
        $label = $release->label(['name'=>'test']);
        $this->assertInstanceOf(seed\Label::class, $label);
        $this->assertSame('test', $label->name);
        $this->assertArrayHasKey('labels.0.name', $release->save());
    }

    public function testArtist()
    {
        $release = new Release([]);
        $artist = $release->artist(['name'=>'test']);
        $this->assertInstanceOf(seed\Artist::class, $artist);
        $this->assertSame('test', $artist->name);
        $this->assertArrayHasKey('artist_credit.names.0.name', $release->save());
    }

    public function testEvent()
    {
        $release = new Release([]);
        $event = $release->event(new DateTime('2020-01-01'));
        $this->assertInstanceOf(seed\Event::class, $event);
        $this->assertArrayHasKey('events.0.date.year', $release->save());
        $this->assertSame('2020', $release->save()['events.0.date.year']);
    }

    public function testEventCountry()
    {
        $release = new Release([]);
        $event = $release->event(new DateTime('2020-01-01'), 'NO');
        $this->assertInstanceOf(seed\Event::class, $event);
        $this->assertArrayHasKey('events.0.date.year', $release->save());
        $this->assertSame('2020', $release->save()['events.0.date.year']);
        $this->assertSame('NO', $event->country);
        $event_array = $event->save('');
        $this->assertSame('NO', $event_array['country']);
    }
}
