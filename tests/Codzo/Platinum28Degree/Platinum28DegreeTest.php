<?php
namespace Codzo\Platinum28Degree;

use PHPUnit\Framework\TestCase;
use Codzo\Config\Config;
use Codzo\Platinum28Degree\Platinum28Degree;

/**
 * @coversDefaultClass \Codzo\Platinum28Degree\Platinum28Degree
 */
final class Platinum28DegreeTest extends TestCase
{
    protected $config;

    public function setUp()
    {
        $this->config = new Config();
    }

    public function testCanUpdateCache()
    {
        $pd = new Platinum28Degree();

        $this->assertInternalType(
            'string',
            $pd->updateCache()
        );
    }

    public function testCanGetContent()
    {
        $pd = new Platinum28Degree();

        $html = $pd->getContent(true);
        $this->assertInternalType(
            'string',
            $html
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
        $config = new Config();
        $config->set('cache.enabled', false);

        $pd = new Platinum28Degree($config);

        $this->assertInternalType(
            'array',
            $pd->getAccountSummary()
        );

        $this->assertInternalType(
            'array',
            $pd->getLatestTransactions()
        );

        // restore the cache
        $pd->updateCache();
    }
}
