<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Translation\Exporter;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * ExporterInterface
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
interface ExporterInterface
{
    /**
     * Retrieve the file extension
     *
     * @return string
     */
    public function getFileExtension();

    /**
     * Exports the translation entries.
     *
     * @param string $filePath
     * @param \Doctrine\Common\Collections\ArrayCollection $translationList
     */
    public function exportFile($filePath, ArrayCollection $translationList);
}
