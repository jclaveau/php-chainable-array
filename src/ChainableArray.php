<?php
namespace JClaveau\Arrays;

/**
 * This class is meant to provide an oriented object way to use arrays
 * in PHP.
 *
 * This file contains only the essential API of the class. The rest is
 * gathered by feature in traits:
 * + One for the ArrayAcess interface
 * + One for the native functions of PHP.
 * + One for functions that do ot exist in PHP.
 *
 *
 * @todo :
 * + recursive
 */
class ChainableArray implements \ArrayAccess, \IteratorAggregate, \JsonSerializable, \Countable
{
    use ChainableArray_ArrayAccess_Trait;
    use ChainableArray_NativeFunctions_Trait;
    use ChainableArray_Utils_Trait;
    use ChainableArray_Wip_Trait;

    protected $data;
    protected $isConstant;

    /**
     */
    public static function from(array $data=[], $isConstant=false)
    {
        return new static($data, $isConstant);
    }

    /**
     */
    public function __construct(array $data=[], $isConstant=false)
    {
        $this->data       = $data;
        $this->isConstant = $isConstant;
    }

    /**
     * Getter of the array behid the helper classe.
     *
     * @return array The data of this array
     */
    public function getArray()
    {
        return $this->data;
    }

    /**
     * Deprecated alias of getArray()
     *
     * @deprecated
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @see self::getData()
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Changes the "constant" state of this instance. If it is "constant",
     * any modification done by one of its methods will return a copy of
     * the current instance instead of modifying it.
     *
     * @param bool $state Whether to enable or not the constant state.
     *
     * @return ChainableArray $this
     */
    public function setConstant($state=true)
    {
        $this->isConstant = $state;
        return $this;
    }

    /**
     * Depending on the isConstant property of the instance the data
     * will be impacted by the methods like mergeWith or not.
     *
     * @param array $out The modified array to return
     *
     * @return ChainableArray $this or a new instance having $out as data.
     */
    protected function returnConstant(array $out)
    {
        if ($this->isConstant) {
            return new static($out);
        }
        else {
            $this->data = $out;
            return $this;
        }
    }

    /**
     * @deprecated
     */
    public function clone_()
    {
        return $this->copy();
    }

    /**
     * clone the current object
     */
    public function copy()
    {
        return clone $this;
    }

    /**
     * Make foreach() possible on this object.
     * This doesn't look to work well on every PHP version but is tested
     * on 5.6.25.
     * We use a generator here to support foreach with references.
     * @see http://stackoverflow.com/questions/29798586/php-an-iterator-cannot-be-used-with-foreach-by-reference#29798888
     */
    public function &getIterator()
    {
        foreach ($this->data as $position => &$row) {
            yield $position => $row;
        }
    }

    /**
     * This method is required to have the good value while we want to
     * json_encode an instance of this class.
     *
     * @see http://stackoverflow.com/questions/4697656/using-json-encode-on-objects-in-php-regardless-of-scope
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * This method is required by the Countable interface.
     *
     * @see https://secure.php.net/manual/en/class.countable.php
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Checks if an argument is an array or a ChainableArray
     */
    private function argumentIsArrayOrArrayObject( $argument )
    {
        return is_array($argument)
            || $argument instanceof ChainableArray;
    }

    /**
     * Throws an exception that will have, as file and line, the position
     * where the public api is called.
     */
    private static function throwUsageException($message)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
        $i      = 1;
        $caller = $backtrace[$i];
        while (     isset($backtrace[$i]['class'])
                &&  $backtrace[$i]['class'] == __CLASS__ ) {
            $caller = $backtrace[$i];
            $i++;
        }

        $exception = new \ErrorException($message);

        $reflectionClass = new \ReflectionClass( get_class($exception) );

        //file
        $reflectionProperty = $reflectionClass->getProperty('file');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($exception, $caller['file']);

        // line
        $reflectionProperty = $reflectionClass->getProperty('line');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($exception, $caller['line']);

        throw $exception;
    }

    /**/
}
