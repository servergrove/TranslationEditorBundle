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
 * @author Juti Noppornpitak <jnopporn@shiroyuki.com>
 */

class ImportCommand extends Base
{

    protected function configure()
    {
        parent::configure();

        $this
        ->setName('locale:editor:import')
        ->setDescription('Import translation files into MongoDB for using through /translations/editor')
        ->addArgument('filename')
        ->addOption("dry-run")
        ;

    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $filename = $input->getArgument('filename');

        $files = array();

        if (!empty($filename) && is_dir($filename)) {
            $output->writeln("Importing translations from <info>$filename</info>...");
            $finder = new Finder();
            $finder->files()->in($filename)->name('*');

            foreach ($finder as $file) {
                $output->writeln("Found <info>".$file->getRealpath()."</info>...");
                $files[] = $file->getRealpath();
            }

        } else {
            $dir = $this->getContainer()->getParameter('kernel.root_dir').'/../src';

            $output->writeln("Scanning ".$dir."...");
            $finder = new Finder();
            $finder->directories()->in($dir)->name('translations');

            foreach ($finder as $dir) {
                $finder2 = new Finder();
                $finder2->files()->in($dir->getRealpath())->name('*');
                foreach ($finder2 as $file) {
                    $output->writeln("Found <info>".$file->getRealpath()."</info>...");
                    $files[] = $file->getRealpath();
                }
            }
        }

        if (!count($files)) {
            $output->writeln("<error>No files found.</error>");
            return;
        }
        $output->writeln(sprintf("Found %d files, importing...", count($files)));
        
        foreach($files as $filename) {
            $this->import($filename);
        }

    }

    public function import($domain, $filename)
    {
        $this->output->writeln("Processing <info>".$filename."</info>...");
        
        list($name, $language, $country, $type) = $this->extractNameLocaleType($filename);
        
        $locale = $this->getLocale($language, $country);
        
        switch($type) {
            case 'xliff':
                $this->importXliff($domain, $filename, $name, $locale);
                break;
        }
    }
    
    /**
     * @return \ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface
     */
    protected function getLocale($language, $country)
    {
        // @TODO implement the service to get the locale. if we can't find one, create it.
    }
    
    protected function extractNameLocaleType($filename)
    {
        // Gather information for re-assembly.
        list($name, $locale, $type) = explode('.', basename($fname));
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


