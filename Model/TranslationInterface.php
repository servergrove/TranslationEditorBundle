<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Model;

/**
 * TranslationInterface
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
interface TranslationInterface
{
    /**
     * Retrieve the Translation entry
     *
     * @return EntryInterface
     */
    public function getEntry();

    /**
     * Define the Translation entry
     *
     * @param EntryInterface $entry
     */
    public function setEntry(EntryInterface $entry);

    /**
     * Retrieve the Translation locale
     *
     * @return LocaleInterface
     */
    public function getLocale();

    /**
     * Define the Translation locale
     *
     * @param LocaleInterface $locale
     */
    public function setLocale(LocaleInterface $locale);

    /**
     * Retrieve the Translation value
     *
     * @return string
     */
    public function getValue();

    /**
     * Define the Translation value
     *
     * @param string $value
     */
    public function setValue($value);
}