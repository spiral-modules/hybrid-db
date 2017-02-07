<?php
/**
 * hybrid-db
 *
 * @author    Wolfy-J
 */

namespace Spiral\Tests\HybridDB;

use PHPUnit\Framework\TestCase;
use Spiral\Core\Container;
use Spiral\Database\Configs\DatabasesConfig;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Drivers\MySQL\MySQLDriver;
use Spiral\HybridDB;
use Spiral\ODM\Accessors as ODMAccessors;
use Spiral\ODM\Configs\MongoConfig;
use Spiral\ODM\Configs\MutatorsConfig as ODMMutatorsConfig;
use Spiral\ODM\ODM;
use Spiral\ODM\ODMInterface;
use Spiral\ORM\Configs\MutatorsConfig as ORMMutatorsConfig;
use Spiral\ORM\Configs\RelationsConfig;
use Spiral\ORM\Entities\Loaders;
use Spiral\ORM\Entities\Relations;
use Spiral\ORM\ORM;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Record;
use Spiral\ORM\Schemas;
use Spiral\Tests\HybridDB\Fixtures\Metadata;
use Spiral\Tests\HybridDB\Fixtures\Photo;
use Spiral\Tokenizer\ClassesInterface;
use Spiral\Tokenizer\ClassLocator;
use Spiral\Tokenizer\Configs\TokenizerConfig;
use Spiral\Tokenizer\Tokenizer;
use Spiral\Tokenizer\TokenizerInterface;

abstract class BaseTest extends TestCase
{
    const DATABASE_CONFIG = [
        'default' => 'primary',

        'databases'   => [
            'primary' => [
                'connection'  => 'mysql',
                'tablePrefix' => '',
            ],

        ],
        'connections' => [
            'mysql' => [
                'driver'     => MySQLDriver::class,
                'connection' => 'mysql:host=127.0.0.1;dbname=spiral',
                'profiling'  => false,
                'username'   => 'root',
                'password'   => '',
                'options'    => []
            ],
        ]
    ];

    const MUTATORS_CONFIG = [
        'mutators' => [
            'php:int'    => ['setter' => 'intval', 'getter' => 'intval'],
            'php:float'  => ['setter' => 'floatval', 'getter' => 'floatval'],
            'php:string' => ['setter' => 'strval'],
            'php:bool'   => ['setter' => 'boolval', 'getter' => 'boolval'],
        ],
    ];

    const RELATIONS_CONFIG = [
        Record::BELONGS_TO         => [
            RelationsConfig::SCHEMA_CLASS => Schemas\Relations\BelongsToSchema::class,
            RelationsConfig::LOADER_CLASS => Loaders\BelongsToLoader::class,
            RelationsConfig::ACCESS_CLASS => Relations\BelongsToRelation::class
        ],
        Record::HAS_ONE            => [
            RelationsConfig::SCHEMA_CLASS => Schemas\Relations\HasOneSchema::class,
            RelationsConfig::LOADER_CLASS => Loaders\HasOneLoader::class,
            RelationsConfig::ACCESS_CLASS => Relations\HasOneRelation::class
        ],
        Record::HAS_MANY           => [
            RelationsConfig::SCHEMA_CLASS => Schemas\Relations\HasManySchema::class,
            RelationsConfig::LOADER_CLASS => Loaders\HasManyLoader::class,
            RelationsConfig::ACCESS_CLASS => Relations\HasManyRelation::class
        ],
        Record::MANY_TO_MANY       => [
            RelationsConfig::SCHEMA_CLASS => Schemas\Relations\ManyToManySchema::class,
            RelationsConfig::LOADER_CLASS => Loaders\ManyToManyLoader::class,
            RelationsConfig::ACCESS_CLASS => Relations\ManyToManyRelation::class
        ],
        Record::BELONGS_TO_MORPHED => [
            RelationsConfig::SCHEMA_CLASS => Schemas\Relations\BelongsToMorphedSchema::class,
            RelationsConfig::ACCESS_CLASS => Relations\BelongsToMorphedRelation::class
        ],
        Record::MANY_TO_MORPHED    => [
            RelationsConfig::SCHEMA_CLASS => Schemas\Relations\ManyToMorphedSchema::class,
            RelationsConfig::ACCESS_CLASS => Relations\ManyToMorphedRelation::class
        ],
        \Spiral\ODM\Document::ONE  => [
            RelationsConfig::SCHEMA_CLASS => HybridDB\HasDocumentSchema::class,
            RelationsConfig::LOADER_CLASS => HybridDB\HasDocumentLoader::class,
            RelationsConfig::ACCESS_CLASS => HybridDB\HasDocumentRelation::class,
        ],
    ];

