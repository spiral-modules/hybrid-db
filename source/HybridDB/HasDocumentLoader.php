<?php
/**
 * ORM-to-ODM relation bridge.
 *
 * @author    Wolfy-J
 */

namespace Spiral\HybridDB;

use Spiral\Core\Component;
use Spiral\ODM\ODMInterface;
use Spiral\ORM\Entities\Nodes\AbstractNode;
use Spiral\ORM\Entities\Nodes\SingularNode;
use Spiral\ORM\Exceptions\LoaderException;
use Spiral\ORM\LoaderInterface;
use Spiral\ORM\Record;

/**
 * Provides support for loading relation data from mongo database.
 */
class HasDocumentLoader extends Component implements LoaderInterface
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @invisible
     * @var ODMInterface
     */
    protected $odm;

    /**
     * Parent loader if any.
     *
     * @invisible
     * @var LoaderInterface
     */
    protected $parent;

    /**
     * Loader options, can be altered on RecordSelector level.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Relation schema.
     *
     * @var array
     */
    protected $schema = [];

    /**
     * @param string       $class
     * @param array        $schema Relation schema.
     * @param ODMInterface $odm
     */
    public function __construct(string $class, array $schema, ODMInterface $odm)
    {
        $this->class = $class;
        $this->schema = $schema;
        $this->odm = $odm;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function withContext(LoaderInterface $parent, array $options = []): LoaderInterface
    {
        if (!empty($options)) {
            throw new LoaderException("HasDocumentLoader does not support any options");
        }

        $loader = clone $this;
        $loader->parent = $parent;
        $loader->options = $options + $this->options;

        return $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function createNode(): AbstractNode
    {
        return new SingularNode(
            [/* since columns are empty all document properties will be used */],
            $this->schema[Record::OUTER_KEY],
            $this->schema[Record::INNER_KEY],
            '_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function loadData(AbstractNode $node)
    {
        if (empty($node->getReferences())) {
            return;
        }

        //Loading document data
        $cursor = $this->odm->selector($this->class)->where([
            $this->schema[Record::OUTER_KEY] => ['$in' => $node->getReferences()]
        ])->getProjection();

        //Pushing documents data into data tree
        foreach ($cursor->toArray() as $document) {
            $node->parseRow(0, $document);
        }
    }
}