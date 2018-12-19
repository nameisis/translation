<?php

namespace Nameisis\TranslationBundle\Translation;

interface TranslatorInterface
{
    public function removeLocalesCacheFiles(array $locales);

    public function getFormats();
}
