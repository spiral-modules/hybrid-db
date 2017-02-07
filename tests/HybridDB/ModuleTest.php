<?php
/**
 * hybrid-db
 *
 * @author    Wolfy-J
 */

namespace Spiral\Tests\HybridDB;

use PHPUnit\Framework\TestCase;
use Spiral\Core\DirectoriesInterface;
use Spiral\HybridDbModule;
use Spiral\Modules\PublisherInterface;
use Spiral\Modules\RegistratorInterface;

class ModuleTest extends TestCase
{
    public function testRegistration()
    {
        $module = new HybridDbModule();
        $registrator = \Mockery::mock(RegistratorInterface::class);
        $registrator->shouldReceive('configure');

        $module->register($registrator);
    }

    public function testPublishing()
    {
        $module = new HybridDbModule();
        $publisher = \Mockery::mock(PublisherInterface::class);
        $publisher->shouldNotReceive('publish');
        $publisher->shouldNotReceive('publishDiretory');

        $module->publish($publisher, \Mockery::mock(DirectoriesInterface::class));
    }
}