<?php
/**
 * hybrid-db
 *
 * @author    Wolfy-J
 */

namespace Spiral\Tests\HybridDB\Fixtures;

use Spiral\ODM\Document;

class Metadata extends Document
{
    const SCHEMA = [
        'photo_id' => 'int',
        'keywords' => ['string'],
    ];
}