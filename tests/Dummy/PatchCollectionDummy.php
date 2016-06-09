<?php

namespace cweagans\Composer\Tests\Dummy;

use cweagans\Composer\Operation\PatchOperation;
use cweagans\Composer\PatchCollection;

class PatchCollectionDummy extends PatchCollection
{
    /**
     * Get the number of patches in the collection of a given type.
     *
     * @param string $type
     * @return int
     */
    public function getPatchCount($type = 'all') {
        switch ($type) {
            case 'all':
                return count($this->patchOperations);
            case PatchOperation::TYPE_ROOT_PATCH:
                return count(array_filter($this->patchOperations, function(PatchOperation $operation) {
                    return ($operation->getPatchType() === PatchOperation::TYPE_ROOT_PATCH);
                }));
            case PatchOperation::TYPE_DEPENDENCY_PATCH:
                return count(array_filter($this->patchOperations, function(PatchOperation $operation) {
                    return ($operation->getPatchType() === PatchOperation::TYPE_DEPENDENCY_PATCH);
                }));
        }
    }
}
