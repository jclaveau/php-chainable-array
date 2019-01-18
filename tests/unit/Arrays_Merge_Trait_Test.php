<?php
use JClaveau\Exceptions\UsageException;
use JClaveau\Arrays\Arrays;
use JClaveau\Arrays\MergeBucket;

trait Arrays_Merge_Trait_Test
{
    /**
     */
    public function test_mergeInColumnBuckets()
    {
        $existing_row = [
            'entry_1' => 'plop',
            4         => 'lolo',
            'entry_2' => [
                'lolo',
            ],
        ];

        $conflict_row = [
            'entry_1' => 'plouf',
            4         => 'lolo',
            'entry_2' => [
                'lala',
            ],
        ];

        $merged_row = Arrays::mergeInColumnBuckets($existing_row, $conflict_row);

        $this->assertEquals(
            [
                'entry_1' => MergeBucket::from([
                    'plop',
                    'plouf',
                ]),
                4 => MergeBucket::from([
                    'lolo',
                    'lolo',
                ]),
                'entry_2' => MergeBucket::from([
                    ['lolo',],
                    ['lala',],
                ]),
            ],
            $merged_row
        );

        $merged_row = Arrays::mergeInColumnBuckets($existing_row, [4 => null]);

        $this->assertEquals(
            [
                'entry_1' => MergeBucket::from([
                    'plop',
                ]),
                4 => MergeBucket::from([
                    'lolo',
                    null,
                ]),
                'entry_2' => MergeBucket::from([
                    ['lolo'],
                ]),
            ],
            $merged_row
        );
        
        $merged_row = Arrays::mergeInColumnBuckets([4 => null], $existing_row);

        $this->assertEquals(
            [
                'entry_1' => MergeBucket::from([
                    'plop',
                ]),
                4 => MergeBucket::from([
                    null,
                    'lolo',
                ]),
                'entry_2' => MergeBucket::from([
                    ['lolo'],
                ]),
            ],
            $merged_row
        );
    }
    
    /**
     */
    public function test_mergeInColumnBuckets_multiple()
    {
        $existing_row = [
            'entry_1' => 'plop',
            4         => 'lolo',
            'entry_2' => [
                'lolo',
            ],
        ];

        $conflict_row = [
            'entry_1' => 'plouf',
            4         => 'lolo',
            'entry_2' => [
                'lala',
            ],
        ];

        $merged_row = Arrays::mergeInColumnBuckets($existing_row, $conflict_row);

        $conflict_row_2 = [
            'entry_1' => 'plaf',
            4         => 'lulu',
            'entry_2' => [
                'lele',
            ],
        ];

        $merged_row = Arrays::mergeInColumnBuckets($merged_row, $conflict_row_2);

        $merged_row = Arrays::cleanMergeBuckets($merged_row);

        $this->assertEquals(
            [
                'entry_1' => [
                    'plop',
                    'plouf',
                    'plaf',
                ],
                4 => [
                    'lolo',
                    'lolo',
                    'lulu',
                ],
                'entry_2' => [
                    [
                        'lolo',
                    ],
                    [
                        'lala',
                    ],
                    [
                        'lele',
                    ],
                ],
            ],
            $merged_row
        );
    }
    
    /**
     */
    public function test_mergeInColumnBuckets_multiple_assoc()
    {
        $existing_row = [
            'entry_1' => 'plop',
            4         => 'lolo',
            'entry_2' => [
                'lolo',
            ],
        ];

        $conflict_row = [
            'entry_1' => 'plouf',
            4         => 'lolo',
            'entry_2' => [
                'lala',
            ],
        ];

        $merged_row = Arrays::mergeInColumnBuckets(
            $existing_row, 
            $conflict_row,
            'existing_row', 
            'conflict_row'
        );

        $conflict_row_2 = [
            'entry_1' => 'plaf',
            4         => 'lulu',
            'entry_2' => [
                'lele',
            ],
        ];

        $merged_row = Arrays::mergeInColumnBuckets(
            $merged_row, 
            $conflict_row_2,
            null,
            'conflict_row_2'
        );

        $merged_row = Arrays::cleanMergeBuckets($merged_row);

        $this->assertEquals(
            [
                'entry_1' => [
                    'existing_row' => 'plop',
                    'conflict_row' => 'plouf',
                    'conflict_row_2' => 'plaf',
                ],
                4 => [
                    'existing_row' => 'lolo',
                    'conflict_row' => 'lolo',
                    'conflict_row_2' => 'lulu',
                ],
                'entry_2' => [
                    'existing_row' => [
                        'lolo',
                    ],
                    'conflict_row' => [
                        'lala',
                    ],
                    'conflict_row_2' => [
                        'lele',
                    ],
                ],
            ],
            $merged_row
        );
    }
    
