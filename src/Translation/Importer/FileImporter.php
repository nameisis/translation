<?php

namespace Selonia\TranslationBundle\Translation\Importer;

use Selonia\TranslationBundle\Entity\Translation;
use Selonia\TranslationBundle\Manager\FileManagerInterface;
use Selonia\TranslationBundle\Manager\TranslationInterface;
use Selonia\TranslationBundle\Manager\TransUnitInterface;
use Selonia\TranslationBundle\Manager\TransUnitManagerInterface;
use Selonia\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\Finder\SplFileInfo;

class FileImporter
{
    /**
     * @var array
     */
    private $loaders;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var TransUnitManagerInterface
     */
    private $transUnitManager;

    /**
     * @var FileManagerInterface
     */
    private $fileManager;

    /**
     * @var boolean
     */
    private $caseInsensitiveInsert;

    /**
     * @var array
     */
    private $skippedKeys;

    /**
     * Construct.
     *
     * @param array $loaders
     * @param StorageInterface $storage
     * @param TransUnitManagerInterface $transUnitManager
     * @param FileManagerInterface $fileManager
     */
    public function __construct(array $loaders, StorageInterface $storage, TransUnitManagerInterface $transUnitManager, FileManagerInterface $fileManager)
    {
        if (!isset($loaders['yml'])) {
            $loaders['yml'] = $loaders['yaml'];
        }
        if (!isset($loaders['xlif'])) {
            $loaders['xliff'] = $loaders['xlf'];
        }
        $this->loaders = $loaders;
        $this->storage = $storage;
        $this->transUnitManager = $transUnitManager;
        $this->fileManager = $fileManager;
        $this->caseInsensitiveInsert = false;
        $this->skippedKeys = [];
    }

    /**
     * @param boolean $value
     */
    public function setCaseInsensitiveInsert($value)
    {
        $this->caseInsensitiveInsert = (bool)$value;
    }

    /**
     * @return array
     */
    public function getSkippedKeys()
    {
        return $this->skippedKeys;
    }

    /**
     * @param SplFileInfo $file
     * @param boolean $forceUpdate
     * @param boolean $merge
     *
     * @return int
     */
    public function import(SplFileInfo $file, $forceUpdate = false, $merge = false)
    {
        $this->skippedKeys = [];
        $imported = 0;
        list($domain, $locale, $extension) = explode('.', $file->getFilename());

        if (!isset($this->loaders[$extension])) {
            throw new \RuntimeException(sprintf('No load found for "%s" format.', $extension));
        }
        $messageCatalogue = $this->loaders[$extension]->load($file->getPathname(), $locale, $domain);
        $translationFile = $this->fileManager->getFor($file->getFilename(), $file->getPath());
        $keys = [];
        foreach ($messageCatalogue->all($domain) as $key => $content) {
            if (!isset($content)) {
                continue;
            }
            $normalizedKey = $this->caseInsensitiveInsert ? strtolower($key) : $key;
            if (in_array($normalizedKey, $keys, true)) {
                $this->skippedKeys[] = $key;
                continue;
            }
            $transUnit = $this->storage->getTransUnitByKeyAndDomain($key, $domain);
            if (!($transUnit instanceof TransUnitInterface)) {
                $transUnit = $this->transUnitManager->create($key, $domain, true);
            }
            $translation = $this->transUnitManager->addTranslation($transUnit, $locale, $content, $translationFile);
            if ($translation && $translation->getTransUnit() && $translation->getFile()) {
                $translation->getFile()
                    ->setDomain($translation->getTransUnit()
                        ->getDomain());
            }
            if ($translation instanceof TranslationInterface) {
                $imported++;
            } else {
                if ($forceUpdate) {
                    $translation = $this->transUnitManager->updateTranslation($transUnit, $locale, $content);
                    if ($translation instanceof Translation) {
                        $translation->setModifiedManually(false);
                    }
                    $imported++;
                } else {
                    if ($merge) {
                        $translation = $this->transUnitManager->updateTranslation($transUnit, $locale, $content, false, true);
                        if ($translation instanceof TranslationInterface) {
                            $imported++;
                        }
                    }
                }
            }
            $keys[] = $normalizedKey;
        }
        $this->storage->flush();
        foreach (['file', 'trans_unit', 'translation', 'domain'] as $name) {
            $this->storage->clear($this->storage->getModelClass($name));
        }

        return $imported;
    }
}
