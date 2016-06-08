<?php

/**
 * @file
 * Contains cweagans\Composer\Patch.
 */

namespace cweagans\Composer;

class Patch
{
    /**
     * PATCH_LEVEL_AUTO will cause composer-patches to attempt to auto-apply the
     * patch.
     *
     * @var string
     */
    const PATCH_LEVEL_AUTO = 'auto';

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
    protected $hash;

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
     * Patch constructor.
     *
     * @param $description
     * @param $url
     * @param $hash
     * @param $patchLevel
     */
    public function __construct($description, $url, $hash, $patchLevel = NULL)
    {
        $this->description = $description;
        $this->url = $url;

        if ($this->isValidSha1($hash)) {
            $this->hash = $hash;
        }
        else {
            throw new \InvalidArgumentException('Invalid SHA-1 hash supplied for patch with url ' . $url);
        }

        if (isset($patchLevel)) {
            $this->patchLevel = $patchLevel;
        }
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
     * Decides if a given hash is valid or not.
     *
     * @param string $hash
     *   The string to validate.
     */
    protected function isValidSha1($hash)
    {
        return (bool)preg_match('/[a-fA-F0-9]{40}/', $hash);
    }

}
