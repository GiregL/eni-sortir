<?php

namespace App\Model;

use Symfony\Component\HttpFoundation\File\File;

class BatchAddUsersModel
{
    private $usersFile;

    /**
     * @return mixed
     */
    public function getUsersFile()
    {
        return $this->usersFile;
    }

    /**
     * @param mixed $usersFile
     */
    public function setUsersFile($usersFile): void
    {
        $this->usersFile = $usersFile;
    }
}