<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Finder\Finder;

/**
 * AbstractCommand
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
abstract class AbstractCommand extends ContainerAwareCommand
{
    /**
     * @var string the relative path
     */
    const TRANSLATION_PATH = 'Resources/translations';

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * Retrieve the list of registered Bundles
     *
     * @param string $filterBundleName
     *
     * @return array
     */
    protected function getBundleList($filterBundleName = null)
    {
        $kernel = $this->getContainer()->get('kernel');

        if ($filterBundleName) {
            return array($kernel->getBundle($filterBundleName));
        }

        $sourcePath = realpath($kernel->getRootDir() . '/../src');

        // Filter non-application bundles
        $bundleList = array_filter(
            $kernel->getBundles(),
            function ($bundle) use ($sourcePath) {
                return (strpos($bundle->getPath(), $sourcePath) === 0);
            }
        );

        return $bundleList;
    }

    /**
     * Extract file information
     *
     * @param string $fileName
     *
     * @return array
     */
    protected function extractNameLocaleType($fileName)
    {
        // Gather information for re-assembly.
        list($name, $locale, $type) = explode('.', basename($fileName));
        list($language, $country)   = $this->extractLocaleInformation($locale);

        // Fix the inconsistency in naming convention.
        $type = strtolower($type);

        return array($name, $language, $country, $type);
    }

    /**
     * Extract locale information
     *
     * @param string $locale
     *
     * @return array
     */
    protected function extractLocaleInformation($locale)
    {
        $localeParts = preg_split('/(-|_)/', $locale, 2);

        // Fix the inconsistency in naming convention.
        $language = strtolower($localeParts[0]);
        $country  = (isset($localeParts[1])) ? strtoupper($localeParts[1]) : null;

        return array($language, $country);
    }
}