<?php

namespace Selonia\TranslationBundle\Manager;

use Selonia\TranslationBundle\Model\Translation;
use Selonia\TranslationBundle\Storage\StorageInterface;

class TransUnitManager implements TransUnitManagerInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var FileManagerInterface
     */
    private $fileManager;

    /**
     * @var String
     */
    private $kernelRootDir;

    /**
     * Construct.
     *
     * @param StorageInterface $storage
     * @param FileManagerInterface $fm
     * @param String $kernelRootDir
     */
    public function __construct(StorageInterface $storage, FileManagerInterface $fm, $kernelRootDir)
    {
        $this->storage = $storage;
        $this->fileManager = $fm;
        $this->kernelRootDir = $kernelRootDir;
    }

    /**
     * @param TransUnitInterface $transUnit
     *
     * @return bool
     */
    public function delete(TransUnitInterface $transUnit)
    {
        try {
            foreach ($transUnit->getTranslations() as $translation) {
                $this->storage->remove($translation);
            }
            $this->storage->remove($transUnit);
            $this->storage->flush();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param TransUnitInterface $transUnit
     * @param string $locale
     *
     * @return bool
     */
    public function deleteTranslation(TransUnitInterface $transUnit, $locale)
    {
        try {
            $translation = $transUnit->getTranslation($locale);
            $this->storage->remove($translation);
            $this->storage->flush();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function newInstance($locales = [])
    {
        $transUnitClass = $this->storage->getModelClass('trans_unit');
        $translationClass = $this->storage->getModelClass('translation');
        $transUnit = new $transUnitClass();
        foreach ($locales as $locale) {
            $translation = new $translationClass();
            $translation->setLocale($locale);
            $transUnit->addTranslation($translation);
        }

        return $transUnit;
    }

    /**
     * {@inheritdoc}
     */
    public function create($keyName, $domainName, $flush = false)
    {
        $domain = $this->storage->getManager()
            ->getRepository($this->storage->getModelClass('domain'))
            ->findOneByName($domainName);
        if (null === $domain) {
            $class = $this->storage->getModelClass('domain');
            $domain = new $class();
            $domain->setName($domainName);
            $this->storage->persist($domain);
        }
        $transUnit = $this->storage->getManager()
            ->getRepository($this->storage->getModelClass('trans_unit'))
            ->findOneBy(['domain' => $domain, 'key' => $keyName]);
        if (null === $transUnit) {
            $transUnit = $this->newInstance();
        }
        $transUnit->setKey($keyName);
        $transUnit->setDomain($domain);
        $this->storage->persist($transUnit);
        if ($flush) {
            $this->storage->flush();
        }

        return $transUnit;
    }

    /**
     * {@inheritdoc}
     */
    public function addTranslation(TransUnitInterface $transUnit, $locale, $content, FileInterface $file = null, $flush = false)
    {
        $translation = null;
        if (!$transUnit->hasTranslation($locale)) {
            $class = $this->storage->getModelClass('translation');
            $translation = new $class();
            $translation->setLocale($locale);
            $translation->setContent($content);
            if ($file !== null) {
                $translation->setFile($file);
            }
            $transUnit->addTranslation($translation);
            $this->storage->persist($transUnit);
            $this->storage->persist($translation);
            if ($flush) {
                $this->storage->flush();
            }
        }

        return $translation;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTranslation(TransUnitInterface $transUnit, $locale, $content, $flush = false, $merge = false)
    {
        $translation = null;
        $i = 0;
        $end = $transUnit->getTranslations()
            ->count();
        $found = false;
        while ($i < $end && !$found) {
            $found = ($transUnit->getTranslations()
                    ->get($i)
                    ->getLocale() == $locale);
            $i++;
        }
        if ($found) {
            /* @var Translation $translation */
            $translation = $transUnit->getTranslations()
                ->get($i - 1);
            if ($merge) {
                if ($translation->isModifiedManually() || $translation->getContent() == $content) {
                    return null;
                }
                $newTranslation = clone $translation;
                $this->storage->remove($translation);
                $this->storage->flush();
                $newTranslation->setContent($content);
                $this->storage->persist($newTranslation);
                $translation = $newTranslation;
            }
            $translation->setContent($content);
        }
        if ($flush) {
            $this->storage->flush();
        }

        return $translation;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTranslationsContent(TransUnitInterface $transUnit, array $translations, $flush = false)
    {
        foreach ($translations as $locale => $content) {
            if (!empty($content)) {
                /** @var TranslationInterface|null $translation */
                $translation = $transUnit->getTranslation($locale);
                $contentUpdated = true;
                if ($translation instanceof TranslationInterface) {
                    $originalContent = $translation->getContent();
                    $translation = $this->updateTranslation($transUnit, $locale, $content);
                    $contentUpdated = ($translation->getContent() != $originalContent);
                } else {
                    //We need to get a proper file for this translation
                    $file = $this->getTranslationFile($transUnit, $locale);
                    $translation = $this->addTranslation($transUnit, $locale, $content, $file);
                }
                if ($translation instanceof Translation && $contentUpdated) {
                    $translation->setModifiedManually(true);
                }
            }
        }
        if ($flush) {
            $this->storage->flush();
        }
    }

    /**
     * @param TransUnitInterface $transUnit
     * @param string $locale
     *
     * @return FileInterface|null
     */
    public function getTranslationFile(TransUnitInterface $transUnit, $locale)
    {
        $file = null;
        foreach ($transUnit->getTranslations() as $translation) {
            if (null !== $file = $translation->getFile()) {
                break;
            }
        }
        if ($file !== null) {
            $name = sprintf('%s.%s.%s', $file->getDomain(), $locale, $file->getExtension());
            $file = $this->fileManager->getFor($name, $this->kernelRootDir.DIRECTORY_SEPARATOR.$file->getPath());
        }

        return $file;
    }
}
