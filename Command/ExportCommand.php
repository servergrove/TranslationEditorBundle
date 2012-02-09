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

        $this->output->write('Exporting translation files for');
        foreach ($domains as $domain) {
            $this->exportDomain($domain);
        }
        
        $this->output->writeln('');
        $this->output->writeln('Done');
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
        $domainName = $domain['name'];
        
        $translationTable = array();
        $storageService   = $this->getContainer()->get('server_grove_translation_editor.storage');
        $entries          = $storageService->findEntryList(array('domain' => $domainName));
        
        // Classify all entries by locale.
        foreach ($entries as $entry) {
            $entryId       = $entry->getId();
            $entryAlias    = $entry->getAlias();
            $entryFilename = $entry->getFileName();
            
            // Assign the translation to the translation table
            foreach ($entry->getTranslations() as $translation) {
                $locale  = (string) $translation->getLocale();
                $context = trim($translation->getValue());
                
                $translationTable[$locale][] = array(
                    'filename' => $entryFilename,
                    'id'       => $entryId,
                    'alias'    => $entryAlias,
                    'context'  => $context
                );
            }
        }
        
        // If there is no translation, there will be no need to proceed for this iteration.
        if (empty($translationTable)) {
            $this->output->write(sprintf(' <error>%s</error>', $domainName));
            return;
        }
        
        $this->writeFiles($domain, $translationTable);
        $this->output->write(sprintf(' <info>%s</info>', $domainName));
    }
    
    /**
     * Write a file from the given translation table.
     *
     * @TODO: This is a working prototype. Refactor the code, especially the XLIFF renderer.
     *
     * @param array $translationTable two-dimension table of locale (1D) and trans unit (2D)
     */
    protected function writeFiles($domainPath, array $translationTable)
    {
        $exporter = $this->getContainer()->get('server_grove_translation_editor.exporter');
        
        foreach ($translationTable as $locale => $entries) {
            $exporter->writeFiles($domainPath, $locale, $entries);
        }
    }
}


