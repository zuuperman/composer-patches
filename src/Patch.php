<?php

/**
 * @file
 * Contains cweagans\Composer\Patch.
 */

namespace cweagans\Composer;

use Composer\IO\IOInterface;
use Composer\Package\Package;
use cweagans\Composer\Exception\DownloadFailureException;
use cweagans\Composer\Exception\InvalidPatchException;
use cweagans\Composer\Exception\VerificationFailureException;

// @TODO: Remove $package references here - package should be passed in to applyTo()
class Patch implements \JsonSerializable
{
    /**
     * PATCH_LEVEL_AUTO will cause composer-patches to attempt to auto-apply the
     * patch.
     *
     * @var string
     */
    const PATCH_LEVEL_AUTO = 'auto';

    /**
     * NO_CHECK_HASH will cause composer-patches to attempt to auto-apply the
     * patch.
     *
     * @var string
     */
    const NO_CHECK_HASH = 'nocheck';

    /**
     * @var string
     *   The package that this patch applies to.
     */
    protected $package;

    /**
     * @var string
     *   A short description of a patch.
     */
    protected $description;

    /**
     * @var string
     *   The location of a patch.
     */
    protected $url;

    /**
     * @var string
     *   The SHA-1 hash of a patch.
     */
    protected $hash = self::NO_CHECK_HASH;

    /**
     * @var string
     *   The path to the locally downloaded copy of the patch.
     */
    protected $localPath;

    /**
     * @var string
     *   The type of patch this object represents. Can be any arbitrary string.
     *   The specific string is chosen by the resolver that added the patch.
     */
    protected $type;

    /**
     * @var string
     *   The preferred patch level to use when applying a patch. When this value
     *   is set, *only* this patch level will be attempted. Should be a positive
     *   integer or self::PATCH_LEVEL_AUTO.
     *
     * @see The -p option for the `patch` command.
     */
    protected $patchLevel = self::PATCH_LEVEL_AUTO;

    /**
     * @var bool
     *   false if patch has not been verified against a SHA-1 hash. true after
     *   successful verification.
     */
    protected $verified = false;

    /**
     * Patch constructor.
     *
     * @param $package
     * @param $description
     * @param $url
     * @param $type
     * @param $hash
     * @param $patchLevel
     */
    public function __construct($package, $description, $url, $type, $hash = null, $patchLevel = null)
    {
        $this->package = $package;
        $this->description = $description;
        $this->url = $url;
        $this->type = $type;

        // If the file exists locally, then we can just set the localPath now
        // and save some work later.
        if (file_exists($this->url)) {
            $this->localPath = $this->url;
        }

        // Hash defaults to self::NO_CHECK_HASH if one isn't supplied in the patch
        // definition in composer.json.
        if (isset($hash)) {
            if ($this->isValidSha1($hash)) {
                $this->hash = $hash;
            } else {
                throw new \InvalidArgumentException('Invalid SHA-1 hash supplied for patch with url ' . $url);
            }
        }

        // Patches default to self::PATCH_LEVEL_AUTO, but this can be overridden.
        if (isset($patchLevel)) {
            $this->patchLevel = $patchLevel;
        }
    }

    /**
     * Creates a Patch object from an object parsed from a composer.json patches array.
     *
     * @param string $package
     *   The package the patch should be applied to.
     * @param \stdClass $jsonObject
     *   The JSON object parsed from composer.json
     * @param string $type
     *   The type of patch.
     *
     * @throws InvalidPatchException
     * @return Patch
     */
    public static function createFromJsonObject($package, $jsonObject, $type)
    {
        if (!is_array($jsonObject)) {
            $jsonObject = (array)$jsonObject;
        }
        // We always have to have a description and URL.
        if (!isset($jsonObject['description']) || !isset($jsonObject['url'])) {
            throw new InvalidPatchException('All patches must have a description and URL.');
        }
        $description = $jsonObject['description'];
        $url = $jsonObject['url'];

        // If there's a hash, use it.
        $hash = null;
        if (isset($jsonObject['hash'])) {
            $hash = $jsonObject['hash'];
        }

        // If there's a patch level, use it.
        $patch_level = null;
        if (isset($jsonObject['patch_level'])) {
            $patch_level = $jsonObject['patch_level'];
        }

        // Finally, create a new instance of this class.
        return new static($package, $description, $url, $type, $hash, $patch_level);
    }

