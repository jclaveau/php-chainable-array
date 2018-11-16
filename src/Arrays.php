<?php
namespace JClaveau\Arrays;

/**
 *
 */
class Arrays
{
    /**
     * Taken from Kohana's Arr class.
     *
     * Tests if an array is associative or not.
     *
     *     // Returns TRUE
     *     Arr::isAssoc(array('username' => 'john.doe'));
     *
     *     // Returns FALSE
     *     Arr::isAssoc('foo', 'bar');
     *
     * @param   array   $array  array to check
     * @return  boolean
     */
    public static function isAssociative(array $array)
    {
        // Keys of the array
        $keys = array_keys($array);

        // If the array keys of the keys match the keys, then the array must
        // not be associative (e.g. the keys array looked like {0:0, 1:1...}).
        return array_keys($keys) !== $keys;
    }

    /**
     * Taken from Kohana's Arr class.
     *
     * Recursively merge two or more arrays. Values in an associative array
     * overwrite previous values with the same key. Values in an indexed array
     * are appended, but only when they do not already exist in the result.
     *
     * Note that this does not work the same as [array_merge_recursive](http://php.net/array_merge_recursive)!
     *
     *     $john = array('name' => 'john', 'children' => array('fred', 'paul', 'sally', 'jane'));
     *     $mary = array('name' => 'mary', 'children' => array('jane'));
     *
     *     // John and Mary are married, merge them together
     *     $john = Arr::merge($john, $mary);
     *
     *     // The output of $john will now be:
     *     array('name' => 'mary', 'children' => array('fred', 'paul', 'sally', 'jane'))
     *
     * @param   array  $array1      initial array
     * @param   array  $array2,...  array to merge
     * @return  array
     */
    public static function merge($array1, $array2)
    {
        if (self::isAssociative($array2))
        {
            foreach ($array2 as $key => $value)
            {
                if (is_array($value)
                    AND isset($array1[$key])
                    AND is_array($array1[$key])
                )
                {
                    $array1[$key] = self::merge($array1[$key], $value);
                }
                else
                {
                    $array1[$key] = $value;
                }
            }
        }
        else
        {
            foreach ($array2 as $value)
            {
                if ( ! in_array($value, $array1, TRUE))
                {
                    $array1[] = $value;
                }
            }
        }

        if (func_num_args() > 2)
        {
            foreach (array_slice(func_get_args(), 2) as $array2)
            {
                if (self::isAssociative($array2))
                {
                    foreach ($array2 as $key => $value)
                    {
                        if (is_array($value)
                            AND isset($array1[$key])
                            AND is_array($array1[$key])
                        )
                        {
                            $array1[$key] = self::merge($array1[$key], $value);
                        }
                        else
                        {
                            $array1[$key] = $value;
                        }
                    }
                }
                else
                {
                    foreach ($array2 as $value)
                    {
                        if ( ! in_array($value, $array1, TRUE))
                        {
                            $array1[] = $value;
                        }
                    }
                }
            }
        }

        return $array1;
    }

    /**
     * Equivalent of array_merge_recursive with more options.
     *
     * @param array         $existing_row
     * @param array         $conflict_row
     * @param callable|null $merge_resolver
     * @param int           $max_depth
     *
     * + If exist only in conflict row => add
     * + If same continue
     * + If different merge as array
     */
    public static function mergeRecursiveCustom(
        $existing_row,
        $conflict_row,
        callable $merge_resolver=null,
        $max_depth=null
    ){
        static::mustBeCountable($existing_row);
        static::mustBeCountable($conflict_row);

        foreach ($conflict_row as $column => $conflict_value) {

            // not existing in first array
            if (!isset($existing_row[$column])) {
                $existing_row[$column] = $conflict_value;
                continue;
            }

            $existing_value = $existing_row[$column];

            // two arrays so we recurse
            if (is_array($existing_value) && is_array($conflict_value)) {

                if ($max_depth === null || $max_depth > 0) {
                    $existing_row[$column] = static::mergeRecursiveCustom(
                        $existing_value,
                        $conflict_value,
                        $merge_resolver,
                        $max_depth - 1
                    );
                    continue;
                }
            }

            if ($merge_resolver) {
                $existing_row[$column] = call_user_func_array(
                    $merge_resolver,
                    [
                        $existing_value,
                        $conflict_value,
                        $column,
                    ]
                );
            }
            else {
                // same resolution as array_merge_recursive
                if (!is_array($existing_value)) {
                    $existing_row[$column] = [$existing_value];
                }

                // We store the new value with their previous ones
                $existing_row[$column][] = $conflict_value;
            }
        }

        return $existing_row;
    }

