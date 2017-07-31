<?php

/**
 * @file
 * Test the PatchesFile resolver.
 */

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use cweagans\Composer\Exception\InvalidPatchesFileException;
use cweagans\Composer\Resolvers\PatchesFile;
use cweagans\Composer\Tests\Dummy\PatchCollectionDummy;

class PatchesFileResolverTest extends Unit
{
    public function testResolve()
    {
        $composer = new Composer();

        // Happy path.
        $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $package->setExtra([
           'patches-file' => __DIR__ . '/../_data/dummyPatches.json',
        ]);
        $composer->setPackage($package);
        $io = new NullIO();
        $event = Stub::make(PackageEvent::class, []);

        $collection = new PatchCollectionDummy();
        $resolver = new PatchesFile($composer, $io);
        $resolver->resolve($collection, $event);
        $this->assertEquals(4, $collection->getPatchCount());

        // Empty patches.
        try {
            $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
            $package->setExtra([
                'patches-file' => __DIR__ . '/../_data/dummyPatchesEmpty.json',
            ]);
            $composer->setPackage($package);

            $collection = new PatchCollectionDummy();
            $resolver = new PatchesFile($composer, $io);
            $resolver->resolve($collection, $event);
        } catch (InvalidPatchesFileException $e) {
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
            $resolver = new PatchesFile($composer, $io);
            $resolver->resolve($collection, $event);
        } catch (InvalidPatchesFileException $e) {
            $this->assertEquals('Syntax error, malformed JSON.', $e->getMessage());
        }
    }
}
