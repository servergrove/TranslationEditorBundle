<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Storage agnostic Entry entity
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
abstract class Entry implements EntryInterface
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var string
     */
    protected $alias;

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
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * {{@inheritdoc}}
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * {{@inheritdoc}}
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * {{@inheritdoc}}
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * {{@inheritdoc}}
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * {{@inheritdoc}}
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * {{@inheritdoc}}
     */
    public function addTranslation(Translation $translation)
    {
        if ( ! $translation->getEntry() instanceof self) {
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