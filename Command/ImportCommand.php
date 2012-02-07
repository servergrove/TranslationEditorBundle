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
     * Import a specific domain locale translation file
     *
     * @param array $domain
     * @param array $translationFile
     * @param \ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface $locale
     */
    protected function importFile($domain, $translationFile, $locale)
    {

    }

    /**
     * Get an existed locale or create and return a new locale.
     * 
     * @return \ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface
     */
    protected function getOrCreateLocale($language, $country)
    {
        $storageService = $this->getContainer()->get('server_grove_translation_editor.storage');

        if (($locale = $storageService->findLocale($language, $country)) !== null) {
            return $locale;
        }

        return $storageService->createLocale($language, $country);
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
        list($language, $country) = preg_split('/(-|_)/', $locale, 2);

        // Fix the inconsistency in naming convention.
        $language  = strtolower($language);
        $country   = strtoupper($country);
        $type      = strtolower($type);

        return array($name, $language, $country, $type);
    }

    protected function importXliff($domain, $filename, $name, $locale)
    {
        $fileContent = file_get_contents($filename);

        // Load all trans-unit blocks.
        $xliff = simplexml_load_file($fileContent);
        $units = $xliff->file->body->children();


        // Load the data from the storage service.
        $entries = $this->getContainer()
           ->get('server_grove_translation_editor.storage')
           ->getEntryList(array('locale'=>$locale));

        $this->output->writeln(sprintf('  Found entries: %d', count($entries)));

        // Create an entry if we don't have one.
        if ( ! $entries) {
            $creationInfo = array(
                'domain'   => $domain,
                'filename' => $filename,
                'locale'   => $locale
            );
            foreach ($units as $unit) {
                $this->updateTranslation(null, $unit, $creationInfo);
            }
        }

        // Add or override the translation for all domains.
        foreach ($entries as $entry) {
            foreach ($units as $unit) {
                $this->updateTranslation($entry, $unit);
            }
            // @TODO: find the way to persist the entry.
        }

        // @TODO: flush the transaction.
    }

    /**
     * Update or add the translation.
     *
     * @param \ServerGrove\Bundle\TranslationEditorBundle\Model\EntryInterface|null $entry
     * @param \SimpleXMLElement $unit
     * @param array $creationInfo
     */
    protected function updateTranslation($entry, \SimpleXMLElement $unit, $creationInfo=null)
    {
        $alias = (string) $unit->source;
        $value = (string) $unit->target;

        if ( ! $entry) {
            $entry = null; // @TODO create Translation object.
            $entry->setDomain($creationInfo['domain']);
            $entry->setFileName($creationInfo['filename']);
            $entry->setAlias($alias);
        }

        // If there exists a translation for this alias, override it.
        if ($entry->getTranslations()->containsValue($value)) {
            $entry->getTranslations()->set($alias, $value);
            return;
        }

        // Otherwise, add this translation.
        $translation = null; // @TODO create Translation object.
        $translation->setEntry($entry);
        $translation->setValue($value);
        $translation->setLocale($creationInfo['locale']);

        $entry->addTranslation($translation);
    }

    /**
     * Import from a YAML file.
     *
     * @param string $filename
     * @param string $name
     * @param string $locale
     */
    protected function importYaml($fileContent, $name, $locale)
    {
        throw \Exception('Code not updated');

        $this->setMongoIndexes();

        $type = 'yml';

        $yaml = new Parser();
        $value = $yaml->parse($fileContent);

        $data = $this->getContainer()
            ->get('server_grove_translation_editor.storage_manager')
            ->getCollection()
            ->findOne(array('filename'=>$name));

        if (!$data) {
            $data = array(
                'filename' => $name,
                'locale' => $locale,
                'type' => $type,
                'entries' => array(),
            );

        }

        $this->output->writeln("  Found ".count($value)." entries...");
        $data['entries'] = $value;

        if (!$this->input->getOption('dry-run')) {
            $this->updateValue($data);
        }
    }

    protected function setMongoIndexes()
    {
        $collection = $this->getContainer()->get('server_grove_translation_editor.storage_manager')->getCollection();
        $collection->ensureIndex( array( "filename" => 1, 'locale' => 1 ) );
    }

    protected function updateValue($data)
    {
        $collection = $collection = $this->getContainer()->get('server_grove_translation_editor.storage_manager')->getCollection();

        $criteria = array(
            'filename' => $data['filename'],
        );

        $mdata = array(
            '$set' => $data,
        );

        return $collection->update($criteria, $data, array('upsert' => true));
    }

}


