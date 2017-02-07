<?php
/**
 * hybrid-db
 *
 * @author    Wolfy-J
 */

namespace Spiral\Tests\HybridDB;

use Spiral\ODM\ODMInterface;
use Spiral\ORM\ORMInterface;

class EnviromentTest extends BaseTest
{
    public function testEnvironment()
    {
        $this->assertInstanceOf(ORMInterface::class, $this->orm);
        $this->assertInstanceOf(ODMInterface::class, $this->odm);
    }
}