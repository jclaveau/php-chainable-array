<?php
namespace JClaveau\Arrays;
use       JClaveau\Exceptions\UsageException;

class ArraysTest extends \PHPUnit_Framework_TestCase
{
    /**
     */
    public function test_mergePreservingDistincts()
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

        $conflict_row_2 = [
            'entry_1' => 'plaf',
            4         => 'lulu',
            'entry_2' => [
                'lele',
            ],
        ];

        $merged_row = Arrays::mergePreservingDistincts($merged_row, $conflict_row_2);

        $merged_row = Arrays::cleanMergeBuckets($merged_row);

        // var_dump($merged_row);
        // exit;
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
    public function test_unique()
    {
        $array = [
            5  => 'plop',
            10 => 'lala',
            15 => 'plop',
            20 => 'lulu',
            25 => ['lolo'],
            30 => ['lolo'],
            25 => (object)['lulu'],
            30 => (object)['lulu'],
        ];

        $array = Arrays::unique($array);

        $this->assertEquals(
            [
                5  => 'plop',
                10 => 'lala',
                20 => 'lulu',
                25 => ['lolo'],
                25 => (object)['lulu'],
            ],
            $array
        );
    }

    /**
     */
    public function test_sum()
    {
        $array = [4, 5, 6];
        $sum = Arrays::sum($array);
        $this->assertEquals(15, $sum);

        // summing array
        $array = [4, 5, [12]];
        try {
            $sum = Arrays::sum($array);
            $this->assertFalse(true, "An exception should have been thrown here");
        }
        catch (\Exception $e) {
            $this->assertEquals(
                "Trying to sum an array with '9': array (\n  0 => 12,\n)",
                $e->getMessage()
            );
        }

        // summing objects
        $array = [4, 5, (object) [12]];
        try {
            $sum = Arrays::sum($array);
            $this->assertFalse(true, "An exception should have been thrown here");
        }
        catch (\Exception $e) {
            $this->assertEquals(
                "Trying to sum a stdClass object which cannot be casted as a number. Please add a toNumber() method.",
                $e->getMessage()
            );
        }

        $array = [4, 5, new NumberableObject(6)];
        $sum = Arrays::sum($array);
        $this->assertEquals(15, $sum);
    }

    /**
     */
    public function test_mustBeCountable()
    {
        $this->assertTrue( Arrays::mustBeCountable([]) );
        $this->assertTrue( Arrays::mustBeCountable( ChainableArray::from() ) );

        try {
            Arrays::mustBeCountable('lalala');
            $this->assertFalse(true, "An exception should have been thrown here");
        }
        catch (\Exception $e) {
            $this->assertEquals( __FILE__, $e->getFile());
            $this->assertEquals( __LINE__ - 5, $e->getLine());

            $this->assertEquals(
                "A value must be Countable instead of: \n'lalala'",
                $e->getMessage()
            );
        }
    }

    /**
     */
    public function test_mustBeTraversable()
    {
        $this->assertTrue( Arrays::mustBeTraversable([]) );
        $this->assertTrue( Arrays::mustBeTraversable( ChainableArray::from() ) );

        try {
            Arrays::mustBeTraversable('lalala');
            $this->assertFalse(true, "An exception should have been thrown here");
        }
        catch (\Exception $e) {
            $this->assertEquals( __FILE__, $e->getFile());
            $this->assertEquals( __LINE__ - 5, $e->getLine());

            $this->assertEquals(
                "A value must be Traversable instead of: \n'lalala'",
                $e->getMessage()
            );
        }
    }

    /**
     */
    public function test_keyExists()
    {
        $this->assertTrue( Arrays::keyExists('lala', ['lala' => null]) );
        $this->assertTrue( Arrays::keyExists('lala', ChainableArray::from(['lala' => null]) ) );

        $this->assertFalse( Arrays::keyExists('lolo', ['lala' => null]) );
        $this->assertFalse( Arrays::keyExists('lolo', ChainableArray::from(['lala' => null]) ) );
    }

