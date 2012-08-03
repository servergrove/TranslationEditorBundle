<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Translation\Importer;

use ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface;
use ServerGrove\Bundle\TranslationEditorBundle\Storage\StorageInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;


/**
 * @author Catalin Costache
 */
class DelegatingImporter implements ImporterInterface
{
    protected $storage;
    protected $importers = array();

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function addImporter(ImporterInterface $importer)
    {
        $this->importers[] = $importer;
    }

    /**
     * {@inheritdoc}
     */
    public function importLocale($language, $country = null)
    {
        $localeList     = $this->storage->findLocaleList(array(
            'language' => $language,
            'country'  => $country
        ));

        return (count($localeList) === 1)
            ? reset($localeList)
            : $this->storage->createLocale($language, $country);
    }

    /**
     * {@inheritdoc}
     */
    public function importFile(Bundle $bundle, LocaleInterface $locale, $filePath)
    {
        foreach ($this->importers as $importer) {
            if ($importer->supports($filePath)) {
                $importer->importFile($bundle, $locale, $filePath);
                continue;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFileExtension()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($filePath)
    {
        return true;
    }
}
