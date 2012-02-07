<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;

use ServerGrove\Bundle\TranslationEditorBundle\Storage;

/**
 * StorageService
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class StorageService
{
    static private $STORAGES = array(
        'orm' => 'ServerGrove\Bundle\TranslationEditorBundle\Storage\ORMStorage',
    );

    /**
     * @var ServerGrove\Bundle\TranslationEditorBundle\Storage\StorageInterface
     */
    protected $storage;

    /**
     * Constructor
     *
     * @param string $type
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     */
    public function __construct($type, ObjectManager $objectManager)
    {
        $this->storage = new self::$STORAGES[$type];

        $this->storage->setObjectManager($objectManager);
    }

    public function getTranslations()
    {
        return $this->storage->getTranslations();
    }

    /**
     * Add a new Storage type
     *
     * @param string $type
     * @param string $class
     */
    static public function addStorageType($type, $class)
    {
        self::$STORAGES[$type] = $class;
    }
}