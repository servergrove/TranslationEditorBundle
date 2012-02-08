<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
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
        $domains    = $this->getDomainList($kernel);
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
     * Retrieve the list of registered domains.
     *
     * @param \Symfony\Component\HttpKernel\Kernel $kernel
     *
     * @return array
     */
    protected function getDomainList($kernel)
    {
        $srcRootDirectory = realpath($kernel->getRootDir() . '/../src');
        $domains          = array();

        $bundles = array_filter(
            $kernel->getBundles(),
            function ($bundle) use ($srcRootDirectory) {
                return (strpos($bundle->getPath(), $srcRootDirectory) === 0);
            }
        );

        foreach ($bundles as $bundle) {
            $domains[] = array(
                'name'  => $bundle->getName(),
                'path'  => $bundle->getPath(),
                'files' => $this->getTranslationFileList($bundle)
            );
        }

        return $domains;
    }

    /**
     * Retrieve the list of translation files of a given Bundle
     *
     * @param \Symfony\Component\HttpKernel\Bundle\Bundle $bundle
     *
     * @return array
     */
    protected function getTranslationFileList($bundle)
    {
        $translationPath = $bundle->getPath() . '/Resources/translations';
        $translationFiles = array();

        if ( ! file_exists($translationPath)) {
            return $translationFiles;
        }

        $finder = new Finder();
        $finder->files()->in($translationPath)->name('*');

        foreach ($finder as $translationFile) {
            $this->output->writeln(sprintf('Found <info>%s</info>...', $translationFile->getRealpath()));

            list($name, $language, $country, $type) = $this->extractNameLocaleType($translationFile);

            $translationFiles[] = array(
                'path'     => $translationFile->getRealPath(),
                'name'     => $name,
                'language' => $language,
                'country'  => $country,
                'type'     => $type
            );
        }

        return $translationFiles;
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
     * Get an existed locale or create and return a new locale.
     *
     * @return \ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface
     */
    protected function getOrCreateLocale($language, $country)
    {
        $storageService = $this->getContainer()->get('server_grove_translation_editor.storage');
        $localeList     = $storageService->findLocaleList(array(
            'language' => $language,
            'country'  => $country
        ));

        return (count($localeList) === 1)
            ? reset($localeList)
            : $storageService->createLocale($language, $country);
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
     * Get an existed entry or create and return a new entry.
     *
     * @return \ServerGrove\Bundle\TranslationEditorBundle\Model\EntryInterface
     */
    protected function getOrCreateEntry($alias, $domain, $fileName, $entries)
    {
        $storageService  = $this->getContainer()->get('server_grove_translation_editor.storage');
        $entryCollection = array_filter(
            $entries,
            function ($entry) use ($alias) {
                return ($alias === $entry->getAlias());
            }
        );

        return (count($entryCollection) > 0)
            ? array_shift($entryCollection)
            : $storageService->createEntry($domain, $fileName, $alias);
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