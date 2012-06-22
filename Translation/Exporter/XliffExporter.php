<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Translation\Exporter;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpKernel\Kernel;

use ServerGrove\Bundle\TranslationEditorBundle\Model\TranslationInterface;

/**
 * XIFF Exporter
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Juti Noppornpitak <jnopporn@shiroyuki.com>
 */
class XliffExporter implements ExporterInterface
{
    /**
     * @var string
     */
    protected $xliffSkeleton;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->xliffSkeleton = trim('
            <xliff version="1.2"
                   xmlns="urn:oasis:names:tc:xliff:document:1.2"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            </xliff>
        ');
    }

    /**
     * {@inheritdoc}
     */
    public function getFileExtension()
    {
        return (version_compare(Kernel::VERSION, '2.1') >= 0) ? 'xlf' : 'xliff';
    }

    /**
     * {@inheritdoc}
     */
    public function exportFile($filePath, ArrayCollection $translationList)
    {
        // Retrieving locale string
        $fileParts = explode('.', basename($filePath));

        // Creating file
        $xliffExporter   = new \SimpleXMLElement($this->xliffSkeleton);
        $xliffBodyNode   = $this->exportRoot($xliffExporter, $fileParts);
        $xliffIdentifier = 1;

        foreach ($translationList as $translation) {
            $this->exportTranslation($xliffBodyNode, $xliffIdentifier++, $translation);
        }

        // Writting file
        return $this->writeFile($filePath, $xliffExporter);
    }

    /**
     * Export XLIFF root node
     *
     * @param \SimpleXMLElement $xliffExporter
     * @param array $fileParts
     *
     * @return \SimpleXMLElement
     */
    protected function exportRoot(\SimpleXMLElement $xliffExporter, $fileParts)
    {
        $fileName      = $fileParts[0];
        $localeString  = $fileParts[1];

        $xliffFileNode = $xliffExporter->addChild('file');

        $xliffFileNode->addAttribute('source-language', str_replace('_', '-', $localeString));
        $xliffFileNode->addAttribute('datatype', 'plaintext');
        $xliffFileNode->addAttribute('original', sprintf('%s.%s.%s', $fileName, $localeString, $this->getFileExtension()));

        return $xliffFileNode->addChild('body');
    }

    /**
     * Export a XLIFF Translation Unit
     *
     * @param \SimpleXMLElement $xliffNode
     * @param integer $xliffIdentifier
     * @param \ServerGrove\Bundle\TranslationEditorBundle\Model\TranslationInterface $translation
     *
     * @return \SimpleXMLElement
     */
    protected function exportTranslation(\SimpleXMLElement $xliffNode, $xliffIdentifier, TranslationInterface $translation)
    {
        $xliffTranslationNode = $xliffNode->addChild('trans-unit');

        $xliffTranslationNode->addAttribute('id', $xliffIdentifier);
        $xliffTranslationNode->addChild('source', sprintf('<![CDATA[%s]]>', $translation->getEntry()->getAlias()));
        $xliffTranslationNode->addChild('target', sprintf('<![CDATA[%s]]>', $translation->getValue()));

        return $xliffTranslationNode;
    }

    /**
     * Write the file
     *
     * @param string $filePath
     * @param \SimpleXMLElement $xliffFile
     *
     * @return integer
     */
    protected function writeFile($filePath, \SimpleXMLElement $xliffFile)
    {
        // DOMDocument is able to format the output nicely
        $dom = new \DOMDocument('1.0');

        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;

        $dom->loadXML(html_entity_decode($xliffFile->asXml(), ENT_NOQUOTES, 'UTF-8'));

        return file_put_contents($filePath, $dom->saveXML());
    }
}
