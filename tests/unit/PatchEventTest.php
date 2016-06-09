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
     * @param PatchOperation $operation
     *   The PatchOperation object that relates a Patch to a Package.
     */
    public function testPatchEventGetters($eventName, PatchOperation $operation)
    {
        $patch_event = new PatchEvent($eventName, $operation);
        $this->assertEquals($eventName, $patch_event->getName());
        $this->assertEquals($operation, $patch_event->getOperation());
        $this->assertEquals('A test patch', $patch_event->getOperation()->getPatch()->getDescription());
        $this->assertEquals('https://www.drupal.org', $patch_event->getOperation()->getPatch()->getUrl());
    }

    /**
     * Data provider for testGetters().
     *
     * @return array
     *   Patch metadata to create PatchEvents with.
     */
    public function patchEventDataProvider()
    {
        $package = Stub::make('Composer\Package\Package');
        $patch = new Patch('A test patch', 'https://www.drupal.org');
        $operation = new PatchOperation($package, $patch, 'root');
        return array(
            array(PatchEvents::PRE_PATCH_APPLY, $operation),
            array(PatchEvents::POST_PATCH_APPLY, $operation),
        );
    }
}
