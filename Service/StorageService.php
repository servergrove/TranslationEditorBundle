<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;

use ServerGrove\Bundle\TranslationEditorBundle\Storage\StorageInterface;

/**
 * StorageService
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class StorageService
{
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
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     *
     * @return array
     */
    public function getEntryList()
    {
        return $this->storage->getEntryList();
    }
}