<?php
namespace JClaveau\Arrays;

/**
 * This Trait gathers methods corresponding to all functions prefixed
 * by "array_" in PHP.
 * The behaviour of some of them is changed to give them more flexibility.
 *
 * Only those I needed are implemented. Others can miss.
 *
 * @see http://php.net/manual/fr/ref.array.php
 */
trait ChainableArray_NativeFunctions_Trait
{
    /**
     * This is an enhanced version of array_column. It preserves index
     * keys of associative arrays.
     *
     * @param $column_names         The columns to keep
     * @param $index_key (optional) The column to use as new index key.
     *                              The current key we be kept if null.
     * @return Helper_Table|$this
     */
    public function columns($column_names, $index_key=null)
    {
        $out = [];
        foreach ($this->data as $key => &$row) {

            if ($index_key)
                $key = $row[$index_key];

            if (is_array($column_names)) {
                $out[$key] = [];
                foreach ($column_names as $column_name) {
                    if (!array_key_exists($column_name, $row)) {
                        self::throwUsageException(
                             "Trying to extract a column from a row which"
                            ." doesn't contain it : '$column_name' \n"
                            .var_export($row, true)
                        );
                    }

                    $out[$key][$column_name] = $row[$column_name];
                }
            }
            else {
                // This avoids issues with isset, array_key_exists and
                // null entries on objects and arrays.
                if ($row instanceof Helper_Table) {
                    $keys = $row->copy()->keys();
                }
                elseif (is_array($row)) {
                    $keys = array_keys($row);
                }
                elseif (is_scalar($row)) {
                    $keys = [];
                }
                else {
                    // todo : handle other classes supporting ArrayAccess?
                    $keys = [];
                }

                if (!in_array($column_names, $keys)) {
                    self::throwUsageException('A row have no index to '
                        .'fill the column: '.$column_names."\n"
                        .$key.' => '.var_export($row, true));
                }

                $out[$key] = $row[$column_names];
            }
        }

        return $this->returnConstant($out);
    }

    /**
     * Rename of column as multiple columns can be retrieved
     *
     * @deprecated Use columns() instead
     */
    public function column($column_names, $index_key=null)
    {
        return $this->columns($column_names, $index_key);
    }

    /**
     * Equivalent of array_sum.
     *
     * @return array The sum.
     */
    public function sum()
    {
        return array_sum($this->data);
    }

    /**
     * Equivalent of min.
     *
     * @param  $default_value To use if the array is empty
     * @return array The min.
     */
    public function min($default_value=null)
    {
        if ($this->isEmpty() && $default_value !== null)
            return $default_value;

        try {
            return min($this->data);
        }
        catch (\Exception $e) {
            $message = $e->getMessage();
            if ($e->getMessage() == 'min(): Array must contain at least one element')
                $message .= ' or you can set a default value as parameter';

            $this->throwUsageException($message);
        }
    }

    /**
     * Equivalent of max.
     *
     * @param  $default_value To use if the array is empty
     * @return array The max.
     */
    public function max($default_value=null)
    {
        if ($this->isEmpty() && $default_value !== null)
            return $default_value;

        try {
            return max($this->data);
        }
        catch (\Exception $e) {
            $message = $e->getMessage();
            if ($e->getMessage() == 'max(): Array must contain at least one element')
                $message .= ' or you can set a default value as parameter';

            $this->throwUsageException($message);
        }
    }

    /**
     * Equivalent of count().
     *
     * @return int The number of rows.
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Computes the average on a unidimensional array of numbers
     *
     * @return int The average
     */
    public function average()
    {
        return array_sum($this->data) / count($this->data);
    }

    /**
     * Equivalent of array_values.
     *
     * @return Helper_Table|$this
     */
    public function values()
    {
        $out = array_values($this->data);
        return $this->returnConstant($out);
    }

