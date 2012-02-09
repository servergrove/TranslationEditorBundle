<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Service;

class XliffFileHandler
{
    /**
     * @var string the regular expression for the context that requires CDATA.
     */
    const CDATA_PATTERN = '/[<>]/';
    
    /**
     * @var string the relative path
     */
    const TRANS_PATH = 'Resources/translations';
    
    /**
     * @var string XLIFF file template requiring two variables: locale and trans units (in this order). Should not be used directly.
     */
    protected $fileTemplate;
    
    /**
     * @var string XLIFF translation unit template requiring three variables: id, alias and context (in this order). Should not be used directly.
     */
    protected $transUnitTemplate;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->fileTemplate = implode("\n", array(
            '<?xml version="1.0"?>',
            '<xliff version="1.2"',
                   'xmlns="urn:oasis:names:tc:xliff:document:1.2"',
                   'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
                   'xsi:schemaLocation="urn:oasis:names:tc:xliff:document:1.2 ../../../../../../../../app/Resources/translations/xliff-core-1.2-strict.xsd.xml">',
                '<file source-language="%s" datatype="plaintext" original="file.ext"><body>%s</body></file></xliff>'
        ));
        
        $this->transUnitTemplate = implode("\n", array(
            '<trans-unit id="%d">',
                '<source>%s</source>',
                '<target>%s</target>',
            '</trans-unit>',
        ));
    }
    
    /**
     * Render a <trans-unit> block based on the given ID, alias and context.
     *
     * @param int $id
     * @param string $alias
     * @param string $context
     *
     * @return string rendered block of <trans-unit>
     */
    protected function renderTranslationUnit($id, $alias, $context)
    {
        $context = preg_match(self::CDATA_PATTERN, $context)
            ? $unit = sprintf('<![CDATA[%s]]>', $context)
            : $context;
        
        return sprintf($this->transUnitTemplate, $id, $alias, $context);
    }
    
    /**
     * Render files based on the given locale and entries.
     *
     * @param string $locale
     * @param array $entries
     *
     * @return array a dictionary keyed by \ServerGrove\Bundle\TranslationEditorBundle\Model\EntryInterface::getFileName()
     */
    protected function renderFiles($locale, array $entries)
    {
        $renderedTransUnits = array();
        
        // Render only a trans unit per entry.
        foreach ($entries as $entry) {
            $renderedTransUnits[$entry['filename']][] = $this->renderTranslationUnit(
                $entry['id'], $entry['alias'], $entry['context']
            );
        }
        
        foreach ($renderedTransUnits as $filename => $transUnits) {
            // Render the whole XLIFF file.
            $renderedXliffFile[$filename] = sprintf($this->fileTemplate, $locale, implode("\n", $transUnits));
        }
        
        return $renderedXliffFile;
    }
    
    /**
     * Write files for a given domain according to a given locale and entries.
     *
     * @param array $domain see \ServerGrove\Bundle\TranslationEditorBundle\Command\Base::getDomainList for the format.
     * @param string $locale the string representation of \ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface
     * @param array $entries see the assignment to $translations in \ServerGrove\Bundle\TranslationEditorBundle\Command\ExportCommand::exportDomain for the format.
     */
    public function writeFiles($domain, $locale, array $entries)
    {
        $contents = $this->renderFiles($locale, $entries);
        $locationPrefix = realpath(sprintf('%s/%s', $domain['path'], self::TRANS_PATH));
        
        foreach ($contents as $filename => $content) {
            $location = realpath(sprintf('%s/%s.%s.xliff', $locationPrefix, $filename, $locale));
            
            if (file_exists($location) && is_writable($location)) {
                unlink($location);
            }
            
            file_put_contents($location, $content);
        }
    }
}