<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Finder\Finder,
    Symfony\Component\Translation\MessageCatalogue;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Command for importing translation files
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Juti Noppornpitak <jnopporn@shiroyuki.com>
 */
class ImportCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('locale:editor:import')
            ->setDescription('Import translation files into storage for usage through Translation Editor GUI')
            ->addOption('bundle', null, InputOption::VALUE_OPTIONAL, 'Allow to import a single bundle')
            ->addOption('locale', null, InputOption::VALUE_OPTIONAL, 'Import to a single locale')
            ->addOption('file', null, InputOption::VALUE_OPTIONAL, 'Restrict the importing to a single file')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        // Bundles scanning
        $this->output->write('Scanning for bundles... ');

        $bundleList      = $this->getBundleList($this->input->getOption('bundle'));
        $bundleListCount = count($bundleList);

        $this->output->writeln(sprintf('found "<info>%s</info>" item(s).', $bundleListCount));

        if ( ! $bundleListCount) {
            $this->output->writeln('No bundles to be processed.');

            return;
        }

        // Importing Bundles
        foreach ($bundleList as $bundle) {
            $this->output->writeln('');
            $this->output->writeln(sprintf('<comment>%s</comment>', $bundle->getName()));

            $this->importBundle($bundle);
        }

        $this->output->writeln('');
        $this->output->writeln('Importing completed.');
    }

    /**
     * Import a Bundle
     *
     * @param \Symfony\Component\HttpKernel\Bundle\Bundle $bundle
     */
    protected function importBundle($bundle)
    {
        // Translation files scanning
        $this->output->write('  Scanning for translation files... ');

        $translationFileList = $this->getTranslationFileList(
            $bundle,
            $this->input->getOption('locale'),
            $this->input->getOption('file')
        );
        $translationFileListCount = count($translationFileList);

        $this->output->writeln(sprintf('found "<info>%s</info>" item(s).', $translationFileListCount));

        if ( ! $translationFileListCount) {
            $this->output->writeln('  No translation files to be processed.');

            return;
        }

        // Importing files
        foreach ($translationFileList as $translationFilePath => $propertyList) {
            $this->output->write(sprintf('  Processing "<info>%s</info>"... ', basename($translationFilePath)));

            $storage = $this->getContainer()->get('server_grove_translation_editor.storage');
            $loader  = $this->getContainer()->get('translation.loader');

            // Get or create locale
            $localeList = $storage->findLocaleList(array(
                'language' => $propertyList['language'],
                'country'  => $propertyList['country']
            ));

            if (null === $locale = array_shift($localeList)) {
                $locale = $storage->createLocale($propertyList['language'], $propertyList['country']);
            }

            // Import translation
            $entryList = $storage->findEntryList(array(
                'domain'   => $bundle->getName(),
                'fileName' => $propertyList['name']
            ));

            $catalogue = new MessageCatalogue($locale);

            $loader->loadMessages(dirname($translationFilePath), $catalogue);

            foreach ($catalogue->all($propertyList['name']) as $alias => $value) {
                // Get or create entry
                $entryListFiltered = array_filter(
                    $entryList,
                    function ($entry) use ($alias) {
                        return ($alias === $entry->getAlias());
                    }
                );

                if (null === $entry = array_shift($entryListFiltered)) {
                    $entry = $storage->createEntry(
                        $bundle->getName(),
                        $propertyList['name'],
                        pathinfo($translationFilePath, PATHINFO_EXTENSION),
                        $alias
                    );
                }

                // Get or create translation
                $translationList = $entry->getTranslations()->filter(
                    function ($translation) use ($locale) {
                        return ($translation->getLocale() === $locale);
                    }
                );

                if (0 === count($translationList)) {
                    $storage->createTranslation($locale, $entry, $value);
                }
            }

            $storage->flush();
            $storage->clear();

            $this->output->writeln('<info>DONE</info>');
        }
    }

    /**
     * Retrieve the list of translation files of a given Bundle
     *
     * @param \Symfony\Component\HttpKernel\Bundle\Bundle $bundle
     * @param string $filterLocaleName
     * @param string $filterFileName
     *
     * @return array
     */
    protected function getTranslationFileList($bundle, $filterLocaleName = null, $filterFileName = null)
    {
        // Building translation directory
        $translationPath = $bundle->getPath() . DIRECTORY_SEPARATOR . self::TRANSLATION_PATH;
        $translationFiles = array();

        // If directory not found, return
        if ( ! file_exists($translationPath)) {
            return $translationFiles;
        }

        $finder = new Finder();
        $finder->files()->in($translationPath)->name('/^[\w]+\.[a-z_]{2,7}\.[a-z]{2,5}$/');

        foreach ($finder as $translationFile) {
            $translationFilePath = $translationFile->getRealPath();

            list($name, $language, $country, $type) = $this->extractNameLocaleType($translationFilePath);

            // Ignore locale if no match
            if ($filterLocaleName && $this->extractLocaleInformation($filterLocaleName) !== array($language, $country)) {
                continue;
            }

            // Ignore file name if no match
            if ($filterFileName && $name !== $filterFileName) {
                continue;
            }

            $translationFiles[$translationFilePath] = array(
                'name'     => $name,
                'language' => $language,
                'country'  => $country,
                'type'     => $type
            );
        }

        return $translationFiles;
    }
}
