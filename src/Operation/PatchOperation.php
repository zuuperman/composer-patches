<?php

/**
 * @file
 * Contains cweagans\Composer\Operation\PatchOperation.
 */

namespace cweagans\Composer\Operation;

use Composer\DependencyResolver\Operation\SolverOperation;
use Composer\Package\PackageInterface;
use cweagans\Composer\Patch;

class PatchOperation extends SolverOperation
{
    /**
     * Denotes a patch found in the root package.
     *
     * @var string
     */
    const TYPE_ROOT_PATCH = 'root';

    /**
     * Denotes a patch found in a dependency package.
     */
    const TYPE_DEPENDENCY_PATCH = 'dependency';

    /**
     * @var Patch
     */
    protected $patch;

    /**
     * @var \Composer\Package\PackageInterface
     */
    protected $package;

    /**
     * @var string
     */
    protected $type;

    /**
     * Initializes a PatchOperation.
     *
     * @param PackageInterface $package
     *   The package to apply a patch to.
     * @param Patch $patch
     *   The patch to apply to the package.
     * @param string $type
     *   The type of patch operation this is - either 'root' or 'dependency'.
     * @param string $reason
     *   The reason for applying the patch.
     */
    public function __construct(PackageInterface $package, Patch $patch, $type, $reason = null)
    {
        parent::__construct($reason);

        $this->package = $package;
        $this->patch = $patch;

        if ($type !== self::TYPE_ROOT_PATCH && $type !== self::TYPE_DEPENDENCY_PATCH) {
            throw new \InvalidArgumentException('Invalid patch type specified: ' . $type);
        }
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getJobType()
    {
        return 'patch';
    }

    /**
     * Get the package to patch.
     *
     * @return PackageInterface
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Get the patch to apply.
     *
     * @return Patch
     */
    public function getPatch()
    {
        return $this->patch;
    }

    /**
     * Get the patch type.
     *
     * @return string
     */
    public function getPatchType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return 'Patching ' . $this->package->getName() . ': ' . $this->patch->getDescription() . ' (' .
          $this->patch->getUrl() . ').';
    }
}
