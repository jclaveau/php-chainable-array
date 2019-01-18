<?php
namespace JClaveau\Arrays;

use Traversable;

/**
 * Functions that deal with merging processes
 */
trait Arrays_Merge_Trait
{

    /**
     * Merges two rows by replacing their column values by MergeBuckets
     * containing their values.
     *
     * @param  array  $existing_row
     * @param  array  $conflict_row
     * @param  scalar $key
     *
     * @return array
     */
    public static function mergeInColumnBuckets(
        $existing_row,
        $conflict_row,
        $existing_key=null,
        $conflict_key=null
    ) {
        static::mustBeCountable($existing_row);
        static::mustBeCountable($conflict_row);
        
        $merged_row = [];
        foreach ($existing_row as $existing_column => $existing_value) {
            if ($existing_value instanceof MergeBucket) {
                $merged_row[ $existing_column ] = $existing_value;
            }
            else {
                if (isset($existing_key)) {
                    $merged_row[ $existing_column ] = MergeBucket::from([
                        $existing_key => $existing_value
                    ]);
                }
                else {
                    $merged_row[ $existing_column ] = MergeBucket::from([
                        $existing_value
                    ]);
                }
            }
        }
        
        foreach ($conflict_row as $conflict_column => $conflict_value) {
            if (! isset($merged_row[ $conflict_column ])) {
                $merged_row[ $conflict_column ] = new MergeBucket;
            }
            
            if ($conflict_value instanceof MergeBucket) {
                foreach ($conflict_value as $conflict_bucket_value) {
                    if (isset($conflict_key)) {
                        $merged_row[ $conflict_column ][$conflict_key] = $conflict_bucket_value;
                    }
                    else {
                        $merged_row[ $conflict_column ][] = $conflict_bucket_value;
                    }
                }
            }
            else {
                if (isset($conflict_key)) {
                    $merged_row[ $conflict_column ][$conflict_key] = $conflict_value;
                }
                else {
                    $merged_row[ $conflict_column ][] = $conflict_value;
                }
            }
        }
        
        return $merged_row;
    }

    /**
     * Merges two rows
     *
     * @param  array $existing_row
     * @param  array $conflict_row
     *
     * @return array
     * 
     * @deprecated
     */
    public static function mergePreservingDistincts(
        $existing_row,
        $conflict_row
    ) {
        return self::mergeInColumnBuckets($existing_row, $conflict_row);
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
            if (in_array($column, $excluded_columns)) {
                continue;
            }

            if ($values instanceof MergeBucket) {
                $values = $values->toArray();
            }
        }

        return $row;
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
     * If an array contains merge buckets merge all those buckets with 
     * the other values.
     * 
     * This is a uni-dimensional flatten implementation
     * 
     * @param  array An array containing MergeBuckets
     * @return array An array conatining all the values of the MergeBuckets
     *               and those of the initial array.
     */
    public static function flattenMergeBuckets(array $array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (! $value instanceof MergeBucket) {
                $result[ $key ] = $value;
            }
            else {
                foreach ($value as $sub_key => $sub_value) {
                    if (is_int($sub_key)) {
                        $result[] = $sub_value;
                    }
                    elseif (isset($result[ $sub_key ])) {
                        throw new \LogicException(
                            "Conflict during flatten merge for key $sub_key between: \n"
                            ."Existing: " . var_export($result[ $sub_key ], true)
                            ."\n and \n"
                            ."Conflict: " . var_export($sub_value, true)
                        );
                    }
                    else {
                        $result[ $sub_key ] = $sub_value;
                    }
                }
            }
        }

        return $result;
    }

    /**/
}
