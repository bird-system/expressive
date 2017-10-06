<?php

namespace BS\Authentication;

use BS\Db\Model\UserInfo;
use BS\Exception\UnAuthenticatedException;
use Zend\Authentication\AuthenticationService as Base;
use Zend\Authentication\Storage\Session;

class AuthenticationService extends Base
{
    private static $UserInfo = [];

    /**
     * @return mixed
     * @throws UnAuthenticatedException
     */
    public function getUserInfo()
    {
        if ($this->hasIdentity()) {
            $storage = $this->getStorage();
            if ($storage instanceof Session) {
                $StorageNameSpace = $storage->getNamespace();

                if (!isset(static::$UserInfo[$StorageNameSpace])) {
                    $UserInfo = (new UserInfo())->exchangeArray($storage->read());
                    static::$UserInfo[$StorageNameSpace] = $UserInfo;
                }

                return static::$UserInfo[$StorageNameSpace];
            }
        }
        throw new UnAuthenticatedException;
    }
}