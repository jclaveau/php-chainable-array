<?php
namespace JClaveau\Arrays;

// use JClaveau\VisibilityViolator\VisibilityViolator;

class ChainableArrayTest extends \PHPUnit_Framework_TestCase
{
    /**
     */
    public function test_getArray()
    {
        $array = ChainableArray::from([
            'key_0' => 'zoubidou',
            'key_1' => 'zoubidi',
            'key_2' => 'zoubida',
        ])
        ;
        
        $this->assertEquals(
            [
                'key_0' => 'zoubidou',
                'key_1' => 'zoubidi',
                'key_2' => 'zoubida',
            ],
            $array->getArray()
        );
    }

    /**
     */
    public function test_toArray()
    {
        $array = ChainableArray::from([
            'key_0' => 'zoubidou',
            'key_1' => 'zoubidi',
            'key_2' => 'zoubida',
        ])
        ;
        
        $this->assertEquals(
            [
                'key_0' => 'zoubidou',
                'key_1' => 'zoubidi',
                'key_2' => 'zoubida',
            ],
            $array->toArray()
        );
    }

    /**
     */
    public function test_filter()
    {
        $array = ChainableArray::from([
            'key_0' => 'zoubidou',
            'key_1' => 'zoubidi',
            'key_2' => 'zoubida',
        ])
        ->filter(function ($value, $key) {
            return $key == 'key_2' 
                || $value == 'zoubidi'
                ;
        })
        ;
        
        $this->assertEquals(
            [
                'key_1' => 'zoubidi',
                'key_2' => 'zoubida',
            ],
            $array->getArray()
        );
    }

    /**
     */
    public function test_unique()
    {
        $array = ChainableArray::from([
            'key_0' => 'zoubidou',
            'key_1' => 'zoubidi',
            'key_2' => 'zoubidi',
        ])
        ->unique()
        ;
        
        $this->assertEquals(
            [
            'key_0' => 'zoubidou',
            'key_1' => 'zoubidi',
            ],
            $array->getArray()
        );
    }

    /**/
}