    /**
     */
    public function test_generateGroupId()
    {
        $row = [
            'col_1' => 12,
            'col_2' => null,
            'col_3' => function ($argument) {
                return $argument . '_suffixe';
            },
            4 => 'value_of_fourth_col',
            'col_5' => [
                'lala',
                'lolo',
            ],
        ];

        $this->assertEquals('col_1:12', Arrays::generateGroupId($row, [
            'col_1',
        ]) );

        $this->assertEquals('col_2:', Arrays::generateGroupId($row, [
            'col_2',
        ]) );

        $this->assertEquals('col_3:Closure_1ac7e0c5', Arrays::generateGroupId($row, [
            'col_3',
        ]) );

        $this->assertEquals('4:value_of_fourth_col', Arrays::generateGroupId($row, [
            4,
        ]) );

        $this->assertEquals('column_4:value_of_fourth_col', Arrays::generateGroupId($row, [
            'column' => 4,
        ]) );

        $this->assertEquals('col_5:array_8940e967', Arrays::generateGroupId($row, [
            'col_5',
        ]) );

        $this->assertEquals('col_1:12-col_2:', Arrays::generateGroupId($row, [
            'col_1',
            'col_2',
        ]) );

        $this->assertEquals('col_1=>12~col_2=>', Arrays::generateGroupId($row, [
            'col_1',
            'col_2',
        ], [
            'key_value_separator' => '=>',
            'groups_separator'    => '~',
        ]) );

        try {
            $this->assertEquals('col_1:12-col_2:', Arrays::generateGroupId($row, [
                ['fghjkl']
            ]) );

            $this->assertTrue( false, 'An exception should elready be thrown');
        }
        catch (UsageException $e) {
            $this->assertEquals( __FILE__, $e->getFile());
            $this->assertEquals(
                   __LINE__ - 6 // PHP 5.6
                || __LINE__ - 7 // PHP 7
                , $e->getLine()
            );

            $this->assertEquals( 1, preg_match(
                "#^Bad value provided for group id generation:#",
                $e->getMessage()
            ));
        }

        try {
            $this->assertEquals('col_1:12-col_2:', Arrays::generateGroupId($row, [
                'unset_column'
            ]) );

            $this->assertTrue( false, 'An exception should elready be thrown');
        }
        catch (UsageException $e) {
            $this->assertEquals( __FILE__, $e->getFile());
            $this->assertEquals(
                   __LINE__ - 6 // PHP 5.6
                || __LINE__ - 7 // PHP 7
                , $e->getLine()
            );
            $this->assertEquals( 1, preg_match(
                "#^Unset column for group id generation: 'unset_column'#",
                $e->getMessage()
            ));
        }

        try {
            $this->assertEquals('col_1:12-col_2:', Arrays::generateGroupId($row, [
                12
            ]) );

            $this->assertTrue( false, 'An exception should elready be thrown');
        }
        catch (UsageException $e) {
            $this->assertEquals( __FILE__, $e->getFile());
            $this->assertEquals(
                   __LINE__ - 6 // PHP 5.6
                || __LINE__ - 7 // PHP 7
                , $e->getLine()
            );
            $this->assertEquals( 1, preg_match(
                "#^Unset column for group id generation: 12#",
                $e->getMessage()
            ));
        }

        $row['col_6'] = MergeBucket::from([
            'lalala',
            'lololo',
        ]);
        $row['col_7'] = [
            'lalala',
            'lololo',
        ];
        $chainable_row = ChainableArray::from($row);
        $this->assertEquals(
            'col_1:12-col_2:-col_6:JClaveau\Arrays\MergeBucket_98133d8e-col_7:array_712811e5',
            Arrays::generateGroupId($chainable_row, [
                'col_1',
                'col_2',
                'col_6',
                'col_7',
            ])
        );


        $this->assertEquals(
            'col_1:12-unnamed-closure-1ac7e0c5:value_of_fourth_col12',
            Arrays::generateGroupId($row, [
                'col_1',
                function($row, &$key) {
                    return $row[4] . $row['col_1'];
                }
            ])
        );
    }

    /**/
}

class NumberableObject
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function toNumber()
    {
        return $this->value;
    }
}
