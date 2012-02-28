<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Translation\Importer;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface;

/**
 * ImporterInterface
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
interface ImporterInterface
{
    /**
     * Import the translation entries.
     *
     * @param \Symfony\Component\HttpKernel\Bundle\Bundle $bundle
     * @param \ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface $locale
     * @param string $filePath
     */
    public function importFile(Bundle $bundle, LocaleInterface $locale, $filePath);

    /**
     * Return true if this importer can load this file
     *
     * @abstract
     * @param $filePath
     *
     * @return boolean
     */
    public function supports($filePath);
}