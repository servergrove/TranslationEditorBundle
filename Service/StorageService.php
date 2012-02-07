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
     * Retrieve the list of registered Locales
     *
     * @param boolean $onlyActive
     *
     * @return array
     */
    public function getLocaleList($onlyActive = true)
    {
        return $this->storage->getLocaleList($onlyActive);
    }

    public function findLocale($language, $country)
    {
        $criteria = array(
            'language' => $language,
            'country'  => $country
        );

        return $this->storage->findLocale($criteria);
    }

    /**
     * Retrieve the list of registered Entries
     *
     * @return array
     */
    public function getEntryList()
    {
        return $this->storage->getEntryList();
    }
}