    const TOKENIZER_CONFIG = [
        'directories' => [
            __DIR__ . '/Fixtures/'
        ],
        'exclude'     => [

        ]
    ];

    const ODM_MUTATORS_CONFIG = [
        /*
    * Set of mutators to be applied for specific field types.
    */
        'mutators' => [
            'int'      => ['setter' => 'intval'],
            'float'    => ['setter' => 'floatval'],
            'string'   => ['setter' => 'strval'],
            'bool'     => ['setter' => 'boolval'],

            //Automatic casting of mongoID
            'ObjectID' => ['setter' => [ODM::class, 'mongoID']],

            'array::string'    => ['accessor' => ODMAccessors\StringArray::class],
            'array::objectIDs' => ['accessor' => ODMAccessors\ObjectIDsArray::class],
            'array::integer'   => ['accessor' => ODMAccessors\IntegerArray::class],
            /*{{mutators}}*/
        ],
        /*
         * Mutator aliases can be used to declare custom getter and setter filter methods.
         */
        'aliases'  => [
            //Id aliases
            'MongoId'                        => 'ObjectID',
            'objectID'                       => 'ObjectID',
            \MongoDB\BSON\ObjectID::class    => 'ObjectID',

            //Timestamps
            \MongoDB\BSON\UTCDateTime::class => 'timestamp',

            //Scalar typ aliases
            'integer'                        => 'int',
            'long'                           => 'int',
            'text'                           => 'string',

            //Array aliases
            'array::int'                     => 'array::integer',
            'array::MongoId'                 => 'array::objectIDs',
            'array::ObjectID'                => 'array::objectIDs',
            'array::MongoDB\BSON\ObjectID'   => 'array::objectIDs'

            /*{{mutators.aliases}}*/
        ]
    ];

    const MONGO_CONFIG = [
        'default' => 'default',

        'databases' => [
            'default' => [
                'server'   => 'mongodb://localhost:27017',
                'database' => 'spiral',
                'options'  => ['connect' => true]
            ],
        ]
    ];

    /**
     * @var \Spiral\Core\Container
     */
    protected $container;

    /**
     * @var \Spiral\Database\DatabaseManager
     */
    protected $dbal;

    /**
     * @var ODM
     */
    protected $odm;

    /**
     * @var ORM
     */
    protected $orm;

    public function setUp()
    {
        $this->container = new Container();

        $this->configureConfigs($this->container);

        //Tokenizer env
        $this->container->bind(TokenizerInterface::class, Tokenizer::class);
        $this->container->bind(ClassesInterface::class, ClassLocator::class);

        $this->container->bind(ORMInterface::class, ORM::class);
        $this->container->bind(ODMInterface::class, ODM::class);

        $this->container->bind(
            \Spiral\ODM\Schemas\LocatorInterface::class,
            \Spiral\ODM\Schemas\SchemaLocator::class
        );

        $this->container->bind(
            \Spiral\ORM\Schemas\LocatorInterface::class,
            \Spiral\ORM\Schemas\SchemaLocator::class
        );

        $this->dbal = $this->container->get(DatabaseManager::class);
        $this->orm = $this->container->get(ORMInterface::class);
        $this->odm = $this->container->get(ODMInterface::class);

        $builder = $this->odm->schemaBuilder(true);

        $this->odm->setSchema($builder);

        $builder = $this->orm->schemaBuilder(true);

        //Syncing with database
        $builder->renderSchema();
        $builder->pushSchema();

        $this->orm->setSchema($builder);
    }

    public function tearDown()
    {
        //Cleanup databases
        foreach ($this->orm->selector(Photo::class) as $record) {
            $record->delete();
        }

        foreach ($this->odm->selector(Metadata::class) as $document) {
            $document->delete();
        }

        $this->container = null;
        $this->orm = null;
        $this->odm = null;
        $this->dbal = null;
    }

    private function configureConfigs(Container $container)
    {
        $this->container->bind(
            TokenizerConfig::class,
            new TokenizerConfig(self::TOKENIZER_CONFIG)
        );

        $this->container->bind(
            DatabasesConfig::class,
            new DatabasesConfig(self::DATABASE_CONFIG)
        );

        $this->container->bind(
            MongoConfig::class,
            new MongoConfig(self::MONGO_CONFIG)
        );

        $this->container->bind(
            RelationsConfig::class,
            new RelationsConfig(self::RELATIONS_CONFIG)
        );

        $this->container->bind(
            ORMMutatorsConfig::class,
            new ORMMutatorsConfig(self::MUTATORS_CONFIG)
        );

        $this->container->bind(
            ODMMutatorsConfig::class,
            new ODMMutatorsConfig(self::ODM_MUTATORS_CONFIG)
        );
    }
}