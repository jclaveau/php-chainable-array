<?php
namespace JClaveau\Arrays;

class ChainableArray_Utils_Test extends \PHPUnit_Framework_TestCase
{
    /**
     */
    public function test_groupInArrays()
    {
        $array = ChainableArray::from([
            9 => 'zoubidou',
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

    /**/
}
