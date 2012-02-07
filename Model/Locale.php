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
     * {{@inheritdoc}}
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * {{@inheritdoc}}
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * {{@inheritdoc}}
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * {{@inheritdoc}}
     */
    public function setCountry($country = null)
    {
        $this->country = $country;
    }

    /**
     * {{@inheritdoc}}
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * {{@inheritdoc}}
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * {{@inheritdoc}}
     */
    public function addTranslation(Translation $translation)
    {
        if ( ! $translation->getLocale() instanceof self) {
            $this->translations[] = $translation;
        }
    }

    /**
     * {{@inheritdoc}}
     */
    public function getTranslations()
    {
        return $this->translations;
    }
}