    /**
     * Merges two rows
     *
     * @param  array $existing_row
     * @param  array $conflict_row
     *
     * @return array
     */
    public static function mergePreservingDistincts(
        $existing_row,
        $conflict_row
    ){
        static::mustBeCountable($existing_row);
        static::mustBeCountable($conflict_row);

        $merge = static::mergeRecursiveCustom(
            $existing_row,
            $conflict_row,
            function ($existing_value, $conflict_value, $column) {

                if ( ! $existing_value instanceof MergeBucket) {
                    $existing_value = MergeBucket::from()->push($existing_value);
                }

                // We store the new value with their previous ones
                if ( ! $conflict_value instanceof MergeBucket) {
                    $conflict_value = MergeBucket::from()->push($conflict_value);
                }

                foreach ($conflict_value->toArray() as $conflict_key => $conflict_entry) {
                    $existing_value->push($conflict_entry);
                }

                return $existing_value;
            },
            0
        );

        return $merge;
    }

    /**
     * This is the cleaning part of self::mergePreservingDistincts()
     *
     * @param  array|Countable   $row
     * @param  array             $options : 'excluded_columns'
     */
    public static function cleanMergeDuplicates($row, array $options=[])
    {
        static::mustBeCountable($row);

        $excluded_columns = isset($options['excluded_columns'])
                          ? $options['excluded_columns']
                          : []
                          ;

        foreach ($row as $column => &$values) {
            if ( ! $values instanceof MergeBucket)
                continue;

            if (in_array($column, $excluded_columns))
                continue;

            $values = Arrays::unique($values);
            if (count($values) == 1)
                $values = $values[0];
        }

        return $row;
    }

    /**
     * This is the cleaning last part of self::mergePreservingDistincts()
     *
     * @param  array|Countable   $row
     * @param  array             $options : 'excluded_columns'
     *
     * @see mergePreservingDistincts()
     * @see cleanMergeDuplicates()
     */
    public static function cleanMergeBuckets($row, array $options=[])
    {
        static::mustBeCountable($row);

        $excluded_columns = isset($options['excluded_columns'])
                          ? $options['excluded_columns']
                          : []
                          ;

        foreach ($row as $column => &$values) {
            if (in_array($column, $excluded_columns))
                continue;

            if ($values instanceof MergeBucket)
                $values = $values->toArray();
        }

        return $row;
    }

    /**
     * Replacement of array_unique, keeping the first key.
     *
     * @param  array|\Traversable $array
     * @return array|\Traversable With unique values
     *
     * @todo   Options to keep another key than the first one?
     */
    public static function unique($array)
    {
        static::mustBeCountable($array);

        $ids = [];
        foreach ($array as $key => $value) {
            if (is_scalar($value)) {
                $id = $value;
            }
            else {
                $id = serialize($value);
            }

            if (isset($ids[ $id ])) {
                unset($array[ $key ]);
                $ids[ $id ][] = $key;
                continue;
            }

            $ids[ $id ] = [$key];
        }

        return $array;
    }

    /**
     */
    public static function keyExists($key, $array)
    {
        static::mustBeTraversable($array);

        if (is_array($array)) {
            return array_key_exists($key, $array);
        }
        elseif ($array instanceof ChainableArray || method_exists($array, 'keyExists')) {
            return $array->keyExists($key);
        }
        else {
            throw new \InvalidArgumentException(
                "keyExists() method missing on :\n". var_export($array, true)
            );
        }

        return $array;
    }

