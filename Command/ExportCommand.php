<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for exporting translations into files
 */

class ExportCommand extends Base
{
    protected $cdataPattern = '/[<>]/';

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('locale:editor:export')
            ->setDescription('Export translations into files');

    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $this->output->writeln('Scanning register bundles...');

        $kernel  = $this->getContainer()->get('kernel');
        $domains = $this->getDomainList($kernel, false);

        foreach ($domains as $domain) {
            $this->exportDomain($domain['name']);
        }
    }
    
    /**
     * Export the given domain.
     *
     * @TODO: This is a working prototype. Refactor the code.
     * 
     * @param array $domain
     */
    protected function exportDomain($domain)
    {
        $this->output->writeln(sprintf('Exporting <info>%s</info>', $domain));
        
        $translations   = array();
        $storageService = $this->getContainer()->get('server_grove_translation_editor.storage');
        $entries        = $storageService->findEntryList(array('domain' => $domain));
        
        // Classify all entries by locale.
        foreach ($entries as $entry) {
            $entryId    = $entry->getId();
            $entryAlias = $entry->getAlias();
            
            foreach ($entry->getTranslations() as $translation) {
                $locale  = (string) $translation->getLocale();
                $context = trim($translation->getValue());
                
                if (!isset($translations[$locale])) {
                    $translations[$locale] = array();
                }
                
                $translations[$locale][] = array(
                    'id'      => $entryId,
                    'alias'   => $entryAlias,
                    'context' => preg_match($this->cdataPattern, $context)
                        ? $unit = sprintf("<![CDATA[%s]]>", $context)
                        : $context
                );
            }
        }
        
        $this->writeFile($translations);
    }
    
    /**
     * Write a file from the given translation table.
     *
     * @TODO: This is a working prototype. Refactor the code, especially the XLIFF renderer.
     *
     * @param array $translationTable two-dimension table of locale (1D) and trans unit (2D)
     */
    protected function writeFile(array $translationTable)
    {
        // XLIFF file template requires two variables: locale and trans units (in this order).
        $xliffFileTemplate = implode("\n", array(
            '<?xml version="1.0"?>',
            '<xliff version="1.2"',
                   'xmlns="urn:oasis:names:tc:xliff:document:1.2"',
                   'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
                   'xsi:schemaLocation="urn:oasis:names:tc:xliff:document:1.2 ../../../../../../../../app/Resources/translations/xliff-core-1.2-strict.xsd.xml">',
                '<file source-language="%s" datatype="plaintext" original="file.ext">',
                    '<body>%s</body>',
                '</file>',
            '</xliff>'
        ));
        
        // XLIFF file template requires two variables: id, alias and context (in this order).
        $xliffTransUnitTemplate = implode("\n", array(
            '<trans-unit id="%d">',
                '<source>%s</source>',
                '<target>%s</target>',
            '</trans-unit>',
        ));
        
        foreach ($translationTable as $locale => $entries)
        {
            $renderedTransUnits = array();
            
            // Render only a trans unit per entry.
            foreach ($entries as $entry) {
                $renderedTransUnits[] = sprintf(
                    $xliffTransUnitTemplate, $entry['id'], $entry['alias'], $entry['context']
                );
            }
            
            // Render the whole XLIFF file.
            $renderedXliffFile = sprintf(
                $xliffFileTemplate, $locale, implode("\n", $renderedTransUnits)
            );
            
            // @TODO: Write a file out.
        }
    }
}


