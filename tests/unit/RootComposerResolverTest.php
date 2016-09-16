<?php

/**
 * @file
 * Test the RootComposer resolver.
 */

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use Composer\Composer;
use Composer\Package\RootPackage;
use cweagans\Composer\Resolvers\RootComposer;
use cweagans\Composer\Tests\Dummy\PatchCollectionDummy;

class RootComposerResolverTest extends Unit
{
    public function testIsEnabled()
    {
        $root_package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $composer = new Composer();

        // Enabled
        $root_package->setExtra([
           'patches' => [],
        ]);
        $composer->setPackage($root_package);
        $root_composer_resolver = new RootComposer($composer, []);
        $this->assertTrue($root_composer_resolver->isEnabled());

        // Not enabled
        $root_package->setExtra([]);
        $composer->setPackage($root_package);
        $root_composer_resolver = new RootComposer($composer, []);
        $this->assertFalse($root_composer_resolver->isEnabled());
    }

    public function testGetMessage()
    {
        $composer = new Composer();
        $resolver = new RootComposer($composer, []);
        $this->assertEquals('Gathering patches from root package.', $resolver->getMessage());
    }

    public function testResolve()
    {
        $patch_collection = new PatchCollectionDummy();
        $root_package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $root_package->setExtra(['patches' => []]);
        $composer = new Composer();
        $composer->setPackage($root_package);

        // Empty patch list.
        $resolver = new RootComposer($composer, []);
        $resolver->resolve($patch_collection);
        $this->assertEquals(0, $patch_collection->getPatchCount());

        // One patch.
        $patch = new \stdClass();
        $patch->url = 'http://drupal.org';
        $patch->description = 'Test patch';
        $root_package->setExtra([
            'patches' => [
                'test/package' => [
                    0 => $patch,
                ]
            ]
        ]);
        $composer->setPackage($root_package);

        $resolver = new RootComposer($composer, []);
        $resolver->resolve($patch_collection);
        $this->assertEquals(1, $patch_collection->getPatchCount());
    }

}
