<?php
/**
 * ORM-to-ODM relation bridge.
 *
 * @author    Wolfy-J
 */

namespace Spiral\HybridDB;

use Spiral\ODM\Document;
use Spiral\ODM\ODMInterface;
use Spiral\ORM\CommandInterface;
use Spiral\ORM\Commands\CallbackCommand;
use Spiral\ORM\ContextualCommandInterface;
use Spiral\ORM\Entities\Relations\Traits\LookupTrait;
use Spiral\ORM\Exceptions\RelationException;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Record;
use Spiral\ORM\RecordInterface;
use Spiral\ORM\RelationInterface;

/**
 * Represents one outer document.
 */
class HasDocumentRelation implements \Spiral\ORM\RelationInterface
{
    use LookupTrait;

    /**
     * @var bool
     */
    protected $loaded;

    /**
     * @invisible
     * @var RecordInterface
     */
    protected $parent;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var array
     */
    protected $schema;

    /**
     * @invisible
     * @var ORMInterface
     */
    protected $orm;

    /**
     * @invisible
     * @var ODMInterface
     */
    protected $odm;

    /**
     * Loaded related data.
     *
     * @invisible
     * @var array|null
     */
    protected $data = null;

    /**
     * @var \Spiral\ODM\Document
     */
    protected $instance;

    /**
     * @param string       $class
     * @param array        $schema
     * @param ORMInterface $orm
     * @param ODMInterface $odm
     */
    public function __construct(string $class, array $schema, ORMInterface $orm, ODMInterface $odm)
    {
        $this->class = $class;
        $this->schema = $schema;
        $this->orm = $orm;
        $this->odm = $odm;
    }

    /**
     * @return bool
     */
    public function isLeading(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function withContext(
        RecordInterface $parent,
        bool $loaded = false,
        array $data = null
    ): RelationInterface {
        $relation = clone $this;
        $relation->parent = $parent;
        $relation->loaded = $loaded;
        $relation->data = is_null($data) ? [] : $data;

        return $relation;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRelated(): bool
    {
        if (!$this->isLoaded()) {
            //Lazy loading our relation data
            $this->loadData();
        }

        return !empty($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function setRelated($value)
    {
        if (is_null($value) && !$this->schema[Record::NULLABLE]) {
            throw new RelationException("Unable to set null value for non nullable relation");
        }

        if (!is_null($value) && !$value instanceof $this->class) {
            throw new RelationException(
                "Unable to set related entity, must be an instance of '{$this->class}'"
            );
        }

        $this->loadData();
        $this->instance = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelated()
    {
        if ($this->instance instanceof Document) {
            return $this->instance;
        }

        //Lazy loading our relation data
        $this->loadData();

        if (empty($this->data)) {
            if (!$this->schema[Record::NULLABLE]) {
                //Stub instance
                return $this->instance = $this->odm->make($this->getClass(), [], false);
            }

            //Relation is nullable and no value is presented
            return null;
        }

        //Create instance based on loaded data
        return $this->instance = $this->odm->make($this->getClass(), $this->data, false);
    }

    /**
     * {@inheritdoc}
     */
    public function queueCommands(ContextualCommandInterface $parentCommand): CommandInterface
    {
        $command = new CallbackCommand(function () use ($parentCommand) {
            if (!empty($this->instance)) {
                $this->instance->setField(
                    $this->key(Record::OUTER_KEY),
                    $this->lookupKey(Record::INNER_KEY, $this->parent, $parentCommand)
                );

                $this->instance->save();

                //De-associate previous document
                if (!empty($this->data) && $this->data['_id'] != $this->instance->primaryKey()) {
                    $this->odm->collection($this->class)->deleteOne([
                        '_id' => $this->data['_id']
                    ]);
                }
            } elseif (!empty($this->data)) {
                //Association removed
                $this->odm->collection($this->class)->deleteOne([
                    '_id' => $this->data['_id']
                ]);
            }
        });

        //Revert state
        $command->onRollBack(function () {
            if (!empty($this->data)) {
                if ($this->data['_id'] != $this->instance->primaryKey()) {
                    //Restore original document
                    $this->odm->collection($this->class)->insertOne($this->data);
                } else {
                    //Restoring original state
                    $this->odm->collection($this->class)->updateOne(
                        ['_id' => $this->data['_id']],
                        ['$set' => $this->data]
                    );
                }
            } elseif (!empty($this->instance)) {
                //Delete newly created instance
                $this->instance->delete();
            }
        });

        //Finish sync
        $command->onComplete(function () {
            //Flush relation status
            $this->data = !empty($this->instance) ? $this->instance->packValue(true) : [];
        });

        return $command;
    }

    /**
     * Load outer document data.
     */
    protected function loadData()
    {
        if ($this->loaded) {
            //Already loaded, nothing to do
            return;
        }

        $this->loaded = true;

        if (empty($innerKey = $this->parent->getField($this->key(Record::INNER_KEY)))) {
            //Parent not loaded or key is missing
            return;
        }

        $selector = $this->odm->selector($this->class)->where([
            $this->key(Record::OUTER_KEY) => $innerKey
        ]);

        //First element from selection (in a form of array)
        $this->data = current($selector->limit(1)->getProjection()->toArray());
    }

    /**
     * Get value from schema.
     *
     * @param int $key
     *
     * @return mixed
     */
    protected function key(int $key)
    {
        return $this->schema[$key];
    }

    /**
     * Defined primary key of parent record.
     *
     * @param \Spiral\ORM\RecordInterface $record
     *
     * @return string
     */
    protected function primaryColumnOf(RecordInterface $record): string
    {
        return $this->orm->define(get_class($record), ORMInterface::R_PRIMARY_KEY);
    }
}