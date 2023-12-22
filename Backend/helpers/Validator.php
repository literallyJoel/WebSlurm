<?php

class Validator
{

    public function __construct()
    {
    }

    private function validateEmail(string $email): bool
    {
        return preg_match("/[^\s@]+@[^\s@]+\.[^\s@]+/", $email);
    }

    private function validateName(string $name): bool
    {
        return strlen($name) !== 0;
    }

    private function validatePassword(string $password): bool
    {
        //The password gets hashed on the client side, so we only need to check if it is empty
        return strlen($password) !== 0;
    }

    public function validateAccountCreation($body, $email, $name, $password, $generatePass, $role): bool
    {

        $isValid = true;
        $isValid = $isValid && $body !== null;
        if(!$isValid){error_log("failed on body");return false;}
        $isValid = $isValid && $this->validateEmail($email);
        if(!$isValid){error_log("failed on email");return false;}
        $isValid = $isValid && $this->validateName($name);
        if(!$isValid){error_log("failed on name");return false;}
        $isValid = $isValid && ($generatePass || $this->validatePassword($password));
        if(!$isValid){error_log("failed on password");return false;}
        $isValid = $isValid && ($role === 0 || $role === 1);
        if(!$isValid){error_log("failed on role");return false;}
        return $isValid;
    }

    public function validateAccountUpdate($body, $email, $name, $password): bool
    {
        $isValid = true;
        $isValid = $isValid && $body !== null;
        if(!$isValid){error_log("failed on body");return false;}
        
        if($email !== null){
            $isValid = $isValid && $this->validateEmail($email);
            if(!$isValid){error_log("failed on email");return false;}
        }
        if($name !== null){
            $isValid = $isValid && $this->validateName($name);
            if(!$isValid){error_log("failed on name");return false;}
        }
        if($password !== null){
            $isValid = $isValid && $this->validatePassword($password);
            if(!$isValid){error_log("failed on password");return false;}
        }

        return $isValid;

    }

    public function validateJobTypeCreation($body, $name, $parameters): bool
    {
        if($body === null){error_log("failed on body");return false;}
        if(!$this->validateName($name)){error_log("failed on name");return false;}
        return true;
    }
}