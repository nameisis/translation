<?php

namespace Selonia\TranslationBundle\Utils\DataGrid;

use Selonia\TranslationBundle\Manager\FileManagerInterface;
use Selonia\TranslationBundle\Manager\LocaleManagerInterface;
use Selonia\TranslationBundle\Manager\TransUnitManagerInterface;
use Selonia\TranslationBundle\Model\TransUnit;
use Selonia\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Translation\DataCollector\TranslationDataCollector;
use Symfony\Component\Translation\DataCollectorTranslator;

class DataGridRequestHandler
{
    /**
     * @var TransUnitManagerInterface
     */
    protected $transUnitManager;

    /**
     * @var FileManagerInterface
     */
    protected $fileManager;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var LocaleManagerInterface
     */
    protected $localeManager;

    /**
     * @var Profiler
     */
    protected $profiler;

    /**
     * @var bool
     */
    protected $createMissing;

    /**
     * @var string
     */
    protected $defaultFileFormat;

    /**
     * @param TransUnitManagerInterface $transUnitManager
     * @param FileManagerInterface $fileManager
     * @param StorageInterface $storage
     * @param LocaleManagerInterface $localeManager
     */
    public function __construct(TransUnitManagerInterface $transUnitManager, FileManagerInterface $fileManager, StorageInterface $storage, LocaleManagerInterface $localeManager)
    {
        $this->transUnitManager = $transUnitManager;
        $this->fileManager = $fileManager;
        $this->storage = $storage;
        $this->localeManager = $localeManager;
        $this->createMissing = false;
        $this->defaultFileFormat = 'yml';
    }

    /**
     * @param Profiler $profiler
     */
    public function setProfiler(Profiler $profiler = null)
    {
        $this->profiler = $profiler;
    }

    /**
     * @param bool $createMissing
     */
    public function setCreateMissing($createMissing)
    {
        $this->createMissing = (bool)$createMissing;
    }

    /**
     * @param string $format
     */
    public function setDefaultFileFormat($format)
    {
        $this->defaultFileFormat = $format;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function getPage(Request $request)
    {
        $parameters = $this->fixParameters($request->query->all());
        $transUnits = $this->storage->getTransUnitList($this->localeManager->getLocales(), $request->query->get('rows', 20), $request->query->get('page', 1), $parameters);
        $count = $this->storage->countTransUnits($this->localeManager->getLocales(), $parameters);

        return [$transUnits, $count];
    }

    /**
     * @param array $dirtyParameters
     *
     * @return array
     */
    protected function fixParameters(array $dirtyParameters)
    {
        $parameters = [];
        array_walk($dirtyParameters, function ($value, $key) use (&$parameters) {
            if ($key != '_search') {
                $key = trim($key, '_');
                $value = trim($value, '_');
            }
            $parameters[$key] = $value;
        });

        return $parameters;
    }

    /**
     * @param Request $request
     * @param string $token
     *
     * @return array
     */
    public function getPageByToken(Request $request, $token)
    {
        list($transUnits, $count) = $this->getByToken($token);
        $parameters = $this->fixParameters($request->query->all());

        return $this->filterTokenTranslations($transUnits, $count, $parameters);
    }

    /**
     * @param string $token
     *
     * @return array
     */
    public function getByToken($token)
    {
        if (null === $this->profiler) {
            throw new \RuntimeException('Invalid profiler instance.');
        }
        $profile = $this->profiler->loadProfile($token);
        if (!$profile instanceof Profile) {
            return [[], 0];
        }
        try {
            /** @var TranslationDataCollector $collector */
            $collector = $profile->getCollector('translation');
            $messages = $collector->getMessages();
            $transUnits = [];
            foreach ($messages as $message) {
                $transUnit = $this->storage->getTransUnitByKeyAndDomain($message['id'], $message['domain']);
                if ($transUnit instanceof TransUnit) {
                    $transUnits[] = $transUnit;
                } elseif (true === $this->createMissing) {
                    $transUnits[] = $transUnit = $this->transUnitManager->create($message['id'], $message['domain'], true);
                }
                if (!$transUnit->hasTranslation($message['locale']) && $message['state'] === DataCollectorTranslator::MESSAGE_DEFINED) {
                    $file = $this->fileManager->getFor(sprintf('%s.%s.%s', $message['domain'], $message['locale'], $this->defaultFileFormat));
                    $this->transUnitManager->addTranslation($transUnit, $message['locale'], $message['translation'], $file, true);
                }
            }

            return [$transUnits, count($transUnits)];
        } catch (\InvalidArgumentException $e) {
            return [[], 0];
        }
    }

    /**
     * @param array $transUnits
     * @param int $count
     * @param array $parameters
     *
     * @return array
     */
    protected function filterTokenTranslations($transUnits, $count, $parameters)
    {
        if (isset($parameters['_search']) && $parameters['_search']) {
            $nonFilterParams = ['rows', 'page', '_search'];
            $filters = [];
            array_walk($parameters, function ($value, $key) use (&$filters, $nonFilterParams) {
                if (!in_array($key, $nonFilterParams) && !empty($value)) {
                    $filters[$key] = $value;
                }
            });
            if (count($filters) > 0) {
                $end = count($transUnits);
                for ($i = 0; $i < $end; $i++) {
                    $match = true;
                    foreach ($filters as $column => $str) {
                        if (in_array($column, ['key', 'domain'])) {
                            $value = $transUnits[$i]->{sprintf('get%s', ucfirst($column))}();
                        } else {
                            $translation = $transUnits[$i]->getTranslation($column);
                            $value = $translation ? $translation->getContent() : '';
                        }
                        $match = $match && (1 === preg_match(sprintf('/.*%s.*/i', $str), $value));
                    }
                    if (!$match) {
                        unset($transUnits[$i]);
                    }
                }
                $transUnits = array_values($transUnits);
                $count = count($transUnits);
            }
        }
        if ($count > $parameters['rows']) {
            $transUnitsPage = array_slice($transUnits, $parameters['rows'] * ($parameters['page'] - 1), $parameters['rows']);
        } else {
            $transUnitsPage = $transUnits;
        }

        return [$transUnitsPage, $count];
    }

    /**
     * @param integer $id
     * @param Request $request
     *
     * @throws NotFoundHttpException
     * @return TransUnit
     */
    public function updateFromRequest($id, Request $request)
    {
        $transUnit = $this->storage->getTransUnitById($id);
        if (!$transUnit) {
            throw new NotFoundHttpException(sprintf('No TransUnit found for "%s"', $id));
        }
        $translationsContent = [];
        foreach ($this->localeManager->getLocales() as $locale) {
            $translationsContent[$locale] = $request->request->get($locale);
        }
        $this->transUnitManager->updateTranslationsContent($transUnit, $translationsContent);
        $this->storage->flush();

        return $transUnit;
    }
}
