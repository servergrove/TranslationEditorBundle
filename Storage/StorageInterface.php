<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Storage;

/**
 * StorageInterface
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
interface StorageInterface
{
    /**
     * Retrieve a list of Locales based on search criteria
     *
     * @param array $criteria
     *
     * @return array
     */
    public function findLocaleList(array $criteria = array());

    /**
     * Create a new Locale.
     *
     * @param string $language
     * @param string $country
     *
     * @return \ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface
     */
    public function createLocale($language, $country = null);

    /**
     * Delete an existing Locale
     *
     * @param integer $id
     *
     * @return boolean
     */
    public function deleteLocale($id);

    /**
     * Retrieve a list of Entries based on search criteria
     *
     * @param array $criteria
     *
     * @return array
     */
    public function findEntryList(array $criteria = array());

    /**
     * Create a new Entry.
     *
     * @param string $domain
     * @param string $fileName
     * @param string $alias
     *
     * @return \ServerGrove\Bundle\TranslationEditorBundle\Model\EntryInterface
     */
    public function createEntry($domain, $fileName, $alias);

    /**
     * Delete an existing Entry
     *
     * @param integer $id
     *
     * @return boolean
     */
    public function deleteEntry($id);

    /**
     * Retrieve a list of Translations based on search criteria
     *
     * @param array $criteria
     *
     * @return array
     */
    public function findTranslationList(array $criteria = array());

    /**
     * Create a new Translation.
     *
     * @param \ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface $locale
     * @param \ServerGrove\Bundle\TranslationEditorBundle\Model\EntryInterface $entry
     * @param string $value
     *
     * @return \ServerGrove\Bundle\TranslationEditorBundle\Model\TranslationInterface
     */
    public function createTranslation($locale, $entry, $value);

    /**
     * Delete an existing Translation
     *
     * @param integer $id
     *
     * @return boolean
     */
    public function deleteTranslation($id);

    /**
     * Persist a given entity onstorage
     *
     * @param object $entity
     *
     * @return object
     */
    public function persist($entity);
}