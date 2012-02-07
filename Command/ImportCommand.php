<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

use ServerGrove\Entity\Entry;
use ServerGrove\Entity\Locale;
use ServerGrove\Entity\Translation;
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

    public function import($filename)
    {
        $this->output->writeln("Processing <info>".$filename."</info>...");
        
        $fileContent = file_get_contents($filename);
        
        list($name, $locale, $type) = $this->extractNameLocaleType($filename);
        
        switch($type) {
            case 'yml':
            case 'yaml':
                $this->importYaml($fileContent, $filename, $locale);
                break;
            case 'xliff':
                $this->importXliff($fileContent, $name, $locale);
                break;
        }
    }
    
    protected function extractNameLocaleType($filename) {
        // Gather information for re-assembly.
        list($name, $locale, $type) = explode('.', basename($fname));
        list($language, $territory) = preg_split('/(-|_)/', $locale, 2);
        
        // Fix the inconsistency in naming convention.
        $language  = strtolower($language);
        $territory = strtoupper($territory);
        $type      = strtolower($type);
        
        // Re-assemble the locale ID.
        $locale = sprintf('%s-%s', $language, $territory);
        
        return array($name, $locale, $type);
    }
    
    protected function importXliff($fileContent, $name, $locale)
    {
        // Load all trans-unit blocks.
        $xliff = simplexml_load_file($fileContent);
        $units = $xliff->file->body->children();
        
        // Load the data from the storage service.
        $entries = $this->getContainer()
           ->get('server_grove_translation_editor.storage')
           ->getEntryList(array('locale'=>$locale));
        
        $this->output->writeln(sprintf('  Found entries: %d', count($entries)));
        
        // @TODO: what if there are no entries?
        // @TODO: search for a target locale or create a new locale.
        
        // Add or override the translation for all domains.
        foreach ($entries as $entry) {
            $currentDomain = $entry->getDomain();
            
            foreach ($units as $unit) {
                $alias = (string) $unit->source;
                $value = (string) $unit->target;
                
                // If there exists a translation for this alias, override it.
                if ($entry->getTranslations()->containsKey($alias)) {
                    $entry->getTranslations()->set($alias, $value);
                    continue;
                }
                
                // Otherwise, add this translation.
                $translation = new Translation();
                $translation->setEntry($entry);
                $translation->setValue($value);
                // @TODO: set the locale.
                
                $entry->addTranslation($translation);
            }
            // @TODO: find the way to persist the entry.
        }
        
        // @TODO: flush the transaction.
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


