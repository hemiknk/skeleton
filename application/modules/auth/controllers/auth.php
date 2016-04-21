<?php
/**
 * Auth controller
 *
 * @author yuklia
 * @created  05.05.15 17:30
 */
namespace Application;

use Bluz\Proxy\Messages;
use Application\Auth\AuthProvider;

return
    /**
     * @param string $provider
     * @return \closure
     */
    function ($provider = '') {

        /**
         * @var Bootstrap $this
         */
        try {
            $auth = new AuthProvider($provider);
            $auth->setResponse($this);
            $auth->setIdentity($this->user());
            $link = $auth->authProcess();
            return function() use ($link){
                echo "<p><a href={$link}>Jethub</a></p>";
            };
        } catch (Exception $e) {
            Messages::addError($e->getMessage());
        }

        return
            function () {
                return false;
            };

    };
