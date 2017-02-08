<?php
/**
 * hybrid-db
 *
 * @author    Wolfy-J
 */

namespace Spiral\Tests\HybridDB;

use Spiral\ORM\Commands\CallbackCommand;
use Spiral\ORM\Transaction;
use Spiral\Tests\HybridDB\Fixtures\Metadata;
use Spiral\Tests\HybridDB\Fixtures\Photo;

class TransactionsTest extends BaseTest
{
    public function testSuccessTransaction()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';

        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['metadata', 'keyword']
        ]);
        $photo->save();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey(), ['metadata']);
        $photo->metadata->keywords = ['new'];

        $transaction = new Transaction();
        $transaction->store($photo);
        $transaction->run();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());
        $this->assertSame(['new'], $photo->metadata->keywords->packValue());
    }

    public function testRevertMongoChange()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';

        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['metadata', 'keyword']
        ]);
        $photo->save();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey(), ['metadata']);
        $photo->metadata->keywords = ['new'];

        $transaction = new Transaction();
        $transaction->store($photo);
        $transaction->addCommand(new CallbackCommand(function () {
            throw new \Error('error');
        }));

        try {
            $transaction->run();
        } catch (\Error $e) {
            $this->assertSame('error', $e->getMessage());
        }

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());
        $this->assertSame(['metadata', 'keyword'], $photo->metadata->keywords->packValue());
    }

    public function testSetNullSuccess()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';

        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['metadata', 'keyword']
        ]);
        $photo->save();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey(), ['metadata']);
        $photo->metadata = null;

        $transaction = new Transaction();
        $transaction->store($photo);

        $transaction->run();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());
        $this->assertNull($photo->metadata);
    }

    public function testSetNullRollback()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';

        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['metadata', 'keyword']
        ]);
        $photo->save();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey(), ['metadata']);
        $photo->metadata = null;

        $transaction = new Transaction();
        $transaction->store($photo);
        $transaction->addCommand(new CallbackCommand(function () {
            throw new \Error('error');
        }));

        try {
            $transaction->run();
        } catch (\Error $e) {
            $this->assertSame('error', $e->getMessage());
        }

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());
        $this->assertSame(['metadata', 'keyword'], $photo->metadata->keywords->packValue());
    }

    public function testReplaceSuccess()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';

        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['metadata', 'keyword']
        ]);
        $photo->save();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey(), ['metadata']);
        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['another']
        ]);

        $transaction = new Transaction();
        $transaction->store($photo);

        $transaction->run();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());
        $this->assertSame(['another'], $photo->metadata->keywords->packValue());
    }

    public function testReplaceRollback()
    {
        /** @var Photo $photo */
        $photo = $this->orm->make(Photo::class);
        $photo->filesize = 100;
        $photo->filename = 'filename';

        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['metadata', 'keyword']
        ]);
        $photo->save();

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey(), ['metadata']);
        $photo->metadata = $this->odm->make(Metadata::class, [
            'keywords' => ['another']
        ]);

        $transaction = new Transaction();
        $transaction->store($photo);
        $transaction->addCommand(new CallbackCommand(function () {
            throw new \Error('error');
        }));

        try {
            $transaction->run();
        } catch (\Error $e) {
            $this->assertSame('error', $e->getMessage());
        }

        $photo = $this->orm->source(Photo::class)->findByPK($photo->primaryKey());
        $this->assertSame(['metadata', 'keyword'], $photo->metadata->keywords->packValue());
    }
}