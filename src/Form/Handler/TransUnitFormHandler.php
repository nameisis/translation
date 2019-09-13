<?php

namespace Selonia\TranslationBundle\Form\Handler;

use Selonia\TranslationBundle\Manager\FileInterface;
use Selonia\TranslationBundle\Manager\FileManagerInterface;
use Selonia\TranslationBundle\Manager\LocaleManagerInterface;
use Selonia\TranslationBundle\Manager\TransUnitManagerInterface;
use Selonia\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class TransUnitFormHandler implements FormHandlerInterface
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
     * @var string
     */
    protected $rootDir;

    /**
     * @param TransUnitManagerInterface $transUnitManager
     * @param FileManagerInterface $fileManager
     * @param StorageInterface $storage
     * @param LocaleManagerInterface $localeManager
     * @param string $rootDir
     */
    public function __construct(TransUnitManagerInterface $transUnitManager, FileManagerInterface $fileManager, StorageInterface $storage, LocaleManagerInterface $localeManager, $rootDir)
    {
        $this->transUnitManager = $transUnitManager;
        $this->fileManager = $fileManager;
        $this->storage = $storage;
        $this->localeManager = $localeManager;
        $this->rootDir = $rootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function createFormData()
    {
        return $this->transUnitManager->newInstance($this->localeManager->getLocales());
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions()
    {
        return [
            'domains' => $this->storage->getTransUnitDomains(),
            'data_class' => $this->storage->getModelClass('trans_unit'),
            'translation_class' => $this->storage->getModelClass('translation'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function process(FormInterface $form, Request $request)
    {
        $valid = false;
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $transUnit = $form->getData();
                $translations = $transUnit->filterNotBlankTranslations();
                foreach ($translations as $translation) {
                    if (!$translation->getFile()) {
                        $file = $this->fileManager->getFor(sprintf('%s.%s.yml', $transUnit->getDomain()
                            ->getName(), $translation->getLocale()), $this->rootDir.'/Resources/translations');
                        if ($file instanceof FileInterface) {
                            $file->setDomain($transUnit->getDomain());
                            $translation->setFile($file);
                        }
                    }
                }
                $transUnit->setTranslations($translations);
                $this->storage->persist($transUnit);
                $this->storage->persist($file);
                $this->storage->flush();
                $valid = true;
            }
        }

        return $valid;
    }
}
