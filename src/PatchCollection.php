<?php

namespace cweagans\Composer;

use cweagans\Composer\Operation\PatchOperation;

class PatchCollection
{
    /**
     * @var PatchOperation[]
     */
    protected $patchOperations;

    /**
     * Add a PatchOperation to the list.
     *
     * @param PatchOperation $operation
     */
    public function addPatch(PatchOperation $operation)
    {
        $this->patchOperations[] = $operation;
    }

    /**
     * Return all known patch operations, root first and then dependencies.
     *
     * @return \Generator
     */
    public function getPatches($type = 'all')
    {
        switch ($type) {
            case 'root':
                $operations = array_filter($this->patchOperations, function(PatchOperation $operation) {
                    return ($operation->getPatchType() === PatchOperation::TYPE_ROOT_PATCH);
                });
                break;
            case 'dependency':
                $operations = array_filter($this->patchOperations, function(PatchOperation $operation) {
                    return ($operation->getPatchType() === PatchOperation::TYPE_DEPENDENCY_PATCH);
                });
                break;
            case 'all':
            default:
                $operations = $this->patchOperations;
        }

        foreach ($operations as $operation) {
            yield $operation;
        }
    }
}
