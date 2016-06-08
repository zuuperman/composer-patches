<?php

/**
 * @file
 * Tests event dispatching.
 */

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Composer\Package\PackageInterface;
use cweagans\Composer\PatchEvent;
use cweagans\Composer\PatchEvents;

class PatchEventTest extends Unit
{
    /**
     * Tests all the getters.
     *
     * @dataProvider patchEventDataProvider
     *
     * @param string $eventName
     *   The name of the event.
     * @param \Composer\Package\PackageInterface $package
     *   The package that the patch applies to.
     * @param $url
     *   The URL to retrieve the patch from.
     * @param $description
     *   A human-readable description of the patch.
     */
    public function testPatchEventGetters($eventName, PackageInterface $package, $url, $description) {
        $patch_event = new PatchEvent($eventName, $package, $url, $description);
        $this->assertEquals($eventName, $patch_event->getName());
        $this->assertEquals($package, $patch_event->getPackage());
        $this->assertEquals($url, $patch_event->getUrl());
        $this->assertEquals($description, $patch_event->getDescription());
    }

    /**
     * Data provider for testGetters().
     *
     * @return array
     *   Patch metadata to create PatchEvents with.
     */
    public function patchEventDataProvider() {
        $package = Stub::make('Composer\Package\Package');
        return array(
            array(PatchEvents::PRE_PATCH_APPLY, $package, 'https://www.drupal.org', 'A test patch'),
            array(PatchEvents::POST_PATCH_APPLY, $package, 'https://www.drupal.org', 'A test patch'),
        );
    }
}
