<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Translation\Importer;

use Symfony\Component\HttpKernel\Bundle\Bundle,
    Symfony\Component\DependencyInjection\ContainerAware;

use ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface,
    ServerGrove\Bundle\TranslationEditorBundle\Model\EntryInterface;

/**
 * AbstractImporter
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
abstract class AbstractImporter extends ContainerAware
{
    /**
     * {{@inheritdoc}}
     */
    public function importLocale($language, $country = null)
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $localeList     = $storageService->findLocaleList(array(
            'language' => $language,
            'country'  => $country
        ));

        return (count($localeList) === 1)
            ? reset($localeList)
            : $storageService->createLocale($language, $country);
    }

    /**
     * Import an Entry
     *
     * @param string $domain
     * @param string $fileName
     * @param string $alias
     * @param array $entryList
     *
     * @return \ServerGrove\Bundle\TranslationEditorBundle\Model\EntryInterface
     */
    protected function importEntry($domain, $fileName, $alias, $entryList)
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $entryList      = array_filter(
            $entryList,
            function ($entry) use ($alias) {
                return ($alias === $entry->getAlias());
            }
        );

        return (count($entryList) > 0)
            ? array_shift($entryList)
            : $storageService->createEntry($domain, $fileName, $alias);
    }

    /**
     * Import a Translation
     *
     * @param \ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface $locale
     * @param \ServerGrove\Bundle\TranslationEditorBundle\Model\EntryInterface $entry
     * @param string $value
     *
     * @return \ServerGrove\Bundle\TranslationEditorBundle\Model\TranslationInterface
     */
    protected function importTranslation(LocaleInterface $locale, EntryInterface $entry, $value)
    {
        $storageService  = $this->container->get('server_grove_translation_editor.storage');
        $translationList = $entry->getTranslations()->filter(
            function ($translation) use ($locale) {
                return ($translation->getLocale() === $locale);
            }
        );

        if ( ! count($translationList)) {
            $storageService->createTranslation($locale, $entry, $value);
        }
    }
}