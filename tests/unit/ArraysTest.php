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

    /**/
}
