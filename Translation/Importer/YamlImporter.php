<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Translation\Importer;

use Symfony\Component\HttpKernel\Bundle\Bundle,
    Symfony\Component\Yaml\Parser;

use ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface;

/**
 * XLIFF Importer
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class YamlImporter extends AbstractImporter implements ImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFileExtension()
    {
        return 'yml';
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
        $yaml        = new Parser();
        $yamlEntries = $yaml->parse(file_get_contents($filePath));

        foreach ($yamlEntries as $alias => $value) {
            $entry = $this->importEntry($bundleName, $fileName, $alias, $entryList);

            $this->importTranslation($locale, $entry, $value);
        }
    }
}