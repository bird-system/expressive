<?php

namespace BS\Tests;

use BS\Authentication\Storage\Session;
use BS\Db\Model\UserInfo;

class MockedAuthService
{
    static protected $UserInfo;
    /**
     * @var Session
     */
    static protected $Storage;

    /**
     * @return UserInfo
     */
    public function getUserInfo()
    {
        if (!static::$UserInfo) {
            static::$UserInfo = new UserInfo([
                'id' => 1,
                'username' => 'username',
                'password' => 'password'
            ]);
        }

        return static::$UserInfo;
    }

    public function hasIdentity()
    {
        return true;
    }

    public function getStorage()
    {
        if (!static::$Storage) {
            static::$Storage = new Session();
            static::$Storage->write($this->getUserInfo()->getArrayCopy());
        }

        return static::$Storage;
    }
}