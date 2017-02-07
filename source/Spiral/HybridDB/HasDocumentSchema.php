<?php
/**
 * ORM-to-ODM relation bridge.
 *
 * @author    Wolfy-J
 */

namespace Spiral\HybridDB;

use Spiral\ODM\Document;
use Spiral\ORM\Helpers\RelationOptions;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Record;
use Spiral\ORM\Schemas\Definitions\RelationDefinition;
use Spiral\ORM\Schemas\RelationInterface;
use Spiral\ORM\Schemas\SchemaBuilder;

/**
 * Declares schema between record (leading) and document.
 */
class HasDocumentSchema implements RelationInterface
{
    /**
     * Relation type to be stored in packed schema.
     */
    const RELATION_TYPE = Document::ONE;

    /**
     * Most of relations provides ability to specify many different configuration options, such
     * as key names, pivot table schemas, foreign key request, ability to be nullable and etc.
     *
     * To simple schema definition in real projects we can fill some of this values automatically
     * based on some "environment" values such as parent/outer record table, role name, primary key
     * and etc.
     *
     * Example:
     * Record::INNER_KEY => '{outer:role}_{outer:primaryKey}'
     *
     * Result:
     * Outer Record is User with primary key "id" => "user_id"
     *
     * @var array
     */
    const OPTIONS_TEMPLATE = [
        Record::NULLABLE  => true,
        Record::INNER_KEY => '{source:primaryKey}',
        Record::OUTER_KEY => '{source:role}_{option:innerKey}'
    ];

    /**
     * @var RelationDefinition
     */
    protected $definition;

    /**
     * Provides ability to define missing relation options based on template. Column names will be
     * added automatically if target presented.
     *
     * @see self::OPTIONS_TEMPLATE
     * @var RelationOptions
     */
    protected $options;

    /**
     * @param RelationDefinition $definition
     */
    public function __construct(RelationDefinition $definition)
    {
        $this->definition = $definition;
        $this->options = new RelationOptions($definition, static::OPTIONS_TEMPLATE);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(): RelationDefinition
    {
        return $this->definition;
    }

    /**
     * {@inheritdoc}
     */
    public function declareTables(SchemaBuilder $builder): array
    {
        //ODM relations do not alter any of SQL tables
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function packRelation(SchemaBuilder $builder): array
    {
        return [
            ORMInterface::R_TYPE   => static::RELATION_TYPE,
            ORMInterface::R_CLASS  => $this->definition->getTarget(),
            ORMInterface::R_SCHEMA => [
                Record::NULLABLE  => $this->options->define(Record::NULLABLE),
                Record::INNER_KEY => $this->options->define(Record::INNER_KEY),
                Record::OUTER_KEY => $this->options->define(Record::OUTER_KEY)
            ]
        ];
    }
}