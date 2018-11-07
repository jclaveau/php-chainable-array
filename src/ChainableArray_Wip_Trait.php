<?php
namespace JClaveau\Arrays;

/**
 * Functions that are not fully implemented or buggy
 */
trait ChainableArray_Wip_Trait
{

    /**
     *
     */
    public function hierarchize(array $options)
    {
        // $options = [
            // 'before' => [
                // 'action' => '.*',
                // 'values' => 'values|formatted_values',
                // 'group'  => '.*'
            // ],
            // 'after'  => [
                // 'group', 'action', 'values'
            // ],
        // ];

        $out = $this->tableize(
            array_keys($options['before'])
        )
        ->piramidize($options['after'])
        ->dumpJson(true)
        ;

        return $out;
    }

    /**
     * Transforms an array in a 2 dimension array (like a table).
     *
     * @param array $key_columns_names The names to give as column for
     *                                 every dimension.
     * @return Helper_Table
     */
    public function tableize(array $key_column_names)
    {
        $key_column_names = new Helper_Table($key_column_names);

        $out = $this->tableize_aux($key_column_names->copy(), $this->data);

        // return $this->returnConstant($out->getArray());
        return $this->returnConstant($out);
    }

    /**
     * Transforms an array in a 2 dimension array (like an SQL table)
     *
     *  action_calculate_paid_impressions: {
            values: [ ],
            options: {
                label: "Paid Impressions"
            },
            formatted_values: [ ]
        }
        *
        =>
     *  action_calculate_paid_impressions: {
            values: [ ],
            options: {
                label: "Paid Impressions"
            },
            formatted_values: [ ]
        }
        *
        *
        *data: [
            {
            paidImpressions: {
                value: 246367,
                formatted: "246 367"
            },
            publisherCalls: {
                value: 22096941,
                formatted: "22 096 941"
            },
*      *
     *
     *
     *
     *
     */
    private function tableize_aux($key_column_names, $rows)
    {
        $column_name = $key_column_names->shift();

        if (!$column_name)
            return $rows;

        // $out = new static;
        $out = [];
        foreach ($rows as $key => $sub_rows) {

            if ($key_column_names->count()) {

                $sub_rows = $this->tableize_aux(
                    $key_column_names->copy(),
                    $sub_rows
                );

                foreach ($sub_rows as $row) {
                    try {
                        if (is_array($row))
                            $row[$column_name] = $key;
                    }
                    catch (\Exception $e) {
                        echo json_encode($column_name);
                        echo json_encode($key);
                        echo json_encode($e);
                        exit;
                    }
                    $out[] = $row;
                }
            }
            else {
                try {
                    if (is_array($sub_rows))
                        $sub_rows[$column_name] = $key;
                }
                catch (\Exception $e) {
                    echo json_encode($sub_rows);
                    echo json_encode($column_name);
                    echo json_encode($key);
                    echo json_encode($e);
                    exit;
                }
                $out[] = $sub_rows;
            }
        }

        return $out;
    }

