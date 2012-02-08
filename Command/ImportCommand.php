<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ServerGrove\Bundle\TranslationEditorBundle\Model\EntryInterface;
use ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Command for importing translation files
 *
 * Additional authors:
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Juti Noppornpitak <jnopporn@shiroyuki.com>
 */

class ImportCommand extends Base
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('locale:editor:import')
            ->setDescription('Import translation files into MongoDB for using through /translations/editor');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $this->output->writeln('Scanning register bundles...');

        $kernel     = $this->getContainer()->get('kernel');
        $domains    = $this->getDomainList($kernel, true);
        $filesCount = array_sum(
            array_map(
                function ($domain) {
                    return count($domain['files']);
                },
                $domains
            )
        );

        if ( ! $filesCount) {
            $this->output->writeln("<error>No files found.</error>");

            return;
        }

        $this->output->writeln(sprintf("Found %d files, importing...", $filesCount));

        foreach ($domains as $domain) {
            $this->importDomain($domain);
        }

    }

    /**
     * Import translation files of a given domain.
     *
     * @param array $domain
     */
    protected function importDomain($domain)
    {
        foreach ($domain['files'] as $translationFile) {
            $this->output->writeln(sprintf('Processing <info>%s</info>...', $translationFile['path']));

            $locale = $this->getOrCreateLocale($translationFile['language'], $translationFile['country']);

            $this->importFile($domain, $translationFile, $locale);
        }
    }

    /**
     * Import a specific domain locale translation file
     *
     * @param array $domain
     * @param array $translationFile
     * @param \ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface $locale
     */
    protected function importFile($domain, $translationFile, $locale)
    {
        $xliff        = simplexml_load_file($translationFile['path']);
        $xliffEntries = $xliff->file->body->children();

        $storageService = $this->getContainer()->get('server_grove_translation_editor.storage');
        $entries        =  $storageService->findEntryList(array(
            'domain'   => $domain['name'],
            'fileName' => $translationFile['name']
        ));

        foreach ($xliffEntries as $xliffEntry) {
            $entry = $this->getOrCreateEntry(
                (string) $xliffEntry->source,
                $domain['name'],
                $translationFile['name'],
                $entries
            );

            $translations = $entry->getTranslations()->filter(
                function ($translation) use ($locale) {
                    return ($translation->getLocale() === $locale);
                }
            );

            if ( ! count($translations)) {
                $storageService->createTranslation($locale, $entry, (string) $xliffEntry->target);
            }
        }

    }

    /**
     * Extract file information
     *
     * @param string $filename
     *
     * @return array
     */
    protected function extractNameLocaleType($filename)
    {
        // Gather information for re-assembly.
        list($name, $locale, $type) = explode('.', basename($filename));

        $localeParts = preg_split('/(-|_)/', $locale, 2);

        // Fix the inconsistency in naming convention.
        $language = strtolower($localeParts[0]);
        $country  = (isset($localeParts[1])) ? strtoupper($localeParts[1]) : null;
        $type     = strtolower($type);

        return array($name, $language, $country, $type);
    }
}