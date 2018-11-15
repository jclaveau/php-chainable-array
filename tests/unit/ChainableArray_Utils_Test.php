<?php
namespace JClaveau\Arrays;

class ChainableArray_Utils_Test extends \PHPUnit_Framework_TestCase
{
    /**
     */
    public function test_groupInArrays()
    {
        $array = ChainableArray::from([
            9  => 'zoubidou',
            10 => 'dadoubida',
            11 => 'zoubidi',
            12 => 'zoubida',
            13 => 'treizist',
        ])
        ->groupInArrays(function($row, $key) {
            if (preg_match("#bida$#", $row))
                return 'bidas';
            elseif ($key < 12)
                return 'below 12';
            else
                return 'above 12';
        })
        // ->dump()
        ;

        $this->assertEquals(
            [
                'below 12' => [
                    9  => 'zoubidou',
                    11 => 'zoubidi',
                ],
                'bidas' => [
                    12 => 'zoubida',
                    10 => 'dadoubida',
                ],
                'above 12' => [
                    13 => 'treizist',
                ],
            ],
            $array->toArray()
        );
    }

    /**
     * @todo move this test to a functionnalm folder
     */
    public function test_weightedMean()
    {
        $array = ChainableArray::from([
            13, 18, 13, 14, 13, 16, 14, 21, 13
        ])
        ->groupInArrays( function($number) {
            return $number % 3;
        })
        ->each(function($numbers) {
            $row = [
                'numbers' => $numbers,
                'mean'    => array_sum($numbers) / count($numbers),
                'sum'     => array_sum($numbers),
            ];

            return $row;
        })
        // ->dump(true)
        ->groupBy(
            function($key, $row) {
                return $row['mean'] > 15 ? 'mean_above_15' : 'mean_below_15';
            },
            function($key, $existing_row, $conflict_row) {

                $existing_row['numbers'] = [$existing_row['numbers'], $conflict_row['numbers']];
                $existing_row['mean']    = [$existing_row['mean'],    $conflict_row['mean']];
                $existing_row['sum']     = [$existing_row['sum'],     $conflict_row['sum']];

                $existing_row['weighted_mean'] = Arrays::weightedMean(
                    $existing_row['mean'],
                    $existing_row['sum']
                );

                return $existing_row;
            }
        )
        // ->dump(true)
        ;

        $this->assertEquals(
            13.716666666666667,
            $array->toArray()['mean_below_15']['weighted_mean']
        );
    }

    /**/
}