    /**
     */
    public function test_mergeInColumnBuckets_mergeValuesOnNull()
    {
        $existing_row = [
            'count' => NULL,
        ];

        $conflict_row = [
            'id' => MergeBucket::from([
                0 => 288,
                1 => 529,
                2 => 528,
                3 => 350,
            ]),
            'event' => 'my_event',
            'count' => 123,
        ];

        $merged_row = Arrays::mergeInColumnBuckets(
            $existing_row, 
            $conflict_row
        );

        $this->assertEquals(
            [
                'id' => MergeBucket::from([
                    0 => 288,
                    1 => 529,
                    2 => 528,
                    3 => 350,
                ]),
                'event' => MergeBucket::from([
                    'my_event',
                ]),
                'count' => MergeBucket::from([
                    null,
                    123,
                ]),
            ],
            $merged_row
        );
    }
    
    /**
     */
    public function test_cleanMergeBuckets()
    {
        $merged_row = [
            'entry_1' => MergeBucket::from([
                'plop',
            ]),
            4 => MergeBucket::from([
                'lolo',
                null,
            ]),
            'entry_2' => MergeBucket::from([
                ['lolo'],
            ]),
        ];

        $merged_row = Arrays::cleanMergeBuckets($merged_row);

        $this->assertEquals(
            [
                'entry_1' => [
                    'plop',
                ],
                4 => [
                    'lolo',
                    null,
                ],
                'entry_2' => [
                    [
                        'lolo',
                    ],
                ],
            ],
            $merged_row
        );
    }

    /**
     */
    public function test_cleanMergeBuckets_with_excludedColumns()
    {
        $merged_row = [
            'entry_1' => MergeBucket::from([
                'plop',
            ]),
            4 => MergeBucket::from([
                'lolo',
                null,
            ]),
            'entry_2' => MergeBucket::from([
                ['lolo'],
            ]),
        ];

        $merged_row = Arrays::cleanMergeBuckets($merged_row, [
            'excluded_columns' => ['entry_1'],
        ]);

        $this->assertEquals(
            [
                'entry_1' => MergeBucket::from([
                    'plop',
                ]),
                4 => [
                    'lolo',
                    null,
                ],
                'entry_2' => [
                    [
                        'lolo',
                    ],
                ],
            ],
            $merged_row
        );
    }

    /**
     */
    public function test_cleanMergeDuplicates()
    {
        $existing_row = [
            'entry_1' => 'plop',
            4         => 'lolo',
            'entry_2' => [
                'lolo',
            ],
        ];

        $conflict_row = [
            'entry_1' => 'plouf',
            4         => 'lolo',
            'entry_2' => [
                'lala',
            ],
        ];

        $merged_row = Arrays::mergePreservingDistincts($existing_row, $conflict_row);
        $merged_row = Arrays::cleanMergeDuplicates($merged_row);
        $merged_row = Arrays::cleanMergeBuckets($merged_row);

        $this->assertEquals(
            [
                'entry_1' => [
                    'plop',
                    'plouf',
                ],
                4 => 'lolo',
                'entry_2' => [
                    [
                        'lolo',
                    ],
                    [
                        'lala',
                    ],
                ],
            ],
            $merged_row
        );
    }

    /**
     */
    public function test_flattenMergeBuckets()
    {
        $result_of_array_column = [
            MergeBucket::from([
                'plop',
            ]),
            MergeBucket::from([
                'lolo',
                null,
            ]),
            MergeBucket::from([
                ['lolo'],
            ]),
            MergeBucket::from([
                'key_1' => 'lolo',
                'key_2' => 'lulu',
            ]),
            'prout',
        ];

        $flattened_row = Arrays::flattenMergeBuckets($result_of_array_column);

        $this->assertEquals(
            [
                'plop',
                'lolo',
                null,
                ['lolo'],
                'key_1' => 'lolo',
                'key_2' => 'lulu',
                'prout',
            ],
            $flattened_row
        );
    }

    /**
     */
    public function test_flattenMergeBuckets_sameKey_exception()
    {
        $result_of_array_column = [
            MergeBucket::from([
                'key_1' => 'lala',
                'key_2' => 'lili',
            ]),
            MergeBucket::from([
                'key_1' => 'lolo',
                'key_2' => 'lulu',
            ]),
        ];

        try {
            Arrays::flattenMergeBuckets($result_of_array_column);
            $this->assertTrue(false, "an exception should have been thrown here");
        }
        catch (LogicException $e) {
            $this->assertEquals(
                $e->getMessage(),
                "Conflict during flatten merge for key key_1 between: 
Existing: 'lala'
 and 
Conflict: 'lolo'"
            );
        }
    }

    /**/
}
