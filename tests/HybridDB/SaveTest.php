<?php
/**
 * hybrid-db
 *
 * @author    Wolfy-J
 */

namespace Spiral\Tests\HybridDB;

use Spiral\Tests\HybridDB\Fixtures\IPTCMetadata;
use Spiral\Tests\HybridDB\Fixtures\Metadata;
use Spiral\Tests\HybridDB\Fixtures\Photo;

class SaveTest extends BaseTest
{
    public function testSaveWithout()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';
        $photo->save();

        $this->assertNull($photo->metadata);
        $this->assertInstanceOf(Metadata::class, $photo->notNullable);
        $this->assertSame($photo->notNullable, $photo->notNullable);

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());

        $this->assertSame(100, $photo->filesize);
        $this->assertSame('filename', $photo->filename);

        $this->assertTrue(empty($photo->metadata));
    }

    public function testSaveWithMetadata()
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

        $this->assertSame(100, $photo->filesize);
        $this->assertSame('filename', $photo->filename);

        $this->assertFalse(empty($photo->metadata));

        $this->assertInstanceOf(Metadata::class, $photo->metadata);
        $this->assertSame(['metadata', 'keyword'], $photo->metadata->keywords->packValue());
    }

    public function testSaveWithInherited()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';

        $photo->metadata = $this->odm->make(IPTCMetadata::class, [
            'keywords' => ['metadata', 'keyword'],
            'iptc'     => [
                'some' => 'value'
            ]
        ]);

        $photo->save();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());

        $this->assertSame(100, $photo->filesize);
        $this->assertSame('filename', $photo->filename);

        $this->assertFalse(empty($photo->metadata));

        $this->assertSame($photo->metadata, $photo->metadata);

        $this->assertInstanceOf(IPTCMetadata::class, $photo->metadata);
        $this->assertSame(['metadata', 'keyword'], $photo->metadata->keywords->packValue());
        $this->assertSame(['some' => 'value'], $photo->metadata->iptc);
    }
}