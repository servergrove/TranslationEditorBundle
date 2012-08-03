<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Storage agnostic Locale entity
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
abstract class Locale implements LocaleInterface
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $language;

    /**
     * @var string
     */
    protected $country;

    /**
     * @var boolean
     */
    protected $active;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $translations;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->active       = true;
    }

    /**
     * Retrieve Entry identifier
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * {@inheritdoc}
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * {@inheritdoc}
     */
    public function setCountry($country = null)
    {
        $this->country = $country;
    }

    /**
     * {@inheritdoc}
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * {@inheritdoc}
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * {@inheritdoc}
     */
    public function addTranslation(TranslationInterface $translation)
    {
        if ( ! $translation->getLocale() instanceof self) {
            $this->translations[] = $translation;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Retrieve a Translation of a given Entry
     *
     * @param Locale $locale
     *
     * @return Translation
     */
    public function getTranslation($entry)
    {
        foreach ($this->getTranslations() as $translation) {
            if ($translation->getEntry() === $entry) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * Check if a given locale equals to this Locale instance.
     * Accepts either a string or a Locale instance for comparison.
     *
     * @param mixed $locale
     *
     * @return boolean
     */
    public function equalsTo($locale)
    {
        if ($locale instanceof self) {
            return ($this === $locale);
        }

        $locale = str_replace('-', '_', $locale);

        return ((string) $this === $locale);
    }

    /**
     * Convert Locale to string
     *
     * @return string
     */
    public function __toString()
    {
        $locale = $this->getLanguage();

        if (($country = $this->getCountry()) !== null) {
            $locale .= '_' . $country;
        }

        return $locale;
    }
}