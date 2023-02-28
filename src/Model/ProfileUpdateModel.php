<?php

namespace App\Model;

use App\Entity\City;
use App\Entity\Member;
use App\Entity\Site;
use App\Entity\User;

/**
 * Profile update DTO
 */
class ProfileUpdateModel
{
    private $pseudo;
    private $firstName;
    private $lastName;
    private $phone;
    private $email;
    private $password;
    private $confirmPassword;
    private $city;
    private $profil;
    private $nameImage;

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo($pseudo): void
    {
        $this->pseudo = $pseudo;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName($firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName($lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone($phone): void
    {
        $this->phone = $phone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail($email): void
    {
        $this->email = $email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword($password): void
    {
        $this->password = $password;
    }

    public function getConfirmPassword(): ?string
    {
        return $this->confirmPassword;
    }

    public function setConfirmPassword($confirmPassword): void
    {
        $this->confirmPassword = $confirmPassword;
    }

    public function getCity(): ?Site
    {
        return $this->city;
    }

    public function setCity(Site $city): void
    {
        $this->city = $city;
    }
    
    public function getProfil(): ?Member
    {
        return $this->profil;
    }

    public function setProfil(Member $profil): self
    {
        $this->profil = $profil;

        return $this;
    }
    
    public function getNameImage(): ?string
    {
        return $this->nameImage;
    }

    public function setNameImage($nameImage): void
    {
        $this->nameImage = $nameImage;
    }
}