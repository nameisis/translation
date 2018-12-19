<?php

namespace Nameisis\TranslationBundle\Utils\DataGrid;

use Nameisis\TranslationBundle\Manager\LocaleManagerInterface;
use Nameisis\TranslationBundle\Manager\TransUnitInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use function is_array;

class DataGridFormatter
{
    /**
     * @var LocaleManagerInterface
     */
    protected $localeManager;

    /**
     * @var string
     */
    protected $storage;

    /**
     * @param LocaleManagerInterface $localeManager
     * @param string $storage
     */
    public function __construct(LocaleManagerInterface $localeManager, $storage)
    {
        $this->localeManager = $localeManager;
        $this->storage = $storage;
    }

    /**
     * @param array $transUnits
     * @param integer $total
     *
     * @return JsonResponse
     */
    public function createListResponse($transUnits, $total)
    {
        return new JsonResponse([
            'translations' => $this->format($transUnits),
            'total' => $total,
        ]);
    }

    /**
     * @param array $transUnits
     *
     * @return array
     */
    protected function format($transUnits)
    {
        $formatted = [];
        foreach ($transUnits as $transUnit) {
            $formatted[] = $this->formatOne($transUnit);
        }

        return $formatted;
    }

    /**
     * @param array $transUnit
     *
     * @return array
     */
    protected function formatOne($transUnit)
    {
        if (is_object($transUnit)) {
            $transUnit = $this->toArray($transUnit);
        }

        if (is_array($transUnit['domain'])) {
            $domain = $transUnit['domain']['name'];
        } else {
            $domain = $transUnit['domain']->getName();
        }

        $formatted = [
            '_id' => $transUnit['id'],
            '_domain' => $domain,
            '_key' => $transUnit['key'],
        ];

        foreach ($this->localeManager->getLocales() as $locale) {
            $formatted[$locale] = '';
        }

        foreach ($transUnit['translations'] as $translation) {
            if (in_array($translation['locale'], $this->localeManager->getLocales())) {
                $formatted[$translation['locale']] = $translation['content'];
            }
        }

        return $formatted;
    }

    /**
     * @param TransUnitInterface $transUnit
     *
     * @return array
     */
    protected function toArray(TransUnitInterface $transUnit)
    {
        $data = [
            'id' => $transUnit->getId(),
            'domain' => $transUnit->getDomain(),
            'key' => $transUnit->getKey(),
            'translations' => [],
        ];
        foreach ($transUnit->getTranslations() as $translation) {
            $data['translations'][] = [
                'locale' => $translation->getLocale(),
                'content' => $translation->getContent(),
            ];
        }

        return $data;
    }

    /**
     * @param mixed $transUnit
     *
     * @return JsonResponse
     */
    public function createSingleResponse($transUnit)
    {
        return new JsonResponse($this->formatOne($transUnit));
    }
}
