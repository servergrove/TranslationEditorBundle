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
     * Import the locale
     *
     * @param string $language
     * @param string $country
     *
     * @return \ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface
     */
    public function importLocale($language, $country = null);

    /**
     * Import the translation entries.
     *
     * @param \Symfony\Component\HttpKernel\Bundle\Bundle $bundle
     * @param \ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface $locale
     * @param string $filePath
     */
    public function importFile(Bundle $bundle, LocaleInterface $locale, $filePath);
}