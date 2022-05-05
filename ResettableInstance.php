<?php
namespace Neos\Flow\ObjectManagement;

/**
 * Mark a singleton implementation as resettable (will be called after each request when runnijg as a PSR-7 worker)
 */
interface ResettableInstance
{
    public function resetInstance(): void;
}
