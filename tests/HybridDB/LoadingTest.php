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

class LoadingTest extends BaseTest
{
    public function testLoadButNoRelated()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';
        $photo->save();

        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 200;
        $photo->filename = 'filename';
        $photo->save();

        $selector = $this->orm->selector(Photo::class)->load('metadata');

        $count = 0;
        foreach ($selector as $photo) {
            $this->assertTrue(empty($photo->metadata));
            $this->assertNull($photo->metadata);

            $count++;
        }

        $this->assertSame(2, $count);
    }

    public function testLoadButNoParents()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';
        $photo->save();

        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 200;
        $photo->filename = 'filename';
        $photo->save();

        $selector = $this->orm->selector(Photo::class)
            ->where('filesize', 300)
            ->load('metadata');

        $count = 0;
        foreach ($selector as $photo) {
            $this->assertTrue(empty($photo->metadata));
            $this->assertNull($photo->metadata);

            $count++;
        }

        $this->assertSame(0, $count);
    }

    public function testLoadButWithRelated()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';
        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['metadata', 'keyword']
        ]);
        $photo->save();

        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 200;
        $photo->filename = 'filename';
        $photo->metadata = $this->odm->make(IPTCMetadata::class, [
            'keywords' => ['metadata', 'keyword'],
            'iptc'     => [
                'some' => 'value'
            ]
        ]);
        $photo->save();

        $selector = $this->orm->selector(Photo::class)->load('metadata')->orderBy('id');

        $count = 0;
        foreach ($selector as $photo) {
            $this->assertNotNull($photo->metadata);

            if ($count == 0) {
                $this->assertInstanceOf(Metadata::class, $photo->metadata);
                $this->assertSame(['metadata', 'keyword'], $photo->metadata->keywords->packValue());
            } else {
                $this->assertInstanceOf(IPTCMetadata::class, $photo->metadata);
                $this->assertSame(['metadata', 'keyword'], $photo->metadata->keywords->packValue());
                $this->assertSame(['some' => 'value'], $photo->metadata->iptc);
            }

            $count++;
        }

        $this->assertSame(2, $count);
    }

    public function testLoadPartial()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';
        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['metadata', 'keyword']
        ]);
        $photo->save();

        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 200;
        $photo->filename = 'filename';
        $photo->save();

        $selector = $this->orm->selector(Photo::class)->load('metadata')->orderBy('id');

        $count = 0;
        foreach ($selector as $photo) {
            if ($count == 0) {
                $this->assertInstanceOf(Metadata::class, $photo->metadata);
                $this->assertSame(['metadata', 'keyword'], $photo->metadata->keywords->packValue());
            } else {
                $this->assertTrue(empty($photo->metadata));
                $this->assertNull($photo->metadata);
            }

            $count++;
        }

        $this->assertSame(2, $count);
    }

    public function testLoadPartialReverted()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';
        $photo->save();

        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 200;
        $photo->filename = 'filename';
        $photo->metadata = $this->odm->make(IPTCMetadata::class, [
            'keywords' => ['metadata', 'keyword'],
            'iptc'     => [
                'some' => 'value'
            ]
        ]);
        $photo->save();

        $selector = $this->orm->selector(Photo::class)->load('metadata')->orderBy('id');

        $count = 0;
        foreach ($selector as $photo) {
            if ($count == 0) {
                $this->assertTrue(empty($photo->metadata));
                $this->assertNull($photo->metadata);
            } else {
                $this->assertInstanceOf(IPTCMetadata::class, $photo->metadata);
                $this->assertSame(['metadata', 'keyword'], $photo->metadata->keywords->packValue());
                $this->assertSame(['some' => 'value'], $photo->metadata->iptc);
            }

            $count++;
        }

        $this->assertSame(2, $count);
    }
}