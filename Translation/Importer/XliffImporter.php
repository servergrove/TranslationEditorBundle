<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Translation\Importer;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface;

/**
 * XLIFF Importer
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class XliffImporter extends AbstractImporter implements ImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function importFile(Bundle $bundle, LocaleInterface $locale, $filePath)
    {
        // Extracting information
        $bundleName = $bundle->getName();
        $fileParts  = explode('.', basename($filePath));
        $fileName   = $fileParts[0];

        // Loading data
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $entryList      =  $storageService->findEntryList(array(
            'domain'   => $bundleName,
            'fileName' => $fileName
        ));

        // Loading file
        $xliff        = simplexml_load_file($filePath);
        $xliffEntries = $xliff->file->body->children();

        foreach ($xliffEntries as $xliffEntry) {
            $alias = (string) $xliffEntry->source;
            $value = (string) $xliffEntry->target;

            $entry = $this->importEntry($bundleName, $fileName, $alias, $entryList);

            $this->importTranslation($locale, $entry, $value);
        }
    }
}