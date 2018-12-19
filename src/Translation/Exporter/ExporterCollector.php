<?php

namespace Nameisis\TranslationBundle\Translation\Exporter;

use RuntimeException;

class ExporterCollector
{
    /**
     * @var array
     */
    private $exporters;

    /**
     *
     * @param string $id
     * @param ExporterInterface $exporter
     */
    public function addExporter($id, ExporterInterface $exporter)
    {
        $this->exporters[$id] = $exporter;
    }

    /**
     * @param string $format
     *
     * @return ExporterInterface
     * @throws RuntimeException
     */
    public function getByFormat($format)
    {
        foreach ($this->getExporters() as $exporter) {
            if ($exporter->support($format)) {
                return $exporter;
            }
        }
        throw new RuntimeException(sprintf('No exporter found for "%s" format.', $format));
    }

    /**
     * @return array
     */
    public function getExporters()
    {
        return $this->exporters;
    }

    /**
     * @param string $format
     * @param string $file
     * @param array $translations
     *
     * @return bool
     * @throws RuntimeException
     */
    public function export($format, $file, $translations)
    {
        foreach ($this->getExporters() as $exporter) {
            if ($exporter->support($format)) {
                return $exporter->export($file, $translations);
            }
        }
        throw new RuntimeException(sprintf('No exporter found for "%s" format.', $format));
    }
}
