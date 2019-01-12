<?php
/**
 * This file is part of a package designed for the CradlePHP Project.
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

use Cradle\Http\Request\RequestInterface;
use Cradle\Http\Response\ResponseInterface;

return function(RequestInterface $request, ResponseInterface $response) {
    $package = $this->package('cradlephp/cradle-auth');

    /**
     * A helper to manage login attempts
     */
    $package->addMethod('getAttempts', function (RequestInterface $request) {
        $attempts = $request->getSession('login_attempts');

        if (!is_array($attempts)) {
            $attempts = [];
        }

        return $attempts;
    });

    /**
     * A helper to manage login attempts
     */
    $package->addMethod('clearAttempts', function (RequestInterface $request) {
        $request->removeSession('login_attempts');
        return $this;
    });

    /**
     * A helper to manage login attempts
     */
    $package->addMethod('addAttempt', function (RequestInterface $request) {
        $attempts = $this->getAttempts($request);
        array_unshift($attempts, time());
        $request->setSession('login_attempts', $attempts);
        return $attempts;
    });

    /**
     * Returns how long someone should wait before logging in again
     */
    $package->addMethod('waitFor', function (RequestInterface $request) {
        $attempts = $this->getAttempts($request);
        //allow a few attempts
        if (count($attempts) < 5) {
            return 0;
        }

        $wait = ($attempts[0] +  (60 * 5)) - time();

        if ($wait < 0) {
            $wait = 0;
        }

        return $wait;
    });
};
