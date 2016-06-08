<?php

/**
 * @file
 * Test the Patch class.
 */

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use cweagans\Composer\Patch;

class PatchTest extends Unit
{
    /**
     * Test the getters for the Patch object.
     */
    public function testPatchGetters()
    {
        $patch = new Patch('A test patch', 'https://www.drupal.org', 'da39a3ee5e6b4b0d3255bfef95601890afd80709');
        $this->assertEquals('A test patch', $patch->getDescription());
        $this->assertEquals('https://www.drupal.org', $patch->getUrl());
        $this->assertEquals('da39a3ee5e6b4b0d3255bfef95601890afd80709', $patch->getHash());
        $this->assertEquals(Patch::PATCH_LEVEL_AUTO, $patch->getPatchLevel());

        $patch = new Patch('A test patch', 'https://www.drupal.org', 'da39a3ee5e6b4b0d3255bfef95601890afd80709', 1);
        $this->assertEquals('A test patch', $patch->getDescription());
        $this->assertEquals('https://www.drupal.org', $patch->getUrl());
        $this->assertEquals('da39a3ee5e6b4b0d3255bfef95601890afd80709', $patch->getHash());
        $this->assertEquals(1, $patch->getPatchLevel());
    }

    /**
     * Test that an exception is thrown when the SHA-1 param is not a SHA-1 hash.
     */
    public function testInvalidSha1Detection() {
        $this->expectException(\InvalidArgumentException::class);
        $patch = new Patch('A test patch', 'https://www.drupal.org', 'not a sha1 hash');
    }
}
