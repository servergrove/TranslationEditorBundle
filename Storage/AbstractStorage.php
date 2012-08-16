<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Storage;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Doctrine ORM Storage
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
abstract class AbstractStorage
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $manager;

    /**
     * Constructor
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function createLocale($language, $country = null)
    {
        $localeClass = $this->getLocaleClassName();
        $locale      = new $localeClass;

        $locale->setLanguage($language);
        $locale->setCountry($country);
        $locale->setActive(true);

        return $this->persist($locale);
    }

    /**
     * {@inheritdoc}
     */
    public function createEntry($domain, $fileName, $format, $alias)
    {
        $entryClass = $this->getEntryClassName();
        $entry      = new $entryClass;

        $entry->setDomain($domain);
        $entry->setFileName($fileName);
        $entry->setFormat($format);
        $entry->setAlias($alias);

        return $this->persist($entry);
    }

    /**
     * {@inheritdoc}
     */
    public function createTranslation($locale, $entry, $value)
    {
        $translationClass = $this->getTranslationClassName();
        $translation      = new $translationClass;

        $translation->setLocale($locale);
        $translation->setEntry($entry);
        $translation->setValue($value);

        return $this->persist($translation);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteLocale($id)
    {
        return $this->delete($this->getLocaleClassName(), $id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteEntry($id)
    {
        return $this->delete($this->getEntryClassName(), $id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteTranslation($id)
    {
        return $this->delete($this->getTranslationClassName(), $id);
    }

    /**
     * {@inheritdoc}
     */
    public function persist($entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();

        return $entity;
    }

    /**
     * Delete an entity based on its identifier.
     *
     * @param string $entityClassName
     * @param integer $id
     *
     * @return boolean
     */
    protected function delete($entityClassName, $id)
    {
        try {
            $entity = $this->manager->find($entityClassName, $id);

            $this->manager->remove($entity);
            $this->manager->flush();

            return true;
        } catch (\Exception $e) {
            // Do nothing
        }

        return false;
    }

    /**
     * Retrieve the Locale class name.
     *
     * @return string
     */
    abstract protected function getLocaleClassName();

    /**
     * Retrieve the Entry class name.
     *
     * @return string
     */
    abstract protected function getEntryClassName();

    /**
     * Retrieve the Translation class name.
     *
     * @return string
     */
    abstract protected function getTranslationClassName();
}