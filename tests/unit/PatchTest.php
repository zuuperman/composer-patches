<?php

/**
 * @file
 * Test the Patch class.
 */

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use cweagans\Composer\Exception\DownloadFailureException;
use cweagans\Composer\Exception\VerificationFailureException;
use cweagans\Composer\Patch;
use cweagans\Composer\Tests\Dummy\PatchDummy;

class PatchTest extends Unit
{
    /**
     * Test the getters for the Patch object.
     */
    public function testPatchGetters()
    {
        $patch = new Patch('A test patch', 'https://www.drupal.org');
        $this->assertEquals('A test patch', $patch->getDescription());
        $this->assertEquals('https://www.drupal.org', $patch->getUrl());
        $this->assertEquals(Patch::NO_CHECK_HASH, $patch->getHash());
        $this->assertEquals(Patch::PATCH_LEVEL_AUTO, $patch->getPatchLevel());
        $this->assertEquals(FALSE, $patch->isVerified());

        $patch = new Patch('A test patch', 'https://www.drupal.org', 'da39a3ee5e6b4b0d3255bfef95601890afd80709');
        $this->assertEquals('A test patch', $patch->getDescription());
        $this->assertEquals('https://www.drupal.org', $patch->getUrl());
        $this->assertEquals('da39a3ee5e6b4b0d3255bfef95601890afd80709', $patch->getHash());
        $this->assertEquals(Patch::PATCH_LEVEL_AUTO, $patch->getPatchLevel());
        $this->assertEquals(FALSE, $patch->isVerified());

        $patch = new Patch('A test patch', 'https://www.drupal.org', 'da39a3ee5e6b4b0d3255bfef95601890afd80709', 1);
        $this->assertEquals('A test patch', $patch->getDescription());
        $this->assertEquals('https://www.drupal.org', $patch->getUrl());
        $this->assertEquals('da39a3ee5e6b4b0d3255bfef95601890afd80709', $patch->getHash());
        $this->assertEquals(1, $patch->getPatchLevel());
        $this->assertEquals(FALSE, $patch->isVerified());
    }

    /**
     * Test that an exception is thrown when the SHA-1 param is not a SHA-1 hash.
     */
    public function testInvalidSha1Detection()
    {
        $this->expectException(\InvalidArgumentException::class);
        $patch = new Patch('A test patch', 'https://www.drupal.org', 'not a sha1 hash');
    }

    /**
     * Test that a local file path is not changed.
     */
    public function testLocalPatchFile()
    {
        touch('/tmp/test.patch');

        // If the file exists (which is does if the touch() call didn't fail),
        // the Patch class should automatically set the local path.
        $patch = new PatchDummy('A local patch', '/tmp/test.patch');
        $this->assertEquals('/tmp/test.patch', $patch->getLocalPath());

        // The download() method should be essentially a no-op for patches that
        // already exist locally.
        $patch->download();
        $this->assertEquals('/tmp/test.patch', $patch->getLocalPath());

        // Clean up.
        if (file_exists('/tmp/test.patch')) {
            unlink('/tmp/test.patch');
        }
    }

    /**
     * Test patch download functionality.
     *
     * @todo: Make this test case better. Seems strange to catch exceptions & assert message text.
     */
    public function testPatchDownload()
    {
        // Happy path.
        $patch = new PatchDummy('A test patch', 'https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches/pull/6.patch');
        $patch->download();
        $this->assertTrue(file_exists($patch->getLocalPath()));

        // Non-existent URL.
        try {
            $patch = new PatchDummy('A test patch', 'http://example.com/asdf.patch');
            $patch->download();
        }
        catch (DownloadFailureException $e) {
            $this->assertEquals('Could not download patch from http://example.com/asdf.patch', $e->getMessage());
        }

        // Valid patch URL, but simulated unwritable local filesystem
        try {
            $patch = new PatchDummy('A test patch', 'https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches/pull/6.patch');
            $patch->download('/test.patch');
        }
        catch (DownloadFailureException $e) {
            $this->assertEquals('Could not write patch to /test.patch', $e->getMessage());
        }
    }

    /**
     * Test patch verification.
     *
     * @todo: Make this test case better. Seems strange to catch exceptions & assert message text.
     */
    public function testVerifyPatch()
    {
        // Happy path.
        $patch = new Patch('Test patch', 'https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches/pull/6.patch', 'd0f5d393fbbcb0a6836b5b89808cda16fe442cc3');
        $patch->download();
        $patch->verifyPatch();
        $this->assertEquals(TRUE, $patch->isVerified());

        // Expected failure: verifying without downloading first.
        $patch = new Patch('Test patch', 'https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches/pull/6.patch');
        try {
            $patch->verifyPatch();
        }
        catch (\LogicException $e) {
            $this->assertEquals('Cannot verify patch without downloading it first!', $e->getMessage());
        }

        // Expected failure: Can't load data from path.
        // Setting a fake test path via PatchDummy to ensure that the patch can't
        // be loaded.
        $patch = new PatchDummy('Test patch', 'https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches/pull/6.patch');
        $patch->setLocalPath('/test.patch');
        try {
            $patch->verifyPatch();
        }
        catch (VerificationFailureException $e) {
            $this->assertEquals('Could not load patch from /test.patch', $e->getMessage());
        }

        // Ensure that a patch instantiated without a hash gets a hash on verify.
        $patch = new PatchDummy('Test patch', 'https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches/pull/6.patch');
        $patch->download();
        $patch->verifyPatch();
        $this->assertEquals('d0f5d393fbbcb0a6836b5b89808cda16fe442cc3', $patch->getHash());

        // Ensure that a patch with a mismatched hash throws an exception.
        $patch = new PatchDummy('Test patch', 'https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches/pull/6.patch', 'd0f5d393fbbcb0a6836b5b89808cda16fe442cc4');
        $patch->download();
        try {
            $patch->verifyPatch();
        }
        catch (VerificationFailureException $e) {
            $this->assertEquals('SHA-1 mismatch for patch downloaded from ' . $patch->getUrl(), $e->getMessage());
        }

    }
}
