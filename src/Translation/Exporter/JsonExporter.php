<?php

namespace Nameisis\TranslationBundle\Translation\Exporter;

class JsonExporter implements ExporterInterface
{
    /**
     * @var bool
     */
    private $hierarchicalFormat;

    /**
     * @param bool $hierarchicalFormat
     */
    public function __construct($hierarchicalFormat = false)
    {
        $this->hierarchicalFormat = $hierarchicalFormat;
    }

    /**
     * {@inheritdoc}
     */
    public function export($file, $translations)
    {
        $bytes = file_put_contents($file, json_encode($this->hierarchicalFormat ? $this->hierarchicalFormat($translations) : $translations, JSON_PRETTY_PRINT));

        return ($bytes !== false);
    }

    /**
     * @param array $translations
     *
     * @return array
     */
    protected function hierarchicalFormat(array $translations)
    {
        $output = [];
        foreach ($translations as $key => $value) {
            $output = array_merge_recursive($output, $this->converterKeyToArray($key, $value));
        }

        return $output;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return array
     */
    protected function converterKeyToArray($key, $value)
    {
        $keysTrad = preg_split("/\./", $key);

        return $this->convertArrayToArborescence($keysTrad, $value);
    }

    /**
     * @param mixed $arrayIn
     * @param mixed $endValue
     *
     * @return array
     */
    protected function convertArrayToArborescence($arrayIn, $endValue)
    {
        $lenArray = count($arrayIn);
        if ($lenArray == 0) {
            return $endValue;
        }
        reset($arrayIn);
        $firstKey = key($arrayIn);
        $firstValue = $arrayIn[$firstKey];
        unset($arrayIn[$firstKey]);

        return [$firstValue => $this->convertArrayToArborescence($arrayIn, $endValue)];
    }

    /**
     * {@inheritdoc}
     */
    public function support($format)
    {
        return ('json' == $format);
    }
}
