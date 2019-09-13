<?php

namespace Selonia\TranslationBundle\Manager;

use Symfony\Component\HttpFoundation\File\File;

interface FileManagerInterface
{
    /**
     * @param string $name
     * @param string $path
     *
     * @return File
     */
    public function create($name, $path, $flush = false);

    /**
     * @param string $name
     * @param string $path
     *
     * @return File
     */
    public function getFor($name, $path = null);
}
