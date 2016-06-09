<?php

/**
 * @file
 * Tests PatchOperation.
 */

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Composer\Package\Package;
use cweagans\Composer\Operation\PatchOperation;
use cweagans\Composer\Patch;

class PatchOperationTest extends Unit
{
    public function testPatchOperation()
    {
        // It doesn't matter that this is an invalid patch URL. We're not testing that.
        $patch = new Patch('Test patch', 'https://www.example.com/asdf.patch');
        $package = Stub::make(Package::class, [
            'getName' => 'test/project'
        ]);

        $operation = new PatchOperation($package, $patch, PatchOperation::TYPE_ROOT_PATCH);
        $this->assertEquals('patch', $operation->getJobType());
        $this->assertEquals($package, $operation->getPackage());
        $this->assertEquals($patch, $operation->getPatch());
        $this->assertEquals(PatchOperation::TYPE_ROOT_PATCH,
            $operation->getPatchType());
        $this->assertEquals((string)$operation,
            'Patching test/project: Test patch (https://www.example.com/asdf.patch).');

        $operation = new PatchOperation($package, $patch, PatchOperation::TYPE_DEPENDENCY_PATCH);
        $this->assertEquals('patch', $operation->getJobType());
        $this->assertEquals($package, $operation->getPackage());
        $this->assertEquals($patch, $operation->getPatch());
        $this->assertEquals(PatchOperation::TYPE_DEPENDENCY_PATCH, $operation->getPatchType());
        $this->assertEquals((string)$operation,
            'Patching test/project: Test patch (https://www.example.com/asdf.patch).');

        try {
            $operation = new PatchOperation($package, $patch, 'invalid patch type');
        }
        catch (\InvalidArgumentException $e) {
            $this->assertEquals('Invalid patch type specified: invalid patch type', $e->getMessage());
        }
    }
}
