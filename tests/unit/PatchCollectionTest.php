<?php

/**
 * @file
 * Tests PatchCollection.
 */

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Composer\Package\Package;
use cweagans\Composer\Operation\PatchOperation;
use cweagans\Composer\Patch;
use cweagans\Composer\Tests\Dummy\PatchCollectionDummy;

class PatchCollectionTest extends Unit
{
    public function testPatchCollection()
    {
        // Create a PatchCollection and populate it with data.
        // We're using the Dummy class because it adds a way to check the Operation counts.
        $patchCollection = new PatchCollectionDummy();
        $patchCollection->addPatch($this->getPatchOperation('', PatchOperation::TYPE_ROOT_PATCH));
        $patchCollection->addPatch($this->getPatchOperation('', PatchOperation::TYPE_ROOT_PATCH));
        $patchCollection->addPatch($this->getPatchOperation('', PatchOperation::TYPE_ROOT_PATCH));
        $patchCollection->addPatch($this->getPatchOperation('', PatchOperation::TYPE_DEPENDENCY_PATCH));
        $patchCollection->addPatch($this->getPatchOperation('', PatchOperation::TYPE_DEPENDENCY_PATCH));
        $patchCollection->addPatch($this->getPatchOperation('', PatchOperation::TYPE_DEPENDENCY_PATCH));

        // Verify that we have the right number of patches here.
        $this->assertEquals(6, $patchCollection->getPatchCount());
        $this->assertEquals(3, $patchCollection->getPatchCount(PatchOperation::TYPE_ROOT_PATCH));
        $this->assertEquals(3, $patchCollection->getPatchCount(PatchOperation::TYPE_DEPENDENCY_PATCH));

        // Verify that we get operation objects back and that the subelements work.
        $count = 0;
        foreach ($patchCollection->getPatches('all') as $operation) {
            /** @var PatchOperation $operation */
            $this->assertEquals('Test patch', $operation->getPatch()->getDescription());
            $this->assertEquals('https://example.com/asdf.patch', $operation->getPatch()->getUrl());
            $this->assertContains('test/package', $operation->getPackage()->getName());
            $count++;
        }

        // Double check the count.
        $this->assertEquals($count, $patchCollection->getPatchCount());

        // Ensure that we only get root patches back when we request them
        $count = 0;
        foreach ($patchCollection->getPatches(PatchOperation::TYPE_ROOT_PATCH) as $operation) {
            /** @var PatchOperation $operation */
            $this->assertEquals(PatchOperation::TYPE_ROOT_PATCH, $operation->getPatchType());
            $count++;
        }
        $this->assertEquals($count, $patchCollection->getPatchCount(PatchOperation::TYPE_ROOT_PATCH));

        // Ensure that we only get dependency patches back when we request them
        $count = 0;
        foreach ($patchCollection->getPatches(PatchOperation::TYPE_DEPENDENCY_PATCH) as $operation) {
            /** @var PatchOperation $operation */
            $this->assertEquals(PatchOperation::TYPE_DEPENDENCY_PATCH, $operation->getPatchType());
            $count++;
        }
        $this->assertEquals($count, $patchCollection->getPatchCount(PatchOperation::TYPE_DEPENDENCY_PATCH));
    }

    protected function getPatchOperation($packageName = '', $type = 'root') {
        // Generate a package name if not supplied.
        if ($packageName == '') {
            $packageName = 'test/package' . uniqid();
        }

        $operation = Stub::make(PatchOperation::class, [
            'getPatchType' => $type,
            'getPackage' => Stub::make(Package::class, [
                'getName' => $packageName,
            ]),
            'getPatch' => Stub::make(Patch::class, [
                'description' => 'Test patch',
                'url' => 'https://example.com/asdf.patch',
            ]),
        ]);

        return $operation;
    }
}
