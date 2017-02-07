<?php
/**
 * ORM-to-ODM relation bridge.
 *
 * @author    Wolfy-J
 */

namespace Spiral;

use Spiral\Core\DirectoriesInterface;
use Spiral\Modules\ModuleInterface;
use Spiral\Modules\PublisherInterface;
use Spiral\Modules\RegistratorInterface;

class HybridDbModule implements ModuleInterface
{
    public function register(RegistratorInterface $registrator)
    {
        $registrator->configure('schemas/relations', 'relations', 'spiral/hybrid-db', [
            '\Spiral\ODM\Document::ONE  => [',
            '   RelationsConfig::SCHEMA_CLASS => Spiral\HybridDB\HasDocumentSchema::class,',
            '   RelationsConfig::LOADER_CLASS => Spiral\HybridDB\HasDocumentLoader::class,',
            '   RelationsConfig::ACCESS_CLASS => Spiral\HybridDB\HasDocumentRelation::class',
            '],',
        ]);
    }

    public function publish(PublisherInterface $publisher, DirectoriesInterface $directories)
    {
        //Nothing to do
    }
}