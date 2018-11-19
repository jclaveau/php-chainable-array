<?php
namespace JClaveau\Exceptions;

class UsagExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     */
    public function test_throwUsageException()
    {
        try {
            (new BadlyUsedObject)->someMethod();
            $this->assertFalse(true, "An exception should have been thrown already");
        }
        catch (\Exception $e) {
            $this->assertEquals( __FILE__, $e->getFile());
            $this->assertEquals( __LINE__ - 5, $e->getLine());

            $this->assertEquals(
                "someMethod badly used",
                $e->getMessage()
            );
        }
    }

    /**/
}

class BadlyUsedObject
{
    public function someMethod()
    {
        throw new UsageException("someMethod badly used");
    }

    public function someOtherMethod()
    {
        $this->someMethod();
    }
}
