<?php
namespace JClaveau\Arrays;

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
