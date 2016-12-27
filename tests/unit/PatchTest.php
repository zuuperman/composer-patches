<?php

/**
 * @file
 * Test the Patch class.
 */

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use cweagans\Composer\Exception\DownloadFailureException;
use cweagans\Composer\Exception\InvalidPatchException;
use cweagans\Composer\Exception\VerificationFailureException;
use cweagans\Composer\Patch;
use cweagans\Composer\Tests\Dummy\PatchDummy;

class PatchTest extends Unit
{
    public function testCreateFromJsonObject()
    {
        // No description.
        try {
            $json = new \stdClass();
            $json->url = 'http://drupal.org';
            $patch = Patch::createFromJsonObject('test/package', $json, 'test');
        }
        catch (InvalidPatchException $e) {
            $this->assertEquals('All patches must have a description and URL.', $e->getMessage());
        }

        // No URL.
        try {
            $json = new \stdClass();
            $json->description = 'Test patch';
            $patch = Patch::createFromJsonObject('test/package', $json, 'test');
        }
        catch (InvalidPatchException $e) {
            $this->assertEquals('All patches must have a description and URL.', $e->getMessage());
        }

        // Happy path.
        $json = new \stdClass();
        $json->url = 'http://drupal.org';
        $json->description = 'Test patch';
        $patch = Patch::createFromJsonObject('test/package', $json, 'test');

        $this->assertEquals('http://drupal.org', $patch->getUrl());
        $this->assertEquals('Test patch', $patch->getDescription());
        $this->assertEquals('test/package', $patch->getPackageName());
        $this->assertEquals('test', $patch->getPatchType());
        $this->assertEquals(Patch::NO_CHECK_HASH, $patch->getHash());
        $this->assertEquals(Patch::PATCH_LEVEL_AUTO, $patch->getPatchLevel());

        // Happy path + hash.
        $json = new \stdClass();
        $json->url = 'http://drupal.org';
        $json->description = 'Test patch';
        $json->hash = 'da39a3ee5e6b4b0d3255bfef95601890afd80709';
        $patch = Patch::createFromJsonObject('test/package', $json, 'test');

        $this->assertEquals('http://drupal.org', $patch->getUrl());
        $this->assertEquals('Test patch', $patch->getDescription());
        $this->assertEquals('test/package', $patch->getPackageName());
        $this->assertEquals('test', $patch->getPatchType());
        $this->assertEquals('da39a3ee5e6b4b0d3255bfef95601890afd80709', $patch->getHash());
        $this->assertEquals(Patch::PATCH_LEVEL_AUTO, $patch->getPatchLevel());

        // Happy path + patch level.
        $json = new \stdClass();
        $json->url = 'http://drupal.org';
        $json->description = 'Test patch';
        $json->hash = 'da39a3ee5e6b4b0d3255bfef95601890afd80709';
        $json->patch_level = 3;
        $patch = Patch::createFromJsonObject('test/package', $json, 'test');

        $this->assertEquals('http://drupal.org', $patch->getUrl());
        $this->assertEquals('Test patch', $patch->getDescription());
        $this->assertEquals('test/package', $patch->getPackageName());
        $this->assertEquals('test', $patch->getPatchType());
        $this->assertEquals('da39a3ee5e6b4b0d3255bfef95601890afd80709', $patch->getHash());
        $this->assertEquals(3, $patch->getPatchLevel());
    }

    /**
     * Test the jsonSerialize method.
     */
    public function testJsonSerialize()
    {
        $json = new \stdClass();
        $json->url = 'http://drupal.org';
        $json->description = 'Test patch';

        $patch = Patch::createFromJsonObject('test/package', $json, 'test');
        $jsonPatch = $patch->jsonSerialize();

        $this->assertEquals($json->url, $jsonPatch->url);
        $this->assertEquals($json->description, $jsonPatch->description);
        $this->assertEquals(Patch::NO_CHECK_HASH, $jsonPatch->hash);

        $json = new \stdClass();
        $json->url = 'http://drupal.org';
        $json->description = 'Test patch';
        $json->patch_level = 2;

        $patch = Patch::createFromJsonObject('test/package', $json, 'test');
        $jsonPatch = $patch->jsonSerialize();

        $this->assertEquals($json->url, $jsonPatch->url);
        $this->assertEquals($json->description, $jsonPatch->description);
        $this->assertEquals($json->patch_level, $jsonPatch->patch_level);
        $this->assertEquals(Patch::NO_CHECK_HASH, $jsonPatch->hash);
    }

