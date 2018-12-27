<?php
namespace JClaveau\Arrays;

/**
 * This class represents a bucket storing values.
 * It's used during the merge process.
 */
class MergeBucket extends ChainableArray
{
    /**
     */
    public function reduceIfUnique()
    {
        if ($this->unique()->count() == 1)
            return reset( $this->data );
    }
    /**/
}