    /**
     * Export this patch to a JSON object.
     *
     * Note that all properties should be set by the time that serialization
     * happens.
     */
    public function jsonSerialize()
    {
        $patch = new \stdClass();
        $patch->description = $this->description;
        $patch->url = $this->url;
        $patch->patch_level = $this->patchLevel;
        $patch->hash = $this->hash;
        return $patch;
    }

    /**
     * @return string
     */
    public function getPackageName()
    {
        return $this->package;
    }

    /**
     * @return string
     */
    public function getPatchType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function getPatchLevel()
    {
        return $this->patchLevel;
    }

    /**
     * @return bool
     */
    public function isVerified()
    {
        return $this->verified;
    }

    /**
     * Downloads a patch and checks against the SHA-1 hash.
     *
     * @param string $localPathOverride
     *   A path to save the patch file to. If not supplied, the save path is
     *   automatically generated. You probably don't want to pass this param.
     *
     * @throws DownloadFailureException
     *   Thrown when a patch cannot be downloaded or saved.
     *
     * @todo Cache downloaded patches.
     */
    public function download($localPathOverride = null)
    {
        // If we've already got the localPath, we can skip the rest.
        if (isset($this->localPath)) {
            return;
        }

        // Generate random (but not cryptographically so) filename if needed.
        // @TODO: /tmp is probably not a great target for Windows users.
        $filename = $localPathOverride;
        if (is_null($filename)) {
            $filename = uniqid("/tmp/") . ".patch";
        }

        // Download file from remote filesystem to that location.
        // The error suppression operator is intentional. Exceptions > warnings.
        $patch_contents = @file_get_contents($this->url);
        if (false === $patch_contents) {
            throw new DownloadFailureException('Could not download patch from ' . $this->url);
        }

        // The error suppression operator is intentional. Exceptions > warnings.
        $bytes_written = @file_put_contents($filename, $patch_contents);
        if (false === $bytes_written) {
            throw new DownloadFailureException('Could not write patch to ' . $filename);
        }

        // If we've made it this far, we've successfully downloaded the patch
        // and saved it locally for later use.
        $this->localPath = $filename;
    }

    /**
     * Validate the patch contents against the SHA-1 hash.
     */
    public function verifyPatch()
    {
        // Callers must download the patch first.
        if (!isset($this->localPath)) {
            throw new \LogicException('Cannot verify patch without downloading it first!');
        }

        // If we can't load the patch, that's going to be a problem.
        // The error suppression operator is intentional. Exceptions > warnings.
        $patch_contents = @file_get_contents($this->localPath);
        if ($patch_contents === false) {
            throw new VerificationFailureException('Could not load patch from ' . $this->localPath);
        }

        // Compute the SHA-1 hash of the patch.
        $patch_hash = sha1($patch_contents);

        if ($this->hash === self::NO_CHECK_HASH) {
            // If we're not verifying against a known hash, store the hash for later
            // so that we can write it to composer.lock for future composer runs.
            // This obviously doesn't prove that the first download was legit, but
            // it can at least guarantee that every composer run is using the same
            // patch.
            $this->hash = $patch_hash;
        } else {
            // Otherwise, we just need to complain if there is a hash mismatch.
            if ($this->hash !== $patch_hash) {
                throw new VerificationFailureException('SHA-1 mismatch for patch downloaded from ' . $this->url);
            }
        }

        // Mark the patch as verified.
        $this->verified = true;
    }

    /**
     * Applies this Patch to a given Package.
     *
     * As part of this process, we'll try to guess which -p value to pass to
     * `patch` and set $this->patch_level accordingly so that the value is
     * saved in composer.lock. This behavior is bypassed if the patch_level
     * is manually set in the patch definition.
     *
     * @param Package $package
     *   The package that this Patch will be applied to.
     */
    public function applyTo(Package $package, $install_path)
    {
        // If we haven't already downloaded and verified the patch file, do so now.
        if (!$this->localPath) {
            $this->download();
            $this->verifyPatch();
        }

        if ($this->patchLevel != self::PATCH_LEVEL_AUTO) {
            // Apply patch with the specified patch level
            return;
        }

        // Guess patch level
        // Apply patch with guessed patch level
    }

    /**
     * Decides if a given hash is valid or not.
     *
     * @param string $hash
     *   The string to validate.
     */
    protected function isValidSha1($hash)
    {
        return (bool)preg_match('/[a-fA-F0-9]{40}/', $hash);
    }

    /**
     * If given the opportunity, clean up local patch files.
     */
    public function __destruct()
    {
        if (file_exists($this->localPath)) {
            unlink($this->localPath);
        }
    }
}
