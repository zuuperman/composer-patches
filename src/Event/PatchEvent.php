<?php

/**
 * @file
 * Dispatch events when patches are applied.
 */

namespace cweagans\Composer\Event;

use Composer\EventDispatcher\Event;
use cweagans\Composer\Patch;

class PatchEvent extends Event {

    /**
     * @var Patch;
     */
    protected $patch;

    /**
     * Constructs a PatchEvent object.
     *
     * @param string $eventName
     *   See constants in PatchEvents for allowed values.
     * @param Patch $patch
     *   The patch that emitted this event.
     */
    public function __construct($eventName, Patch $patch) {
        parent::__construct($eventName);
        $this->patch = $patch;
    }

    /**
     * Returns the PatchOperation that emitted this event.
     *
     * @return Patch
     */
    public function getPatch()
    {
        return $this->patch;
    }

}
