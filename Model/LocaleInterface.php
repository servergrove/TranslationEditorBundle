<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Model;

/**
 * LocaleInterface
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
interface LocaleInterface
{
    /**
     * Retrieve the ISO639-1 language code
     *
     * @return string
     */
    public function getLanguage();

    /**
     * Define the Locale language code
     *
     * @param string $language
     */
    public function setLanguage($language);

    /**
     * Retrieve the ISO3166 country code
     *
     * @return string
     */
    public function getCountry();

    /**
     * Define the Locale country code (optional)
     *
     * @param string $country
     */
    public function setCountry($country = null);

    /**
     * Retrieve the Locale active status
     *
     * @return boolean
     */
    public function getActive();

    /**
     * Define the Locale active status
     *
     * @param boolean $active
     */
    public function setActive($active);

    /**
     * Append a Locale translation
     *
     * @param Translation $translation
     */
    public function addTranslation(Translation $translation);

    /**
     * Retrieve the Locale translations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations();
}