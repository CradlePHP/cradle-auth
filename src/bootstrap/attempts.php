<?php
/**
 * This file is part of a package designed for the CradlePHP Project.
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

use Cradle\Http\Request\RequestInterface;
use Cradle\Http\Response\ResponseInterface;

return function (RequestInterface $request, ResponseInterface $response) {
    $package = $this->package('cradlephp/cradle-auth');

    /**
     * A helper to manage login attempts
     */
    $package->addMethod('getAttempts', function (RequestInterface $request) {
        $attempts = $request->getSession('auth_attempts');

        if (!is_array($attempts)) {
            $attempts = [];
        }

        return $attempts;
    });

    /**
     * A helper to manage login attempts
     */
    $package->addMethod('clearAttempts', function (RequestInterface $request) {
        $request->removeSession('auth_attempts');
        return $this;
    });

    /**
     * A helper to manage login attempts
     */
    $package->addMethod('addAttempt', function (RequestInterface $request) {
        $attempts = $this->getAttempts($request);
        array_unshift($attempts, time());
        $request->setSession('auth_attempts', $attempts);
        return $attempts;
    });

    /**
     * Returns how long someone should wait before logging in again
     */
    $package->addMethod('waitFor', function (RequestInterface $request) {
        $config = $this->config();
        $attempts = $this->getAttempts($request);

        //allow a few attempts
        if (count($attempts) < $config['lockout']) {
            return 0;
        }

        $wait = ($attempts[0] +  (60 * $config['wait'])) - time();

        if ($wait < 0) {
            $wait = 0;
        }

        return $wait;
    });

    /**
     * Returns how long someone should wait before logging in again
     */
    $package->addMethod('config', function () {
        $config = cradle('global')->config('auth', 'submission');

        if (!is_array($config)) {
            $config = [];
        }

        if (!isset($config['captcha'])) {
            $config['captcha'] = 2;
        }

        if (!isset($config['lockout'])) {
            $config['lockout'] = 5;
        }

        if (!isset($config['wait'])) {
            $config['wait'] = 5;
        }

        return $config;
    });
};