    /**
     * Equivalent of array_filter as if used with the flag
     * ARRAY_FILTER_USE_BOTH.
     *
     * @param  callable|array $callback The filter logic with $value and $key
     *                            as parameters.
     *
     * @return Helper_Table $this or a new Helper_Table.
     */
    public function filter($callback=null)
    {
        if ($callback) {

            if (is_array($callback)) {
                $callback = new \JClaveau\LogicalFilter\LogicalFilter($callback);
            }

            if (!is_callable($callback)) {
                $this->throwUsageException(
                    "\$callback must be a logical filter description array or a callable"
                    ." instead of "
                    .var_export($callback, true)
                );
            }


            // Flags are supported since 5.6
            if (PHP_VERSION_ID >= 50600) {
                $out = array_filter($this->data, $callback,
                    ARRAY_FILTER_USE_BOTH);
            }
            else {
                $out = $this->data;
                foreach ($out as $key => $value) {
                    if (!$callback($value, $key))
                        unset( $out[$key] );
                }
            }
        }
        else {
            $out = array_filter($this->data);
        }

        return $this->returnConstant($out);
    }

    /**
     * Equivalent of array_filter but filtering on keys
     *
     * @see self::filter()
     * @deprecated
     *
     * @return Helper_Table $this or a new Helper_Table.
     */
    public function filterKey(callable $callback=null)
    {
        throw new \ErrorException('This method is replaced by '
            . __TRAIT__ . '::filtr()');
    }

    /**
     * Remove doesn't exist but look a lot like filter so I place it here.
     *
     * @param * $item_to_remove
     *
     * @return Helper_Table $this or a new Helper_Table.
     */
    public function remove($item_to_remove)
    {
        $out = [];
        foreach ($this->data as $i => $data_item) {

            if ($data_item == $item_to_remove)
                continue;

            $out[$i] = $data_item;
        }

        return $this->returnConstant($out);
    }

    /**
     * Equivalent of array_intersect_key()
     *
     * @return Helper_Table $this or a new Helper_Table.
     */
    public function intersectKey($intersect_with)
    {
        if (!$this->argumentIsArrayOrArrayObject($intersect_with))
            self::throwUsageException("First argument must be an array or a Helper_Table.");

        if ($intersect_with instanceof Helper_Table)
            $intersect_with = $intersect_with->getArray();

        $out = array_intersect_key($this->data, $intersect_with);

        return $this->returnConstant($out);
    }

    /**
     * Equivalent of array_flip()
     *
     * @return Helper_Table $this or a new Helper_Table.
     */
    public function flip()
    {
        $out = array_flip($this->data);
        return $this->returnConstant($out);
    }

    /**
     * Equivalent of array_shift()
     *
     * @return The extracted first value
     */
    public function shift()
    {
        return array_shift($this->data);
    }

    /**
     * Equivalent of array_unshift()
     *
     * @return Helper_Table $this.
     */
    public function unshift()
    {
        $data = $this->data;
        $arguments = Arr::merge( [&$data], func_get_args() );

        call_user_func_array('array_unshift', $arguments);
        return $this->returnConstant($data);
    }

    /**
     * Equivalent of array_push()
     *
     * @return Helper_Table $this or a new Helper_Table.
     */
    public function push()
    {
        $data = $this->data;
        $arguments = Arr::merge( [&$data], func_get_args() );

        call_user_func_array('array_push', $arguments);
        return $this->returnConstant($data);
    }

    /**
     * Equivalent of array_unique()
     *
     * @return Helper_Table $this or a new Helper_Table.
     */
    public function unique($flags=SORT_STRING)
    {
        $out = array_unique($this->data, $flags);
        return $this->returnConstant($out);
    }

