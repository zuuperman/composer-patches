<?php

/**
 * @file
 * Test the Patches plugin class.
 */

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use Composer\Composer;
use Composer\Installer\PackageEvents;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use Composer\Script\ScriptEvents;
use cweagans\Composer\Patches;

class PatchesPluginTest extends Unit
{
    // Despite not asserting anything in this test, this ensures that if the
    // plugin is required and not configured (through any means of doing so),
    // it won't cause an exception to be thrown or something.
    public function testActivate()
    {
        $plugin = new Patches();
        $composer = new Composer();
        $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $package->setExtra([]);
        $composer->setPackage($package);
        $io = new NullIO();
        $plugin->activate($composer, $io);
    }

    public function testSubscribedEvents()
    {
        $allowed_events = [
            ScriptEvents::PRE_INSTALL_CMD => 'pre_install_cmd',
            ScriptEvents::PRE_UPDATE_CMD => 'pre_update_cmd',
            PackageEvents::PRE_PACKAGE_INSTALL => 'pre_package_install',
            PackageEvents::POST_PACKAGE_INSTALL => 'post_package_install',
            PackageEvents::PRE_PACKAGE_UPDATE => 'pre_package_update',
            PackageEvents::POST_PACKAGE_UPDATE => 'post_package_update',
        ];
        $plugin_events = Patches::getSubscribedEvents();

        // Make sure that every event the plugin listens to is allowed.
        foreach ($plugin_events as $event_name => $handler) {
            $this->assertArrayHasKey($event_name, $allowed_events);
        }

        // Make sure that every event that is allowed is listened to.
        foreach ($allowed_events as $event_name => $text) {
            $this->assertArrayHasKey($event_name, $plugin_events);
        }
    }

}
