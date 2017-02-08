<?php
/**
 * hybrid-db
 *
 * @author    Wolfy-J
 */

namespace Spiral\Tests\HybridDB;

use Spiral\Tests\HybridDB\Fixtures\Metadata;
use Spiral\Tests\HybridDB\Fixtures\Photo;

class UpdateTest extends BaseTest
{
    public function testUpdateParentWithoutMetadata()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';

        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['metadata', 'keyword']
        ]);
        $photo->save();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());
        $photo->filesize = 200;
        $photo->save();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());

        $this->assertSame(200, $photo->filesize);
        $this->assertSame(['metadata', 'keyword'], $photo->metadata->keywords->packValue());
    }

    public function testUpdateChild()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';

        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['metadata', 'keyword']
        ]);
        $photo->save();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());
        $photo->metadata->keywords->add('new');
        $photo->save();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());

        $this->assertSame(100, $photo->filesize);
        $this->assertSame(['metadata', 'keyword', 'new'], $photo->metadata->keywords->packValue());
    }

    public function testSetNull()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';

        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['metadata', 'keyword']
        ]);
        $photo->save();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());
        $this->assertNotNull($photo->metadata);
        $photo->metadata = null;
        $photo->save();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());
        $this->assertNull($photo->metadata);
    }

    public function testReplace()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';

        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['metadata', 'keyword']
        ]);
        $photo->save();

        $objectId = $photo->metadata->_id;

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());
        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['another']
        ]);

        $photo->save();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());
        $this->assertNotNull($photo->metadata);
        $this->assertSame(['another'], $photo->metadata->keywords->packValue());

        $this->assertNotSame((string)$objectId, (string)$photo->metadata->_id);
    }
}