    /**
     * Equivalent of array_diff() but supporting associative arrays
     * comparaison.
     *
     * Contrary to array_diff, only one iterable can be given as argument.
     *
     * Supports also :
     * + Keys comparison
     * + Strict comparison
     *
     * @param Iterable $compare_with      Values to compare with.
     * @param bool     $check_keys        Compare also keys of items before
     *                                    considering them equals.
     * @param bool     $strict_comparison Perform strict comparaisons
     *                                    between items and keys.
     *
     * @see http://php.net/manual/en/function.array-diff.php
     *
     * @return Helper_Table $this or a new Helper_Table.
     */
    public function diff($compare_with, $check_keys=false, $strict_comparison=false)
    {
        if (!$this->argumentIsArrayOrArrayObject($compare_with))
            self::throwUsageException("First argument must be an iterable");

        $kept_values = $this->data;

        foreach ($kept_values as $kept_key => $kept_value) {
            foreach ($compare_with as $compared_key => $compared_value) {

                $is_equal = false;

                if ($check_keys) {

                    if ($strict_comparison) {
                        if (    $kept_key   === $compared_key
                            &&  $kept_value === $compared_value ) {
                            $is_equal = true;
                        }
                    }
                    else {
                        if (    $kept_key   == $compared_key
                            &&  $kept_value == $compared_value ) {
                            $is_equal = true;
                        }
                    }
                }
                else {
                    if ($strict_comparison) {
                        if ($kept_value === $compared_value) {
                            $is_equal = true;
                        }
                    }
                    else {
                        if ($kept_value == $compared_value) {
                            $is_equal = true;
                        }
                    }
                }

                if ($is_equal) {
                    // Debug::dumpJson($kept_key, !true);
                    unset($kept_values[ $kept_key ]);
                    break;
                }
            }
        }

        // Debug::dumpJson($kept_values, true);

        return $this->returnConstant($kept_values);
    }

    /**
     * Equivalent of array_pop()
     *
     * @return The extracted last value
     */
    public function pop()
    {
        return array_pop($this->data);
    }

    /**
     * Equivalent of array_fill_keys.
     *
     * @see    http://php.net/manual/en/function.array-fill-keys.php
     *
     * @return Helper_Table The filled array.
     */
    public function fillKeys($keys, $value)
    {
        if (!$this->argumentIsArrayOrArrayObject($keys))
            self::throwUsageException("First argument must be an array or a Helper_Table.");

        if ($keys instanceof Helper_Table)
            $keys = $keys->getArray();

        $out = array_fill_keys($keys, $value);
        return $this->returnConstant($out);
    }

    /**
     * Equivalent of array_fill.
     *
     * @see    http://php.net/manual/en/function.array-fill.php
     *
     * @return Helper_Table The filled array.
     */
    public function fill($start, $number, $value, $interval=1)
    {
        $out = [];
        while ($number >= 0) {
            $out[$start] = $value;
            $start += $interval;
            $number--;
        }

        return $this->returnConstant($out);
    }

    /**
     * This is a fully permissive equivalent of array_fill.
     *
     * @param  scalar    $zero_key       Your starting point reference
     * @param  scalar    $end_key        The last key of the resulting array
     * @param  callable  $step_generator The callback defining the current step
     *                                   based on the previous one.
     * @param  int       $max_interations_count A limit to avoid inifinite loops
     *                                   set to 1000 by default.
     *
     * @see    http://php.net/manual/en/function.array-fill.php
     *
     * @return Helper_Table The filled array.
     */
    public function fillWithSeries(
        $zero_key,
        $end_key,
        callable $step_generator,
        $max_interations_count=1000
    ){
        if ($max_interations_count < 0)
            throw new \InvalidArgumentException("$maximum_interations_count must be positive");

        $out                 = [];
        $previous_step_value = null;
        $current_step_key    = $zero_key;
        $iterations_count    = 0;

        while ($iterations_count <= $max_interations_count) {
            $current_step_value = call_user_func_array( $step_generator, [
                &$current_step_key,   // param by reference
                $previous_step_value,
                $current_step_key,   // not passed by ref
                $out
            ]);

            if ($current_step_key === null) {
                $out[] = $current_step_value;
            }
            elseif (!is_int($current_step_key)) {
                // Set the local as en_US tu have floats formatted with
                // "." as separator
                // TODO : could it be useful for dates to?
                $current_locale = setlocale(LC_NUMERIC, 'en_US');
                $out[(string) $current_step_key] = $current_step_value;
                setlocale(LC_NUMERIC, $current_locale);
            }
            else {
                $out[$current_step_key] = $current_step_value;
            }

            if (is_float($current_step_key) || is_float($end_key)) {
                // float comparison can lead complex behaviour
                // https://stackoverflow.com/questions/3148937/compare-floats-in-php

                if ((string) $current_step_key == (string) $end_key) {
                    break;
                }
            }
            elseif ($current_step_key == $end_key) {
                break;
            }

            $previous_step_value = $current_step_value;
            $iterations_count++;
        }

        return $this->returnConstant($out);
    }