    /**
     * @todo : move to unit test
     */
    public function test_tabelize()
    {
        $values = new Helper_Table([
            'a' => [
                'a1' => [
                    'a2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                    'b2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                    'c2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                ],
                'b1' => [
                    'a2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                    'b2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                    'c2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                ],
            ],
            'b' => [
                'a1' => [
                    'a2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                    'b2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                    'c2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                ],
                'b1' => [
                    'a2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                    'b2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                    'c2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                ],
            ],
        ]);

        $tableized = $values->tableize([
            '1st',
            '2nd',
            '3rd'
        ]);

        $expected = array (
            0 => array (
                'a3' => 1,
                'b3' => 1,
                '3rd' => 'a2',
                '2nd' => 'a1',
                '1st' => 'a',
            ),
            1 => array (
                'a3' => 1,
                'b3' => 1,
                '3rd' => 'b2',
                '2nd' => 'a1',
                '1st' => 'a',
            ),
            2 => array (
                'a3' => 1,
                'b3' => 1,
                '3rd' => 'c2',
                '2nd' => 'a1',
                '1st' => 'a',
            ),
            3 => array (
                'a3' => 1,
                'b3' => 1,
                '3rd' => 'a2',
                '2nd' => 'b1',
                '1st' => 'a',
            ),
            4 => array (
                'a3' => 1,
                'b3' => 1,
                '3rd' => 'b2',
                '2nd' => 'b1',
                '1st' => 'a',
            ),
            5 => array (
                'a3' => 1,
                'b3' => 1,
                '3rd' => 'c2',
                '2nd' => 'b1',
                '1st' => 'a',
            ),
            6 => array (
                'a3' => 1,
                'b3' => 1,
                '3rd' => 'a2',
                '2nd' => 'a1',
                '1st' => 'b',
            ),
            7 => array (
                'a3' => 1,
                'b3' => 1,
                '3rd' => 'b2',
                '2nd' => 'a1',
                '1st' => 'b',
            ),
            8 => array (
                'a3' => 1,
                'b3' => 1,
                '3rd' => 'c2',
                '2nd' => 'a1',
                '1st' => 'b',
            ),
            9 => array (
                'a3' => 1,
                'b3' => 1,
                '3rd' => 'a2',
                '2nd' => 'b1',
                '1st' => 'b',
            ),
            10 => array (
                'a3' => 1,
                'b3' => 1,
                '3rd' => 'b2',
                '2nd' => 'b1',
                '1st' => 'b',
            ),
            11 => array (
                'a3' => 1,
                'b3' => 1,
                '3rd' => 'c2',
                '2nd' => 'b1',
                '1st' => 'b',
            ),
        );

        if ($expected != $tableized->getArray())
            throw new \Exception("test failed");

        echo 'Helper_Tabe->tableize tested successfully';
    }

    /**
     * Transforms an array in a 2 dimension array (like a table).
     *
     * @param array $key_columns_names The names to give as column for
     *                                 every dimension.
     * @return Helper_Table
     */
    public function piramidize(array $key_column_names)
    {
        $key_column_names = new Helper_Table($key_column_names);

        $out = $this->piramidize_aux($key_column_names->copy(), $this->data);

        // return $this->returnConstant($out->getArray());
        return $this->returnConstant($out);
    }

    /**
     * Transforms an array in a 2 dimension array (like an SQL table)
     */
    private function piramidize_aux($key_column_names, $rows)
    {
        if (!is_array($rows))
            return $rows;

        $column_name = $key_column_names->shift();

        if (!$column_name)
            return $rows;

        // $out = new static;
        $out = [];
        foreach ($rows as $key => $row) {

            if (!isset($row[$column_name])) {
                throw new \ErrorException("No value found for column"
                    ." name '$column_name' in the row: ".var_export($row, true));
            }

            $key = $row[$column_name];
            unset($row[$column_name]);

            if ($key_column_names->count()) {
                if (!isset($out[$key]))
                    $out[$key] = [];

                $out[$key][] = $row;
            }
            else {
                $out[$key] = $row;
            }
        }

        if ($key_column_names->count()) {
            foreach ($out as $key => $sub_rows) {
                $out[$key] = $this->piramidize_aux(
                    $key_column_names->copy(),
                    $sub_rows
                );
            }
        }

        return $out;
    }

    /**
     * @todo : move to unit test
     */
    public static function test_piramidize()
    {
        $values = new Helper_Table([
            'a' => [
                'a1' => [
                    'a2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                    'b2' => [
                        'a3' => 2,
                        'b3' => 2,
                    ],
                    'c2' => [
                        'a3' => 3,
                        'b3' => 3,
                    ],
                ],
                'b1' => [
                    'a2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                    'b2' => [
                        'a3' => 2,
                        'b3' => 2,
                    ],
                    'c2' => [
                        'a3' => 3,
                        'b3' => 3,
                    ],
                ],
            ],
            'b' => [
                'a1' => [
                    'a2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                    'b2' => [
                        'a3' => 2,
                        'b3' => 2,
                    ],
                    'c2' => [
                        'a3' => 3,
                        'b3' => 3,
                    ],
                ],
                'b1' => [
                    'a2' => [
                        'a3' => 1,
                        'b3' => 1,
                    ],
                    'b2' => [
                        'a3' => 2,
                        'b3' => 2,
                    ],
                    'c2' => [
                        'a3' => 3,
                        'b3' => 3,
                    ],
                ],
            ],
        ]);

        // $values->dumpJson();

        $values->tableize([
            '1st',
            '2nd',
            '3rd',
        ]);

        // $values->dumpJson(
            // true
        // );

        $values->piramidize([
            '3rd',
            '2nd',
            '1st',
        ]);

        $expected = array (
            'a2' => array (
                'a1' => array (
                    'a' => array (
                        'a3' => 1,
                        'b3' => 1,
                    ),
                    'b' => array (
                        'a3' => 1,
                        'b3' => 1,
                    ),
                ),
                'b1' => array (
                    'a' => array (
                        'a3' => 1,
                        'b3' => 1,
                    ),
                    'b' => array (
                        'a3' => 1,
                        'b3' => 1,
                    ),
                ),
            ),
            'b2' => array (
                'a1' => array (
                    'a' => array (
                        'a3' => 2,
                        'b3' => 2,
                    ),
                    'b' => array (
                        'a3' => 2,
                        'b3' => 2,
                    ),
                ),
                'b1' => array (
                    'a' => array (
                        'a3' => 2,
                        'b3' => 2,
                    ),
                    'b' => array (
                        'a3' => 2,
                        'b3' => 2,
                    ),
                ),
            ),
            'c2' => array (
                'a1' => array (
                    'a' => array (
                        'a3' => 3,
                        'b3' => 3,
                    ),
                    'b' => array (
                        'a3' => 3,
                        'b3' => 3,
                    ),
                ),
                'b1' => array (
                    'a' => array (
                        'a3' => 3,
                        'b3' => 3,
                    ),
                    'b' => array (
                        'a3' => 3,
                        'b3' => 3,
                    ),
                ),
            ),
        );


        // echo var_export($values->getArray());
        // $values->dumpJson(true);

        if ($expected != $values->getArray())
            throw new \Exception("test failed");

        echo 'Helper_Tabe->piramidize tested successfully';
    }

    /**/
}