    /**
     * Test the getters for the Patch object.
     */
    public function testPatchGetters()
    {
        $patch = new Patch('drupal/drupal', 'A test patch', 'https://www.drupal.org', 'test');
        $this->assertEquals('drupal/drupal', $patch->getPackageName());
        $this->assertEquals('A test patch', $patch->getDescription());
        $this->assertEquals('https://www.drupal.org', $patch->getUrl());
        $this->assertEquals('test', $patch->getPatchType());
        $this->assertEquals(Patch::NO_CHECK_HASH, $patch->getHash());
        $this->assertEquals(Patch::PATCH_LEVEL_AUTO, $patch->getPatchLevel());
        $this->assertEquals(FALSE, $patch->isVerified());

        $patch = new Patch('drupal/drupal', 'A test patch', 'https://www.drupal.org', 'test', 'da39a3ee5e6b4b0d3255bfef95601890afd80709');
        $this->assertEquals('drupal/drupal', $patch->getPackageName());
        $this->assertEquals('A test patch', $patch->getDescription());
        $this->assertEquals('https://www.drupal.org', $patch->getUrl());
        $this->assertEquals('test', $patch->getPatchType());
        $this->assertEquals('da39a3ee5e6b4b0d3255bfef95601890afd80709', $patch->getHash());
        $this->assertEquals(Patch::PATCH_LEVEL_AUTO, $patch->getPatchLevel());
        $this->assertEquals(FALSE, $patch->isVerified());

        $patch = new Patch('drupal/drupal', 'A test patch', 'https://www.drupal.org', 'test', 'da39a3ee5e6b4b0d3255bfef95601890afd80709', 1);
        $this->assertEquals('drupal/drupal', $patch->getPackageName());
        $this->assertEquals('A test patch', $patch->getDescription());
        $this->assertEquals('https://www.drupal.org', $patch->getUrl());
        $this->assertEquals('test', $patch->getPatchType());
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
        $patch = new Patch('drupal/drupal', 'A test patch', 'https://www.drupal.org', 'test', 'not a sha1 hash');
    }

    /**
     * Test that a local file path is not changed.
     */
    public function testLocalPatchFile()
    {
        touch('/tmp/test.patch');

        // If the file exists (which is does if the touch() call didn't fail),
        // the Patch class should automatically set the local path.
        $patch = new PatchDummy('drupal/drupal', 'A local patch', '/tmp/test.patch', 'test');
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
        $patch = new PatchDummy('drupal/drupal', 'A test patch', 'https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches/pull/6.patch', 'test');
        $patch->download();
        $this->assertTrue(file_exists($patch->getLocalPath()));

        // Non-existent URL.
        try {
            $patch = new PatchDummy('drupal/drupal', 'A test patch', 'http://example.com/asdf.patch', 'test');
            $patch->download();
        }
        catch (DownloadFailureException $e) {
            $this->assertEquals('Could not download patch from http://example.com/asdf.patch', $e->getMessage());
        }

        // Valid patch URL, but simulated unwritable local filesystem
        try {
            $patch = new PatchDummy('drupal/drupal', 'A test patch', 'https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches/pull/6.patch', 'test');
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
        $patch = new Patch('drupal/drupal', 'Test patch', 'https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches/pull/6.patch', 'test', 'd0f5d393fbbcb0a6836b5b89808cda16fe442cc3');
        $patch->download();
        $patch->verifyPatch();
        $this->assertEquals(TRUE, $patch->isVerified());

        // Expected failure: verifying without downloading first.
        $patch = new Patch('drupal/drupal', 'Test patch', 'https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches/pull/6.patch', 'test');
        try {
            $patch->verifyPatch();
        }
        catch (\LogicException $e) {
            $this->assertEquals('Cannot verify patch without downloading it first!', $e->getMessage());
        }

        // Expected failure: Can't load data from path.
        // Setting a fake test path via PatchDummy to ensure that the patch can't
        // be loaded.
        $patch = new PatchDummy('drupal/drupal', 'Test patch', 'https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches/pull/6.patch', 'test');
        $patch->setLocalPath('/test.patch');
        try {
            $patch->verifyPatch();
        }
        catch (VerificationFailureException $e) {
            $this->assertEquals('Could not load patch from /test.patch', $e->getMessage());
        }

        // Ensure that a patch instantiated without a hash gets a hash on verify.
        $patch = new PatchDummy('drupal/drupal', 'Test patch', 'https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches/pull/6.patch', 'test');
        $patch->download();
        $patch->verifyPatch();
        $this->assertEquals('d0f5d393fbbcb0a6836b5b89808cda16fe442cc3', $patch->getHash());

        // Ensure that a patch with a mismatched hash throws an exception.
        $patch = new PatchDummy('drupal/drupal', 'Test patch', 'https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches/pull/6.patch', 'test', 'd0f5d393fbbcb0a6836b5b89808cda16fe442cc4');
        $patch->download();
        try {
            $patch->verifyPatch();
        }
        catch (VerificationFailureException $e) {
            $this->assertEquals('SHA-1 mismatch for patch downloaded from ' . $patch->getUrl(), $e->getMessage());
        }

    }
}
