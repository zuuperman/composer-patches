<?php

namespace cweagans\Composer\Capability;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\Capability;
use Composer\Plugin\PluginInterface;

abstract class BaseResolverProvider implements Capability
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
     * @var PluginInterface
     */
    protected $plugin;

    /**
     * BaseResolverProvider constructor.
     *
     * Stores values passed by the plugin manager for later use.
     *
     * @param array $ctorargs
     *   An array of args passed by the plugin manager.
     */
    public function __construct($ctorargs) {
        $this->composer = $ctorargs['composer'];
        $this->io = $ctorargs['io'];
        $this->plugin = $ctorargs['plugin'];
    }

    /**
     * {@inheritDoc}
     */
    abstract public function getResolvers();
}
