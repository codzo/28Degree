<?php
namespace Codzo\Platinum28Degree;

use PHPUnit\Framework\TestCase;
use Codzo\Platinum28Degree\Platinum28Degree;

/**
 * @coversDefaultClass \Codzo\Platinum28Degree\Platinum28Degree
 */
final class Platinum28DegreeTest extends TestCase
{

    public function testCanUpdateCache()
    {
        $pd = new Platinum28Degree();

        $html = $pd->updateCache();
        $this->assertInternalType(
            'string',
            $html
        );
    }

    public function testCanGetCacheMTime()
    {
        $pd = new Platinum28Degree();

        $this->assertInternalType(
            'int',
            $pd->getCacheMTime()
        );
    }

    public function testCanGetAccountSummary()
    {
        $pd = new Platinum28Degree();

        $this->assertInternalType(
            'array',
            $pd->getAccountSummary()
        );
    }

    public function testCanGetLatestTransactions()
    {
        $pd = new Platinum28Degree();

        $this->assertInternalType(
            'array',
            $pd->getLatestTransactions()
        );
    }

    public function testCanHandleWhenCacheNotExist()
    {
        // del the cache
        unlink(sys_get_temp_dir() . '/codzo.p28d.cache');

        $pd = new Platinum28Degree();

        $this->assertFalse(
            $pd->getCacheMTime()
        );

        $this->assertFalse(
            $pd->getAccountSummary()
        );

        $this->assertFalse(
            $pd->getLatestTransactions()
        );

        // restore the cache
        $pd->updateCache();
    }
}
