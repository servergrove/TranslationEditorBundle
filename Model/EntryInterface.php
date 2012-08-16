<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Model;

/**
 * EntryInterface
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
interface EntryInterface
{
    /**
     * Retrieve the Entry domain
     *
     * @return string
     */
    public function getDomain();

    /**
     * Define the Entry domain
     *
     * @param string $domain
     */
    public function setDomain($domain);

    /**
     * Retrieve the Entry file name
     *
     * @return string
     */
    public function getFileName();

    /**
     * Define the Entry file name
     *
     * @param string $fileName
     */
    public function setFileName($fileName);

    /**
     * Retrieve the Entry format
     *
     * @return string
     */
    public function getFormat();

    /**
     * Define the Entry format
     *
     * @param string $format
     */
    public function setFormat($format);

    /**
     * Retrieve the Entry alias
     *
     * @return string
     */
    public function getAlias();

    /**
     * Define the Entry alias
     *
     * @param string $alias
     */
    public function setAlias($alias);

    /**
     * Append an Entry translation
     *
     * @param TranslationInterface $translation
     */
    public function addTranslation(TranslationInterface $translation);

    /**
     * Retrieve the Entry translations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations();
}