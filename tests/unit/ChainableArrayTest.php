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

    /**
     */
    public function test_usort()
    {
        $array = ChainableArray::from([
            'key_0' => 'zoubidou',
            'key_1' => 'zoubidi',
            'key_2' => 'zoubidi',
        ])
        ->usort(function($a, $b) {
            if ($a == $b)
                return 0;

            return $a < $b ? -1 : 1;
        })
        ;
        
        $this->assertEquals(
            [
                'key_1' => 'zoubidi',
                'key_2' => 'zoubidi',
                'key_0' => 'zoubidou',
            ],
            $array->getArray()
        );
    }

    /**
     */
    public function test_mergeWith()
    {
        $array = ChainableArray::from([
            'key_0' => 'zoubidou',
            'key_1' => 'zoubidi',
            'key_2' => 'zoubidi',
        ])
        ->mergeWith( ChainableArray::from([
            'key_5' => ':)',
            'key_6' => ':(',
            'key_7' => 'XD',
        ]) )
        ;
        
        $this->assertEquals(
            [
                'key_0' => 'zoubidou',
                'key_1' => 'zoubidi',
                'key_2' => 'zoubidi',
                'key_5' => ':)',
                'key_6' => ':(',
                'key_7' => 'XD',
            ],
            $array->getArray()
        );
    }

    /**
     */
    public function test_throwUsageException()
    {
        try {
            $array = ChainableArray::from([
                'key_0' => 'zoubidou',
                'key_1' => 'zoubidi',
                'key_2' => 'zoubidi',
            ])
            ->mergeWith('azertyui')
            ;
        }
        catch (\Exception $e) {
            $this->assertEquals( __LINE__ - 4 , $e->getLine());
            $this->assertEquals( __FILE__, $e->getFile());
            
            $this->assertEquals(
                "\$otherTable must be an array or an instance of JClaveau\Arrays\ChainableArray instead of: 'azertyui'",
                $e->getMessage()
            );
            
        }
    }

    /**/
}
