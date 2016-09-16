<?php

/**
 * @file
 * Test the PatchesFile resolver.
 */

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use Composer\Composer;
use Composer\Package\RootPackage;
use cweagans\Composer\Exception\InvalidPatchesFileException;
use cweagans\Composer\Resolvers\PatchesFile;
use cweagans\Composer\Tests\Dummy\PatchCollectionDummy;

class PatchesFileResolverTest extends Unit
{
    public function testIsEnabled()
    {
        $composer = new Composer();

        // patches-file key not set at all.
        $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $composer->setPackage($package);
        $resolver = new PatchesFile($composer, []);
        $this->assertFalse($resolver->isEnabled());

        // patches-file key set to a nonexistent file.
        $package->setExtra([
            'patches-file' => '/nonexistentfile.json'
        ]);
        $composer->setPackage($package);
        $resolver = new PatchesFile($composer, []);
        $this->assertFalse($resolver->isEnabled());

        // patches-file key set to a readable json file.
        $package->setExtra([
            'patches-file' => __DIR__ . '/../_data/dummyPatches.json'
        ]);
        $composer->setPackage($package);
        $resolver = new PatchesFile($composer, []);
        $this->assertTrue($resolver->isEnabled());
    }

    public function testGetMessage()
    {
        $composer = new Composer();
        $resolver = new PatchesFile($composer, []);
        $this->assertEquals('Gathering patches from patches file.', $resolver->getMessage());
    }

    public function testResolve()
    {
        $composer = new Composer();

        // Happy path.
        $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $package->setExtra([
           'patches-file' => __DIR__ . '/../_data/dummyPatches.json',
        ]);
        $composer->setPackage($package);

        $collection = new PatchCollectionDummy();
        $resolver = new PatchesFile($composer, []);
        $resolver->resolve($collection);
        $this->assertEquals(4, $collection->getPatchCount());

        // Empty patches.
        try {
            $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
            $package->setExtra([
                'patches-file' => __DIR__ . '/../_data/dummyPatchesEmpty.json',
            ]);
            $composer->setPackage($package);

            $collection = new PatchCollectionDummy();
            $resolver = new PatchesFile($composer, []);
            $resolver->resolve($collection);
        }
        catch (InvalidPatchesFileException $e) {
            $this->assertEquals('No patches found.', $e->getMessage());
        }

        // Invalid JSON.
        try {
            $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
            $package->setExtra([
                'patches-file' => __DIR__ . '/../_data/dummyPatchesInvalid.json',
            ]);
            $composer->setPackage($package);

            $collection = new PatchCollectionDummy();
            $resolver = new PatchesFile($composer, []);
            $resolver->resolve($collection);
        }
        catch (InvalidPatchesFileException $e) {
            $this->assertEquals('Syntax error, malformed JSON.', $e->getMessage());
        }
    }

}
