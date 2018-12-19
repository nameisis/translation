<?php

namespace Nameisis\TranslationBundle\Translation\Exporter;

use InvalidArgumentException;
use Symfony\Component\Yaml\Dumper;

class YamlExporter implements ExporterInterface
{
    private $createTree;

    /**
     * @param bool $createTree
     */
    public function __construct($createTree = true)
    {
        $this->createTree = $createTree;
    }

    /**
     * {@inheritdoc}
     */
    public function export($file, $translations)
    {
        if ($this->createTree) {
            $result = $this->createMultiArray($translations);
            $translations = $this->flattenArray($result);
        }
        $ymlContent = (new Dumper())->dump($translations, 10, 0);
        $bytes = file_put_contents($file, $ymlContent);

        return ($bytes !== false);
    }

    /**
     * @param array $translations
     *
     * @return array
     */
    protected function createMultiArray(array $translations)
    {
        $res = [];
        foreach ($translations as $keyString => $value) {
            $keys = explode('.', $keyString);
            $keyLength = count($keys);
            if ($keys[$keyLength - 1] == '') {
                unset($keys[$keyLength - 1]);
                $keys[$keyLength - 2] .= '.';
            }
            $this->addValueToMultiArray($res, $value, $keys);
        }

        return $res;
    }

    /**
     * @param array $array
     * @param $value
     * @param array $keys
     *
     * @throws InvalidArgumentException
     */
    private function addValueToMultiArray(array &$array, $value, array $keys)
    {
        $key = array_shift($keys);
        if (count($keys) == 0) {
            $array[$key] = $value;

            return;
        }
        if (!isset($array[$key])) {
            $array[$key] = [];
        } elseif (!is_array($array[$key])) {
            throw new InvalidArgumentException('Found an leaf, expected a tree');
        }
        $this->addValueToMultiArray($array[$key], $value, $keys);
    }

    /**
     * @param mixed $array
     * @param string $prefix
     *
     * @return mixed
     */
    protected function flattenArray($array, $prefix = '')
    {
        if (is_array($array)) {
            foreach ($array as $key => $subarray) {
                if (count($array) == 1) {
                    return $this->flattenArray($subarray, ($prefix == '' ? $prefix : $prefix.'.').$key);
                }
                $array[$key] = $this->flattenArray($subarray);
            }
        }
        if ($prefix == '') {
            return $array;
        }

        return [$prefix => $array];
    }

    /**
     * {@inheritdoc}
     */
    public function support($format)
    {
        return ('yml' === $format);
    }
}
