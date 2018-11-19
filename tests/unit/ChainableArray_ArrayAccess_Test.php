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

    /**
     */
    public function test_access_unset_entries_with_default()
    {
        $array = ChainableArray::from([
            'lalala',
            'plop',
        ]);
        $this->assertFalse( $array->hasDefaultRowValue() );

        // scalar defaulkt row
        $array->setDefaultRow('lilili');

        $this->assertEquals('lalala', $array[0]);
        $this->assertEquals('lilili', $array[2]);
        $this->assertEquals('lilili', $array['dfghjkl']);

        $this->assertTrue( $array->hasDefaultRowValue() );

        // default row generator
        $array->setDefaultRowGenerator(function($key) {
            return "'$key' is the best key ever";
        });

        $this->assertEquals("':)' is the best key ever", $array[':)']);

        // removed default row
        $array->unsetDefaultRow();
        $this->assertFalse( $array->hasDefaultRowValue() );
    }

    /**
     */
    public function test_array_key_exists()
    {
        $array = ChainableArray::from();
        $array[] = 'lalala';
        $array['my_key'] = 'plop';

        // php bug https://stackoverflow.com/questions/1538124/php-array-key-exists-and-spl-arrayaccess-interface-not-compatible
        $this->assertFalse( array_key_exists(0, $array) );
        $this->assertFalse( array_key_exists('my_key', $array) );
    }

    /**/
}
