<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

use ServerGrove\Entity\Entry as TranslationPackage;
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

        list($name, $locale, $type) = $this->extractNameLocaleType($filename);
        
        switch($type) {
            case 'yml':
            case 'yaml':
                $this->importYaml($filename, $name, $locale);
                break;
            case 'xliff':
                $this->importXml($filename, $name, $locale);
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
    
    protected function importXml($filename, $name, $locale)
    {
        $xml = simplexml_load_file(file_get_contents($filename));
        
        // Load the data from the storage service.
        $entries = $this->getContainer()
           ->get('server_grove_translation_editor.storage')
           ->getEntries(array('locale'=>$locale));
        
        $this->output->writeln(sprintf('  Found entries: %d', count($entries)));
        
        if ( ! $entries) {
            $entries = TranslationPackage();
        }
        foreach ($entries->getTranslations() as $translation) {
            $translation;
        }
        
    }
    
    /**
     * Import from a YAML file.
     *
     * @param string $filename
     * @param string $name
     * @param string $locale
     */
    protected function importYaml($filename, $name, $locale)
    {
        throw \Exception('Code not updated');
        
        $this->setMongoIndexes();
        
        $type = 'yml';
        
        $yaml = new Parser();
        $value = $yaml->parse(file_get_contents($filename));

        $data = $this->getContainer()
            ->get('server_grove_translation_editor.storage_manager')
            ->getCollection()
            ->findOne(array('filename'=>$filename));
        
        if (!$data) {
            $data = array(
                'filename' => $filename,
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


