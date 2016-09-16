<?php

/**
 * @file
 * Tests event dispatching.
 */

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use cweagans\Composer\Event\PatchEvent;
use cweagans\Composer\Event\PatchEvents;
use cweagans\Composer\Operation\PatchOperation;
use cweagans\Composer\Patch;

class PatchEventTest extends Unit
{
    /**
     * Tests all the getters.
     *
     * @dataProvider patchEventDataProvider
     *
     * @param string $eventName
     *   The name of the event.
     * @param Patch $patch
     *   The Patch that emitted the event.
     */
    public function testPatchEventGetters($eventName, Patch $patch)
    {
        $patch_event = new PatchEvent($eventName, $patch);
        $this->assertEquals($eventName, $patch_event->getName());
        $this->assertEquals($patch, $patch_event->getPatch());
        $this->assertEquals('A test patch', $patch_event->getPatch()->getDescription());
        $this->assertEquals('https://www.drupal.org', $patch_event->getPatch()->getUrl());
    }

    /**
     * Data provider for testGetters().
     *
     * @return array
     *   Patch metadata to create PatchEvents with.
     */
    public function patchEventDataProvider()
    {
        $patch = new Patch('test/patch', 'A test patch', 'https://www.drupal.org', 'root');
        return array(
            array(PatchEvents::PRE_PATCH_APPLY, $patch),
            array(PatchEvents::POST_PATCH_APPLY, $patch),
        );
    }
}
