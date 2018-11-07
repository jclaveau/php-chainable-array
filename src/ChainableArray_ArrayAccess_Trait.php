<?php
namespace JClaveau\Arrays;

/**
 * This Trait gathers methods related to the ArrayAccess interface for
 * the Table Helper.
 *
 * @see http://php.net/manual/en/class.arrayaccess.php
 */
trait ChainableArray_ArrayAccess_Trait
{
    // protected $defaultRowGenerator;

    /**
     * ArrayAccess interface
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        }
        else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * ArrayAccess interface
     */
    public function offsetExists($offset)
    {
        try {
            return isset($this->data[$offset]);
        }
        catch (\Exception $e) {
            if (property_exists($this, 'defaultRowGenerator')) {
                return true;
            }

            $this->throwUsageException(
                $e->getMessage().': '.var_export($offset, true)
            );
        }
    }

    /**
     * ArrayAccess interface
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * ArrayAccess interface. Method called if isset() or empty() is called.
     *
     * /!\ We need the reference here to have the line below working
     *      $this['key']['sub_key'] = 'lalala'
     *
     * @throws \Exception The same as for a classical array with
     *                         "Undefined index" having the good trace end.
     */
    public function &offsetGet($offset)
    {
        try {
            if ( ! array_key_exists($offset, $this->data)
                && $this->hasDefaultRowValue()
            ) {
                $this->data[$offset] = $this->generateDefaultRow($offset);
            }

            // This line simply triggers the exception. It's odd but wanted.
            // If we do not do that, it could create an null entry in the array
            // instead of throw "Undefined index".
            $this->data[$offset];
            // This one returns the expected value as reference for foreach
            // support but wouldn't throw exception if it doesn't exist
            $returnValue = &$this->data[$offset];
            return $returnValue;
        }
        catch (\Exception $e) {
            // here we simply move the Exception location at the one
            // of the caller as the isset() method is called at its
            // location.

            // The true location of the throw is still available through
            // $e->getTrace()
            $trace_location  = $e->getTrace()[1];
            $reflectionClass = new \ReflectionClass( get_class($e) );

            //file
            $reflectionProperty = $reflectionClass->getProperty('file');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($e, $trace_location['file']);

            // line
            $reflectionProperty = $reflectionClass->getProperty('line');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($e, $trace_location['line']);

            throw $e;
        }
    }

    /**
     * Defines a default row or a callback to generate it.
     *
     * @param  mixed
     * @return $this
     */
    public function setDefaultRow($row)
    {
        unset($this->defaultRowGenerator);
        $this->defaultRow = $row;
        return $this;
    }

    /**
     * Defines a default row or a callback to generate it.
     *
     * @param  mixed
     * @return $this
     */
    public function setDefaultRowGenerator(callable $row_generator)
    {
        unset($this->defaultRow);
        $this->defaultRowGenerator = $row_generator;
        return $this;
    }

    /**
     * Undefines the default row or a callback to generate it.
     *
     * @return $this
     */
    public function unsetDefaultRow()
    {
        unset($this->defaultRow);
        unset($this->defaultRowGenerator);
        return $this;
    }

    /**
     * Checks if a default row generator exists.
     *
     * @return bool
     */
    public function hasDefaultRowValue()
    {
        return property_exists($this, 'defaultRow') 
            || property_exists($this, 'defaultRowGenerator');
    }

    /**
     * Undefines the default row or a callback to generate it.
     *
     * @return $this
     */
    protected function generateDefaultRow($offset)
    {
        if (property_exists($this, 'defaultRow'))
            return $this->defaultRow;
        elseif (property_exists($this, 'defaultRowGenerator'))
            return call_user_func($this->defaultRowGenerator, $offset);
    }

    /**/
}
