<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 */

class Base extends ContainerAwareCommand
{
    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * Retrieve the list of registered domains.
     *
     * @param \Symfony\Component\HttpKernel\Kernel $kernel
     * @param boolean $searchForTranslationFiles to instruct the method to automatically search for translation files of this domain.
     * 
     * @return array
     */
    protected function getDomainList($kernel, $searchForTranslationFiles=false)
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
                'files' => $searchForTranslationFiles
                           ? $this->getTranslationFileList($bundle)
                           : array()
            );
        }

        return $domains;
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