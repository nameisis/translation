<?php

namespace Selonia\TranslationBundle\Translation\Exporter;

class PhpExporter implements ExporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function export($file, $translations)
    {
        $phpContent = sprintf("<?php\nreturn %s;", var_export($translations, true));
        $bytes = file_put_contents($file, $phpContent);

        return ($bytes !== false);
    }

    /**
     * {@inheritdoc}
     */
    public function support($format)
    {
        return ('php' === $format);
    }
}
