<?php

namespace Nameisis\TranslationBundle\Manager;

interface TranslationInterface
{
    /**
     * @return string
     */
    public function getLocale();

    /**
     * @return string
     */
    public function getContent();
}
