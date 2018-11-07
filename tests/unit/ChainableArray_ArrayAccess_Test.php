<?php
namespace JClaveau\Arrays;

// use JClaveau\VisibilityViolator\VisibilityViolator;

class ChainableArray_ArrayAccess_Test extends \PHPUnit_Framework_TestCase
{
    /**
     */
    public function test_access_entries()
    {
        $array = ChainableArray::from([
            'key_0' => 'zoubidou',
            'key_1' => 'zoubidi',
            'key_2' => 'zoubida',
        ])
        ;
        
        $this->assertEquals('zoubida', $array['key_2']);
    }

    /**
     */
    public function test_set_entries()
    {
        $array = ChainableArray::from();
        $array[] = 'lalala';
        $array['my_key'] = 'plop';
        
        $this->assertEquals('lalala', $array[0]);
        $this->assertEquals('plop', $array['my_key']);
    }

    /**
     */
    public function test_unset_entries()
    {
        $array = ChainableArray::from();
        $array[] = 'lalala';
        $array['my_key'] = 'plop';
        
        unset($array['my_key']);
        
        try {
            $missing_row = $array['my_key'];
            $this->assertFalse(true, 'An exception should have been thrown here');
        }
        catch (\Exception $e) {
            $this->assertEquals('Undefined index: my_key', $e->getMessage());
        }
    }

    /**/
}
