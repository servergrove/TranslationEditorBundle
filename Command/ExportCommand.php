<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Dumper;

/**
 * Command for exporting translations into files
 */
class ExportCommand extends Base
{
    protected function configure()
    {
        parent::configure();

        $this
        ->setName('locale:editor:export')
        ->setDescription('Export translations into files')
        ->addArgument('filename')
        ->addOption('dry-run')
        ->addOption('pretty-print')
        ;

    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $filename = $input->getArgument('filename');

        $files = array();

        if (!empty($filename) && is_dir($filename)) {
            $output->writeln("Exporting translations to <info>$filename</info>...");
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
        $output->writeln(sprintf("Found %d files, exporting...", count($files)));
        
        foreach ($files as $filename) {
            $this->export($filename);
        }

    }

    public function export($filename)
    {
        $fname = basename($filename);
        $this->output->writeln("Exporting to <info>".$filename."</info>...");

        list($name, $locale, $type) = explode('.', $fname);

        $data = $this->getContainer()->get('server_grove_translation_editor.storage_manager')->getCollection()->findOne(array('filename'=>$filename));
        if (!$data) {
            $this->output->writeln("Could not find data for this locale");
            return;
        }

        switch($type) {
            case 'yml':
                foreach ($data['entries'] as $key => $val) {
                    if (empty($val)) {
                        unset($data['entries'][$key]);
                    }
                }

                $dumper = new Dumper();
                $result = $dumper->dump($data['entries'], 1);

                break;
            case 'xliff':
                $xml = new \SimpleXMLElement('<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2"></xliff>');

                $xliff_file = $xml->addChild("file");
            	$xliff_file->addAttribute("source-language", $locale);
            	$xliff_file->addAttribute("datatype", "plaintext");
            	$xliff_file->addAttribute("original", "file.ext");

            	$body = $xliff_file->addChild('body');

            	$i = 0;
            	foreach ($data['entries'] as $source => $target) {
            		if (empty($target)) {
            			continue;
            		}

            		$unit = $body->addChild('trans-unit');
            		$unit->addAttribute("id", ++$i);
            		$unit->addChild("source", $source);
            		$unit->addChild("target", $target);
            	}

            	$result = $xml->asXML();
            	
            	if ($this->input->getOption('pretty-print')) {
            		$dom = new \DOMDocument('1.0');
            		$dom->preserveWhiteSpace = false;
            		$dom->formatOutput       = true;
            		$dom->loadXML($result);
            		
            		$result = $dom->saveXML();
            	}
            	
                break;
        }

        $this->output->writeln("  Writing ".count($data['entries'])." entries to $filename");
        if (!$this->input->getOption('dry-run')) {
    		file_put_contents($filename, $result);
    	}
    }
}

