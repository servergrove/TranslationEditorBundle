<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Translation\Importer;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

use ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface;

/**
 * XLIFF Importer
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Oleksii Strutsynkyi <cajoy1981@gmail.com>
 */
class XliffImporter extends AbstractImporter implements ImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFileExtension()
    {
        return (version_compare(Kernel::VERSION, '2.1') >= 0) ? 'xlf' : 'xliff';
    }

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
