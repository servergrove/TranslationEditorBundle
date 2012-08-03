<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Translation\Exporter;

use Doctrine\Common\Collections\ArrayCollection,
    Symfony\Component\Yaml\Dumper;

use ServerGrove\Bundle\TranslationEditorBundle\Model\TranslationInterface;

/**
 * YAML Exporter
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class YamlExporter implements ExporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFileExtension()
    {
        return 'yml';
    }

    /**
     * {@inheritdoc}
     */
    public function exportFile($filePath, ArrayCollection $translationList)
    {
        // Creating file
        $yamlExporter = array();

        foreach ($translationList as $translation) {
            $yamlExporter[$translation->getEntry()->getAlias()] = $translation->getValue();
        }

        // Writting file
        return $this->writeFile($filePath, $yamlExporter);
    }

    /**
     * Write the file
     *
     * @param string $filePath
     * @param array $xliffFile
     *
     * @return integer
     */
    protected function writeFile($filePath, array $yamlFile)
    {
        $dumper = new Dumper();
        $yaml   = $dumper->dump($yamlFile);

        return file_put_contents($filePath, $yaml);
    }
}