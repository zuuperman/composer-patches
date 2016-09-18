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

    /**
     * @dataProvider castEnvvarToBoolDataProvider
     */
    public function testCastEnvvarToBool($input, $expected)
    {
        $plugin = new Patches();
        // No typehint = we can just throw junk data to the function for the
        // second arg here.
        $result = $plugin->castEnvvarToBool($input, 'fake');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test as many cases for boolean string parsing as possible.
     */
    public function castEnvvarToBoolDataProvider()
    {
        return [
            ['FALSE', FALSE],
            ['False', FALSE],
            ['FaLsE', FALSE],
            ['false', FALSE],
            ['NO', FALSE],
            ['No', FALSE],
            ['no', FALSE],
            ['0', FALSE],
            ['TRUE', TRUE],
            ['True', TRUE],
            ['TrUe', TRUE],
            ['true', TRUE],
            ['YES', TRUE],
            ['Yes', TRUE],
            ['yes', TRUE],
            ['1', TRUE],
            // This is a special case for the test. Since we're passing 'fake'
            // as the second param, any case that doesn't result in a bool
            // should result in the string 'fake'.
            ['asdf', 'fake'],
        ];
    }

    /**
     * @dataProvider castEnvvarToArrayDataProvider
     */
    public function testCastEnvvarToArray($input, $expected)
    {
        $plugin = new Patches();
        // No typehint = we can just throw junk data to the function for the
        // second arg here.
        $result = $plugin->castEnvvarToArray($input, ['fake']);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test as many cases for envvar -> array parsing as possible.
     */
    public function castEnvvarToArrayDataProvider()
    {
        return [
            [
                'project/someproject',
                ['project/someproject']
            ],
            [
                'project/someproject,another/project',
                ['project/someproject','another/project']
            ],
            // This is a special case for the test. Since we're passing 'fake'
            // as the second param, any case that doesn't result in a bool
            // should result in the string 'fake'.
            [
                ',',
                ['fake'],
            ]
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Option some-random-value is not a valid composer-patches option.
     */
    public function testInvalidConfiguration()
    {
        // Set up a new instance of the plugin and call ->activate().
        $plugin = new Patches();
        $composer = new Composer();
        $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $package->setExtra([
            'patches-config' => [
                'some-random-value' => 'asdf',
            ],
        ]);
        $composer->setPackage($package);
        $io = new NullIO();
        $plugin->activate($composer, $io);
    }

    public function testComposerJsonConfiguration()
    {
        // Set up a new instance of the plugin and call ->activate().
        $plugin = new Patches();
        $composer = new Composer();
        $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $package->setExtra([]);
        $composer->setPackage($package);
        $io = new NullIO();
        $plugin->activate($composer, $io);

        // Everything should be at it's default value.
        $this->assertTrue($plugin->getConfigValue('patching-enabled'));
        $this->assertTrue($plugin->getConfigValue('dependency-patching-enabled'));
        $this->assertTrue($plugin->getConfigValue('stop-on-patch-failure'));
        $this->assertEquals([], $plugin->getConfigValue('ignore-packages'));

        $package->setExtra([
           'patches-config' => [
               'patching-enabled' => FALSE,
           ],
        ]);
        $composer->setPackage($package);
        $plugin->activate($composer, $io);
        $this->assertFalse($plugin->getConfigValue('patching-enabled'));

        $package->setExtra([
            'patches-config' => [
                'dependency-patching-enabled' => FALSE,
            ],
        ]);
        $composer->setPackage($package);
        $plugin->activate($composer, $io);
        $this->assertFalse($plugin->getConfigValue('dependency-patching-enabled'));

        $package->setExtra([
            'patches-config' => [
                'stop-on-patch-failure' => FALSE,
            ],
        ]);
        $composer->setPackage($package);
        $plugin->activate($composer, $io);
        $this->assertFalse($plugin->getConfigValue('stop-on-patch-failure'));

        $package->setExtra([
            'patches-config' => [
                'ignore-packages' => [
                    'some/package',
                    'another/package'
                ],
            ],
        ]);
        $composer->setPackage($package);
        $plugin->activate($composer, $io);
        $this->assertEquals(['some/package', 'another/package'], $plugin->getConfigValue('ignore-packages'));
    }

    // NOTE: This test function modifies environment variables.
    public function testEnvvarConfiguration()
    {
        // Set up a new instance of the plugin and call ->activate().
        $plugin = new Patches();
        $composer = new Composer();
        $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $package->setExtra([]);
        $composer->setPackage($package);
        $io = new NullIO();
        $plugin->activate($composer, $io);

        // Everything should be at it's default value.
        $this->assertTrue($plugin->getConfigValue('patching-enabled'));
        $this->assertTrue($plugin->getConfigValue('dependency-patching-enabled'));
        $this->assertTrue($plugin->getConfigValue('stop-on-patch-failure'));
        $this->assertEquals([], $plugin->getConfigValue('ignore-packages'));

        // Change an environment variable and call ->activate() again, which
        // causes the plugin to reconfigure.
        putenv('COMPOSER_PATCHES_PATCHING_ENABLED=False');
        $plugin->activate($composer, $io);
        $this->assertFalse($plugin->getConfigValue('patching-enabled'));

        putenv('COMPOSER_PATCHES_DEPENDENCY_PATCHING_ENABLED=False');
        $plugin->activate($composer, $io);
        $this->assertFalse($plugin->getConfigValue('dependency-patching-enabled'));

        putenv('COMPOSER_PATCHES_STOP_ON_PATCH_FAILURE=False');
        $plugin->activate($composer, $io);
        $this->assertFalse($plugin->getConfigValue('stop-on-patch-failure'));

        putenv('COMPOSER_PATCHES_IGNORE_PACKAGES=some/package,test/package');
        $plugin->activate($composer, $io);
        $this->assertEquals($plugin->getConfigValue('ignore-packages'), ['some/package', 'test/package']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Config key test does not exist.
     */
    public function testGetInvalidConfigKey()
    {
        $plugin = new Patches();
        $plugin->getConfigValue('test');
    }
}
