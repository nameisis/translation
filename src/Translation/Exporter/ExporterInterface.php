<?php

namespace Nameisis\TranslationBundle\Translation\Exporter;

interface ExporterInterface
{
    /**
     * @param string $file
     * @param array $translations
     *
     * @return boolean
     */
    public function export($file, $translations);

    /**
     * @param string $format
     *
     * @return boolean
     */
    public function support($format);
}