    /**
     * Equivalent of array_keys.
     *
     * @see    http://php.net/manual/fr/function.array-keys.php
     *
     * @return array The keys.
     */
    public function keys($search_value=null, $strict=false)
    {
        if ($search_value)
            return array_keys($this->data, $search_value, $strict);
        else
            return array_keys($this->data);
    }

    /**
     * Equivalent of implode.
     * NB : "explode()" should not be implemented here but into a String
     *      class if you really want it.
     *
     * @see    http://php.net/manual/fr/function.implode.php
     *
     * @return string The joined values
     */
    public function implode($glue)
    {
        return implode($glue, $this->data);
    }

    /**
     * Equivalent of var_export().
     *
     * @see    http://php.net/manual/fr/function.var-export.php
     * @param  bool $return Should we print it or return it?
     *                      Its default value is the opposit of the PHP
     *                      one as it's more often used this way.
     *
     * @return string The array written in PHP code.
     */
    public function export($return=true)
    {
        return var_export($this->data, $return);
    }

    /**
     * Equivalent of print_r().
     *
     * @see    http://php.net/manual/fr/function.print-r.php
     *
     * @param  bool $return Should we print it or return it?
     * @return string The printed array
     */
    public function print_($return=false)
    {
        if (!$return) {

            echo '<pre>';
            print_r($this->data);
            echo '</pre>';

            return $this;
        } else {
            return print_r($this->data, true);
        }
    }

    /**
     * Equivalent of key().
     *
     * @see http://php.net/manual/fr/function.key.php
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * Equivalent of current().
     *
     * @see  http://php.net/manual/fr/function.current.php
     * @todo Exception if the pointer is above the length?
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     * Equivalent of next().
     *
     * @see http://php.net/manual/fr/function.next.php
     */
    public function next()
    {
        next($this->data);
        return $this;
    }

    /**
     * Equivalent of prev().
     *
     * @see http://php.net/manual/fr/function.prev.php
     */
    public function prev()
    {
        prev($this->data);
        return $this;
    }

    /**
     * Equivalent of uasort().
     *
     * @see http://php.net/manual/fr/function.prev.php
     */
    public function usort(callable $callback=null)
    {
        $data = $this->data;

        if ($callback === null) {
            $callback = function($a, $b) {
                if ($a == $b)
                    return 0;

                return $a > $b ? -1 : 1;
            };
        }

        $arguments = Arr::merge( [&$data], [$callback] );

        if ( ! call_user_func_array('uasort', $arguments) )
            throw new \ErrorException('Unable to apply usort');

        return $this->returnConstant($data);
    }

    /**
     * Equivalent of uksort().
     *
     * @see http://php.net/manual/fr/function.uksort.php
     */
    public function uksort(callable $callback=null)
    {
        $data = $this->data;

        if ($callback === null) {
            $callback = function($a, $b) {
                return $a >= $b;
            };
        }

        $arguments = Arr::merge( [&$data], [$callback] );

        if ( ! call_user_func_array('uksort', $arguments) )
            throw new \ErrorException('Unable to apply uksort');

        return $this->returnConstant($data);
    }

    /**
     * Equivalent of array_intersect()

     * @param Array|Helper_Table $intersect_with
     * @return Helper_Table $this or a new Helper_Table.
     */
    public function intersect($intersect_with)
    {
        if (!$this->argumentIsArrayOrArrayObject($intersect_with)) {
            $this->throwUsageException("First argument must be an array or a Helper_Table.");
        }

        if ($intersect_with instanceof Helper_Table) {
            $intersect_with = $intersect_with->getArray();
        }

        return $this->returnConstant(array_intersect($this->data, $intersect_with));
    }
}
