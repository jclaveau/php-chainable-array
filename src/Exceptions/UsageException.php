<?php
namespace JClaveau\Exceptions;

/**
 */
class UsageException extends \Exception
{
    /**
     * /
    public function new_($message)
    {
        return new static($message);
    }

    /**
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());

        $this->rewindStackWhile( function($backtrace, $level) {
            // Finds the closest caller
            return  isset($backtrace[ $level ]['class'])
                &&  $backtrace[ $level ]['class'] == __CLASS__;
        }, 0 );
    }

    /**
     */
    public function setStackLocationHere()
    {
        $this->rewindStackWhile( function($backtrace, $level) {
            // Finds the closest caller
            return $level < 2;
        }, 4 );

        return $this;
    }

    /**
     */
    protected function rewindStackWhile(callable $scope_checker, $stack_max_depth=20)
    {
        $backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, $stack_max_depth);
        $i         = 1;
        $caller    = $backtrace[$i];
        while ( $scope_checker( $backtrace, $i ) ) {
            $i++;
            $caller = $backtrace[$i];
            // TODO remove the previous levels of the stack?
        }

        // var_export($backtrace);
        // var_export($caller);

        // TODO How to handle perfectly the missing fields of the backtrace?
        if (isset($caller['file']))
            $this->file = $caller['file'];

        if (isset($caller['line']))
            $this->line = $caller['line'];

        // var_export($this->stack);
    }

    /**/
}
