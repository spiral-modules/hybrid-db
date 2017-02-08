<?php
/**
 * hybrid-db
 *
 * @author    Wolfy-J
 */

namespace Spiral\Tests\HybridDB;

use Spiral\HybridDB\HasDocumentLoader;
use Spiral\Tests\HybridDB\Fixtures\Metadata;

class LoaderTest extends BaseTest
{
    public function testGetClass()
    {
        $loader = new HasDocumentLoader(
            Metadata::class,
            [

            ],
            $this->odm
        );

        $this->assertSame(Metadata::class, $loader->getClass());
    }

    /**
     * @expectedException \Spiral\ORM\Exceptions\LoaderException
     * @expectedExceptionMessage HasDocumentLoader does not support any options
     */
    public function testSetOptions()
    {
        $loader = new HasDocumentLoader(
            Metadata::class,
            [

            ],
            $this->odm
        );

        $loader->withContext(clone $loader, ['option']);
    }
}