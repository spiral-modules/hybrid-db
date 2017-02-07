<?php
/**
 * hybrid-db
 *
 * @author    Wolfy-J
 */

namespace Spiral\Tests\HybridDB\Fixtures;

use Spiral\ODM\Document;
use Spiral\ORM\Record;

class Photo extends Record
{
    const SCHEMA = [
        'id'          => 'primary',
        'filesize'    => 'int',
        'filename'    => 'string',
        'metadata'    => [Document::ONE => Metadata::class],
        'notNullable' => [
            Document::ONE  => Metadata::class,
            self::NULLABLE => false
        ]
    ];
}