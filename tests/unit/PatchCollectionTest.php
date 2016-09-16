<?php

/**
 * @file
 * Tests PatchCollection.
 */

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Composer\Package\Package;
use cweagans\Composer\Patch;
use cweagans\Composer\Tests\Dummy\PatchCollectionDummy;

class PatchCollectionTest extends Unit
{
    public function testPatchCollection()
    {
        // Create a PatchCollection and populate it with data.
        // We're using the Dummy class because it adds a way to check the Patch counts.
        $patchCollection = new PatchCollectionDummy();
        $patchCollection->addPatch($this->getMockPatch('test/package', 'root'));
        $patchCollection->addPatch($this->getMockPatch('test/package', 'root'));
        $patchCollection->addPatch($this->getMockPatch('test/package', 'root'));
        $patchCollection->addPatch($this->getMockPatch('test/package', 'dependency'));
        $patchCollection->addPatch($this->getMockPatch('test/package', 'dependency'));
        $patchCollection->addPatch($this->getMockPatch('test/package', 'dependency'));

        // Verify that we have the right number of patches here.
        $this->assertEquals(6, $patchCollection->getPatchCount());
        $this->assertEquals(3, $patchCollection->getPatchCount('root'));
        $this->assertEquals(3, $patchCollection->getPatchCount('dependency'));

        // Verify that we get operation objects back and that the subelements work.
        $count = 0;
        foreach ($patchCollection->getPatches('all') as $patch) {
            $this->assertEquals('Test Patch', $patch->getDescription());
            $this->assertEquals('https://example.com/asdf.patch', $patch->getUrl());
            $this->assertEquals('test/package', $patch->getPackageName());
            $count++;
        }

        // Double check the count.
        $this->assertEquals($count, $patchCollection->getPatchCount('all'));

        // Ensure that we only get patches of a specific type back.
        $count = 0;
        foreach ($patchCollection->getPatches('root') as $patch) {
            $this->assertEquals('root', $patch->getPatchType());
            $count++;
        }
        $this->assertEquals($count, $patchCollection->getPatchCount('root'));

        // Same, but for dependency patches.
        $count = 0;
        foreach ($patchCollection->getPatches('dependency') as $patch) {
            $this->assertEquals('dependency', $patch->getPatchType());
            $count++;
        }
        $this->assertEquals($count, $patchCollection->getPatchCount('dependency'));

    }

    protected function getMockPatch($packageName = 'test/package', $type = 'root') {
        // Return a mocked Patch
        return Stub::make(Patch::class, [
            'description' => 'Test Patch',
            'url' => 'https://example.com/asdf.patch',
            'type' => $type,
            'package' => $packageName,
        ]);
    }
    
}
