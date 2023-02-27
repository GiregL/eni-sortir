<?php

namespace App\Model;

use Symfony\Component\HttpFoundation\File\File;

class BatchAddUsersModel
{
    private $file;

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param File $file
     */
    public function setFile($file): void
    {
        $this->file = $file;
    }
}