    /**
     * Replacement of array_sum wich throws exceptions instead of skipping
     * bad operands.
     *
     * @param  array|\Traversable $array
     * @return int|double         The sum
     *
     * @todo   Support options like 'strict', 'skip_non_scalars', 'native'
     */
    public static function sum($array)
    {
        static::mustBeCountable($array);

        $sum = 0;
        foreach ($array as $key => &$value) { // &for optimization
            if (is_scalar($value)) {
                $sum += $value;
            }
            elseif (is_null($value)) {
                continue;
            }
            elseif (is_array($value)) {
                throw new \InvalidArgumentException(
                    "Trying to sum an array with '$sum': ".var_export($value, true)
                );
            }
            elseif (is_object($value)) {
                if ( ! method_exists($value, 'toNumber')) {
                    throw new \InvalidArgumentEXception(
                         "Trying to sum a ".get_class($value)." object which cannot be casted as a number. "
                        ."Please add a toNumber() method."
                    );
                }

                $sum += $value->toNumber();
            }
        }

        return $sum;
    }

    /**
     * This method returns a classical mathemartic weighted mean.
     *
     * @todo It would ideally handled by a bridge with this fantastic math
     * lib https://github.com/markrogoyski/math-php/ but we need the support
     * of PHP 7 first.
     *
     * @see https://en.wikipedia.org/wiki/Weighted_arithmetic_mean
     * @see https://github.com/markrogoyski/math-php/
     */
    public static function weightedMean($values, $weights)
    {
        if ($values instanceof ChainableArray)
            $values = $values->toArray();

        if ($weights instanceof ChainableArray)
            $weights = $weights->toArray();

        if ( ! is_array($values))
            $values = [$values];

        if ( ! is_array($weights))
            $weights = [$weights];

        if (count($values) != count($weights)) {
            throw new \InvalidArgumentException(
                "Different number of "
                ." values and weights for weight mean calculation: \n"
                .var_export($values,  true)."\n\n"
                .var_export($weights, true)
            );
        }

        if (!$values)
            return null;

        $weights_sum  = array_sum($weights);
        if (!$weights_sum)
            return 0;

        $weighted_sum = 0;
        foreach ($values as $i => $value) {
            $weighted_sum += $value * $weights[$i];
        }

        return $weighted_sum / $weights_sum;
    }

    /**
     * This is not required anymore with PHP 7.
     *
     * @return bool
     */
    public static function isTraversable($value)
    {
        return $value instanceof \Traversable || is_array($value);
    }

    /**
     * This is not required anymore with PHP 7.
     *
     * @return bool
     */
    public static function isCountable($value)
    {
        return $value instanceof \Countable || is_array($value);
    }

    /**
     */
    public static function mustBeCountable($value)
    {
        if ( ! static::isCountable($value) ) {
            $exception = new \InvalidArgumentException(
                "A value must be Countable instead of: \n"
                .var_export($value, true)
            );

            // The true location of the throw is still available through the backtrace
            $trace_location  = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
            $reflectionClass = new \ReflectionClass( get_class($exception) );

            // file
            if (isset($trace_location['file'])) {
                $reflectionProperty = $reflectionClass->getProperty('file');
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($exception, $trace_location['file']);
            }

            // line
            if (isset($trace_location['line'])) {
                $reflectionProperty = $reflectionClass->getProperty('line');
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($exception, $trace_location['line']);
            }

            throw $exception;
        }
    }

    /**
     */
    public static function mustBeTraversable($value)
    {
        if ( ! static::isTraversable($value) ) {
            $exception = new \InvalidArgumentException(
                "A value must be Traversable instead of: \n"
                .var_export($value, true)
            );

            // The true location of the throw is still available through the backtrace
            $trace_location  = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
            $reflectionClass = new \ReflectionClass( get_class($exception) );

            // file
            if (isset($trace_location['file'])) {
                $reflectionProperty = $reflectionClass->getProperty('file');
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($exception, $trace_location['file']);
            }

            // line
            if (isset($trace_location['line'])) {
                $reflectionProperty = $reflectionClass->getProperty('line');
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($exception, $trace_location['line']);
            }

            throw $exception;
        }
    }

    /**/
}
