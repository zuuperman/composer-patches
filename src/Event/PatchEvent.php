<?php

/**
 * @file
 * Dispatch events when patches are applied.
 */

namespace cweagans\Composer\Event;

use Composer\EventDispatcher\Event;
use cweagans\Composer\Operation\PatchOperation;

class PatchEvent extends Event {

    /**
     * @var PatchOperation;
     */
    protected $operation;

    /**
     * Constructs a PatchEvent object.
     *
     * @param string $eventName
     *   See constants in PatchEvents for allowed values.
     * @param PatchOperation $operation
     *   The operation that emitted this event.
     */
    public function __construct($eventName, PatchOperation $operation) {
        parent::__construct($eventName);
        $this->operation = $operation;
    }

    /**
     * Returns the PatchOperation that emitted this event.
     *
     * @return PatchOperation
     */
    public function getOperation()
    {
        return $this->operation;
    }

}
