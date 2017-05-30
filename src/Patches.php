<?php

/**
 * @file
 * Contains cweagans\Composer\Patches.
 *
 * This plugin allows Composer users to apply patches to installed dependencies
 * through a variety of methods, including a list of patches in the root
 * composer.json, a separate patches file, and patches aggregated from dependencies
 * installed by Composer.
 */

namespace cweagans\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Util\ProcessExecutor;
use cweagans\Composer\Resolvers\ResolverBase;
use cweagans\Composer\Resolvers\ResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Patches implements PluginInterface, EventSubscriberInterface, Capable
{
   use ConfigurablePlugin;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ProcessExecutor
     */
    protected $executor;

    /**
     * @var PatchCollection
     */
    protected $patchCollection;

    /**
     * @var bool
     */
    protected $patchesResolved;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        // Store a bunch of stuff we'll need later.
        $this->composer = $composer;
        $this->io = $io;
        $this->eventDispatcher = $composer->getEventDispatcher();
        $this->executor = new ProcessExecutor($this->io);
        $this->patchCollection = new PatchCollection();

        $this->configuration = [
            'patching-enabled' => [
                'type' => 'bool',
                'default' => TRUE,
            ],
// @TODO: Replace this with the ability to ignore a particular PatchResolver.
//            'dependency-patching-enabled' => [
//                'type' => 'bool',
//                'default' => TRUE,
//            ],
            'stop-on-patch-failure' => [
                'type' => 'bool',
                'default' => TRUE,
            ],
            'ignore-packages' => [
                'type' => 'list',
                'default' => [],
            ],
        ];
        $this->configure($composer->getPackage()->getExtra(), 'composer-patches');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::PRE_INSTALL_CMD => '',
            ScriptEvents::PRE_UPDATE_CMD => '',
            PackageEvents::PRE_PACKAGE_INSTALL => 'resolvePatches',
            PackageEvents::PRE_PACKAGE_UPDATE => 'resolvePatches',
            PackageEvents::POST_PACKAGE_INSTALL => 'applyPatchOnInstall',
            PackageEvents::POST_PACKAGE_UPDATE => 'applyPatchOnUpdate',
        ];
    }

    /**
     * Return a list of plugin capabilities.
     *
     * @return array
     */
    public function getCapabilities()
    {
        return [
            'cweagans\Composer\Capability\ResolverProvider' => 'cweagans\Composer\Capability\CoreResolverProvider',
        ];
    }

    /**
     * Gather a list of all patch resolvers.
     *
     * @return ResolverBase[]
     *   A list of PatchResolvers to be run.
     *
     * @todo Add config option to disable specific resolvers - list of classes to skip.
     */
    public function getPatchResolvers()
    {
        $resolvers = [];

        $plugin_manager = $this->composer->getPluginManager();
        foreach ($plugin_manager->getPluginCapabilities('cweagans\Composer\Capability\ResolverProvider', ['composer' => $this->composer, 'io' => $this->io]) as $capability) {
            /** @var \cweagans\Composer\Capability\ResolverProvider $capability */
            $newResolvers = $capability->getResolvers();
            if (!is_array($newResolvers)) {
                throw new \UnexpectedValueException('Plugin capability ' . get_class($capability) . ' failed to return an array from getResolvers().');
            }

            foreach ($newResolvers as $resolver) {
                if (!$resolver instanceof ResolverBase) {
                    throw new \UnexpectedValueException('Plugin capability ' . get_class($capability) . ' returned an invalid value.');
                }
            }

            $resolvers = array_merge($resolvers, $newResolvers);
        }

        return $resolvers;
    }

    /**
     * Gather patches that need to be applied to the current set of packages.
     *
     * Note that this work is done unconditionally if this plugin is enabled,
     * even if patching is disabled in any way. The point where patches are
     * applied is where the work will be skipped. It's done this way to ensure
     * that patching can be disabled temporarily in a way that doesn't affect
     * the contents of composer.lock
     *
     * @param PackageEvent $event
     *   The PackageEvent passed by composer to the event listener.
     *
     * @todo Add a composer.lock resolver and use the patches recorded in the
     *       lockfile unless the user specifically wants to update patches.
     */
    public function resolvePatches(PackageEvent $event)
    {
        // No need to resolve patches more than once.
        if ($this->patchesResolved) {
            return;
        }

        /** @var ResolverInterface $resolver */
        foreach ($this->getPatchResolvers() as $resolver) {
            $resolver->resolve($this->patchCollection, $event);
        }

        $this->patchesResolved = TRUE;
    }

    /**
     * Called by Composer after package installation.
     *
     * @param PackageEvent $event
     */
    public function applyPatchOnInstall(PackageEvent $event)
    {
        $this->applyPatchesToPackage($event->getOperation()->getPackage());
    }

    /**
     * Called by Composer after package update.
     *
     * @param PackageEvent $event
     */
    public function applyPatchOnUpdate(PackageEvent $event)
    {
        $this->applyPatchesToPackage($event->getOperation()->getTargetPackage());
    }

    /**
     * Apply patches to a given package.
     *
     * This function requires patches to have been resolved. During normal
     * operation, Composer will make sure that this is done in the correct order.
     *
     * @param Package $package
     *   The package to which patches will be applied.
     */
    public function applyPatchesToPackage(Package $package)
    {
        // Get the list of patches from the PatchCollection.
        $patchesForPackage = $this->patchCollection->getPatchesForPackage($package->getName());

        // If there aren't any patches to apply, we're done here.
        if (empty($patchesForPackage)) {
            $this->io->write('<info>No patches found for package ' . $package->getName() . '.</info>', TRUE, IOInterface::VERBOSE);
            return;
        }

        // Let the user know that we're going to patch this package.
        $this->io->write('  - Applying patches for <info>' . $package->getName() . '</info>.');

        // Patches are tracked in composer.lock, so we'll need to modify the
        // package data before it's saved.
        $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        /** @var Package $localPackage */
        $localPackage = $localRepository->findPackage($package->getName(), $package->getVersion());
        $extra = $localPackage->getExtra();
        $extra['patches'] = array();

        // Get the installation path for the package.
        $install_path = $this->composer
            ->getInstallationManager()
            ->getInstaller($package->getType())
            ->getInstallPath($package);

        // Process each patch object.
        foreach ($patchesForPackage as $patch) {
            $extra['patches'][] = $patch;
        }

        // Set the package's extra data to the modified version.
        $localPackage->setExtra($extra);
    }
}
