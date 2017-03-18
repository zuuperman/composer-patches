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
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Util\ProcessExecutor;
use cweagans\Composer\Resolvers\PatchesFile;
use cweagans\Composer\Resolvers\ResolverInterface;
use cweagans\Composer\Resolvers\RootComposer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Patches implements PluginInterface, EventSubscriberInterface
{

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
     * @var array
     */
    protected $config;

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

        // Set up the plugin configuration.
        $this->configure();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
//            ScriptEvents::PRE_INSTALL_CMD => '',
//            ScriptEvents::PRE_UPDATE_CMD => '',
            PackageEvents::PRE_PACKAGE_INSTALL => 'resolvePatches',
            PackageEvents::PRE_PACKAGE_UPDATE => 'resolvePatches',
            PackageEvents::POST_PACKAGE_INSTALL => 'applyPatchOnInstall',
            PackageEvents::POST_PACKAGE_UPDATE => 'applyPatchOnUpdate',
        ];
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
     * @todo Allow end users to define new resolvers and add them to the list.
     *       This can be done by providing a plugin capability and an interface
     *       to get more resolvers. This plugin can provide a reference implementation
     *       as an example to follow (this plugin would provide it's own Resolvers via
     *       a Resolvers provider).
     * @todo Add a composer.lock resolver and use the patches recorded in the
     *       lockfile unless the user specifically wants to update patches.
     */
    public function resolvePatches(PackageEvent $event)
    {
        // No need to resolve patches more than once.
        if ($this->patchesResolved) {
            return;
        }

        // See note in docblock about converting this to a Composer plugin capability.
        $resolvers = [
            new RootComposer($this->composer, $event->getOperations()),
            new PatchesFile($this->composer, $event->getOperations()),
        ];

        /** @var ResolverInterface $resolver */
        foreach ($resolvers as $resolver) {
            if ($resolver->isEnabled()) {
                $this->io->write('  - <info>' . $resolver->getMessage() . '</info>');
                $resolver->resolve($this->patchCollection);
            }
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

    /**
     * Configure the patches plugin.
     *
     * Configuration depends on (in order of increasing priority):
     *   - Default values (which are defined in this method)
     *   - Values in composer.json
     *   - Values of environment variables (if set)
     *
     * As an example, environment variables will override whatever you've set
     * in composer.json.
     */
    protected function configure()
    {
        // Set default values for each of the configuration options.
        $config = [
            'patching-enabled' => TRUE,
            'dependency-patching-enabled' => TRUE,
            'stop-on-patch-failure' => TRUE,
            'ignore-packages' => [],
        ];

        // Grab the configuration from composer.json and override defaults as
        // appropriate & tell the user if they've done something unexpected.
        $extra = $this->composer->getPackage()->getExtra();
        if (isset($extra['patches-config'])) {
            foreach ($extra['patches-config'] as $option => $value) {
                if (array_key_exists($option, $config)) {
                    $config[$option] = $value;
                } else {
                    throw new \InvalidArgumentException("Option $option is not a valid composer-patches option.");
                }
            }
        }

        // Environment variables have to be handled manually because everything
        // in a shell is a string, and we don't necessarily want string values
        // for everything.
        if (getenv('COMPOSER_PATCHES_PATCHING_ENABLED') !== FALSE) {
            $config['patching-enabled'] = $this->castEnvvarToBool(getenv('COMPOSER_PATCHES_PATCHING_ENABLED'), $config['patching-enabled']);
        }
        if (getenv('COMPOSER_PATCHES_DEPENDENCY_PATCHING_ENABLED') !== FALSE) {
            $config['dependency-patching-enabled'] = $this->castEnvvarToBool(getenv('COMPOSER_PATCHES_DEPENDENCY_PATCHING_ENABLED'), $config['dependency-patching-enabled']);
        }
        if (getenv('COMPOSER_PATCHES_STOP_ON_PATCH_FAILURE') !== FALSE) {
            $config['stop-on-patch-failure'] = $this->castEnvvarToBool(getenv('COMPOSER_PATCHES_STOP_ON_PATCH_FAILURE'), $config['stop-on-patch-failure']);
        }
        if (getenv('COMPOSER_PATCHES_IGNORE_PACKAGES') !== FALSE) {
            $config['ignore-packages'] = $this->castEnvvarToArray(getenv('COMPOSER_PATCHES_IGNORE_PACKAGES'), $config['ignore-packages']);
        }

        // Finally, save the config values.
        $this->config = $config;
    }

    /**
     * Get a boolean value from the environment.
     *
     * @param string $value
     *   The value retrieved from the environment.
     * @param bool $default
     *   The default value to use if we can't figure out what the user wants.
     *
     * @return bool
     */
    public function castEnvvarToBool($value, $default)
    {
        // Everything is strtolower()'d because that cuts the number of cases
        // to look for in half.
        $value = trim(strtolower($value));

        // If it looks false-y, return FALSE.
        if ($value == 'false' || $value == '0' || $value == 'no') {
            return FALSE;
        }

        // If it looks truth-y, return TRUE.
        if ($value == 'true' || $value == '1' || $value == 'yes') {
            return TRUE;
        }

        // Otherwise, just return the default value that we were given. Ain't
        // nobody got time to look for a million different ways of saying yes
        // or no.
        return $default;
    }

    /**
     * Get an array from the environment.
     *
     * @param string $value
     *   The value retrieved from the environment.
     * @param array $default
     *   The default value to use if we can't figure out what the user wants.
     *
     * @return array
     */
    public function castEnvvarToArray($value, $default)
    {
        // Trim any extra whitespace and then split the string on commas.
        $value = explode(',', trim($value));

        // Strip any empty values.
        $value = array_filter($value);

        // If we didn't get anything from the supplied value, better to just use the default.
        if (empty($value)) {
            return $default;
        }

        // Return the array.
        return $value;
    }

    /**
     * Retrieve the value of a specific configured value.
     *
     * @param $name
     *   The name of the configuration key to retrieve.
     *
     * @return mixed
     */
    public function getConfigValue($name)
    {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }

        throw new \InvalidArgumentException("Config key $name does not exist.");
    }
}
