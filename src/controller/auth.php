<?php //-->
/**
 * This file is part of a Custom Project.
 * (c) 2016-2018 Acme Products Inc.
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

use Cradle\Package\System\Schema as SystemSchema;
use Cradle\Module\Utility\File;
use Cradle\OAuth\OAuth2;

/**
 * Render the Signup Page
 *
 * SIGNUP FLOW:
 * - GET /signup
 * - POST /signup
 * - EMAIL
 * - GET /activate/auth_id/hash
 * - GET /login
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/auth/signup', function ($request, $response) {
    //----------------------------//
    // 1. Security Checks
    //----------------------------//
    // 2. Prepare Data
    //Prepare body
    $data = ['item' => $request->getPost()];

    $authSchema = SystemSchema::i('auth');
    $data['auth_schema'] = $authSchema->getAll();

    $profileSchema = SystemSchema::i('profile');
    $data['profile_schema'] = $profileSchema->getAll();

    //add CSRF
    $this->trigger('csrf-load', $request, $response);
    $data['csrf'] = $response->getResults('csrf');

    //add captcha
    $this->trigger('captcha-load', $request, $response);
    $data['captcha'] = $response->getResults('captcha');

    if ($response->isError()) {
        if ($response->getValidation('auth_slug')) {
            $message = $response->getValidation('auth_slug');
        }

        $response->setFlash($response->getMessage(), 'error');
        $data['errors'] = $response->getValidation();
    }

    //if there are file fields
    if (!empty($data['auth_schema']['files'])
        || !empty($data['profile_schema']['files'])
    ) {
        //add CDN
        $config = $this->package('global')->service('s3-main');
        $data['cdn_config'] = File::getS3Client($config);
    }

    //----------------------------//
    // 3. Render Template
    //Render body
    $class = 'page-auth-signup';
    $title = $this->package('global')->translate('Sign Up');

    $template = dirname(__DIR__) . '/template';
    if (is_dir($response->getPage('template_root'))) {
        $template = $response->getPage('template_root');
    }

    $partials = dirname(__DIR__) . '/template';
    if (is_dir($response->getPage('partials_root'))) {
        $partials = $response->getPage('partials_root');
    }

    $body = $this
        ->package('cradlephp/cradle-system')
        ->template(
            'signup',
            $data,
            ['form_fieldset'],
            $template,
            $partials
        );

    //Set Content
    $response
        ->setPage('title', $title)
        ->setPage('class', $class)
        ->setContent($body);

    //if we only want the body
    if ($request->getStage('render') === 'body') {
        return;
    }

    //Render blank page
    $this->trigger('www-render-blank', $request, $response);
});

/**
 * Render the Login Page
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/auth/login', function ($request, $response) {
    //----------------------------//
    // 1. Prepare Data
    // get home page
    $home = $this->package('global')->config('settings', 'home');

    if (!$home) {
        $home = '/';
    }

    // already logged in?
    if ($request->getSession('me')) {
        return $this->package('global')->redirect($home);
    }

    //Prepare body
    $data = ['item' => $request->getPost()];

    $package = $this->package('cradlephp/cradle-auth');
    $attempts = $package->getAttempts($request);
    $authConfig = $package->config();

    if (count($attempts) >= $authConfig['captcha']) {
        //add Captcha
        $this->trigger('captcha-load', $request, $response);
        $data['captcha'] = $response->getResults('captcha');
    }

    //add CSRF
    $this->trigger('csrf-load', $request, $response);
    $data['csrf'] = $response->getResults('csrf');


    if ($response->isError()) {
        $response->setFlash($response->getMessage(), 'error');

        $data['errors'] = $response->getValidation();
    }

    //----------------------------//
    // 2. Render Template
    //Render body
    $class = 'page-auth-login';
    $title = $this->package('global')->translate('Log In');

    $template = dirname(__DIR__) . '/template';
    if (is_dir($response->getPage('template_root'))) {
        $template = $response->getPage('template_root');
    }

    $partials = dirname(__DIR__) . '/template';
    if (is_dir($response->getPage('partials_root'))) {
        $partials = $response->getPage('partials_root');
    }

    $body = $this
        ->package('cradlephp/cradle-system')
        ->template(
            'login',
            $data,
            [],
            $template,
            $partials
        );

    //Set Content
    $response
        ->setPage('title', $title)
        ->setPage('class', $class)
        ->setContent($body);

    //if we only want the body
    if ($request->getStage('render') === 'body') {
        return;
    }

    //Render blank page
    $this->trigger('www-render-blank', $request, $response);
});

/**
 * Process the Logout
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/auth/logout', function ($request, $response) {
    //----------------------------//
    // 1. Security Checks
    //----------------------------//
    // 2. Prepare Data
    $request->removeSession('me');

    //add a flash
    $this->package('global')->flash('Log Out Successful', 'success');

    //redirect
    $redirect = '/';
    if ($request->hasGet('redirect_uri')) {
        $redirect = $request->getGet('redirect_uri');
    }

    $this->package('global')->redirect($redirect);
});

/**
 * Render the Account Page
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/auth/account', function ($request, $response) {
    //----------------------------//
    // 1. Security Checks
    //Need to be logged in
    $this->package('global')->requireLogin();

    //----------------------------//
    // 2. Prepare Data
    //Prepare body
    $data = ['item' => $request->getPost()];

    $authSchema = SystemSchema::i('auth');
    $data['auth_schema'] = $authSchema->getAll();

    $profileSchema = SystemSchema::i('profile');
    $data['profile_schema'] = $profileSchema->getAll();

    //add CDN
    $config = $this->package('global')->service('s3-main');
    $data['cdn_config'] = File::getS3Client($config);

    //add CSRF
    $this->trigger('csrf-load', $request, $response);
    $data['csrf'] = $response->getResults('csrf');

    //If no post
    if (!$request->hasPost('profile_name')) {
        //set default data
        $data['item'] = $request->getSession('me');
    }

    if ($response->isError()) {
        $response->setFlash($response->getMessage(), 'error');
        $data['errors'] = $response->getValidation();
    }

    //if there are file fields
    if (!empty($data['schema']['files'])) {
        //add CDN
        $config = $this->package('global')->service('s3-main');
        $data['cdn_config'] = File::getS3Client($config);
    }

    //----------------------------//
    // 3. Render Template
    //Render body
    $class = 'page-auth-account';
    $title = $this->package('global')->translate('Account Settings');

    $template = dirname(__DIR__) . '/template';
    if (is_dir($response->getPage('template_root'))) {
        $template = $response->getPage('template_root');
    }

    $partials = dirname(__DIR__) . '/template';
    if (is_dir($response->getPage('partials_root'))) {
        $partials = $response->getPage('partials_root');
    }

    $body = $this
        ->package('cradlephp/cradle-system')
        ->template(
            'account',
            $data,
            ['form_fieldset'],
            $template,
            $partials
        );

    //Set Content
    $response
        ->setPage('title', $title)
        ->setPage('class', $class)
        ->setContent($body);

    //if we only want the body
    if ($request->getStage('render') === 'body') {
        return;
    }

    //Render blank page
    $this->trigger('www-render-blank', $request, $response);
});

/**
 * Render the Forgot Page
 *
 * FORGOT FLOW:
 * - GET /forgot
 * - POST /forgot
 * - EMAIL
 * - GET /recover/auth_id/hash
 * - POST /recover/auth_id/hash
 * - GET /login
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/auth/forgot', function ($request, $response) {
    //----------------------------//
    // 1. Security Checks
    //----------------------------//
    // 2. Prepare Data
    //Prepare body
    $data = ['item' => $request->getPost()];

    $package = $this->package('cradlephp/cradle-auth');
    $attempts = $package->getAttempts($request);
    $authConfig = $package->config();

    if (count($attempts) >= $authConfig['captcha']) {
        //add Captcha
        $this->trigger('captcha-load', $request, $response);
        $data['captcha'] = $response->getResults('captcha');
    }

    //add CSRF
    $this->trigger('csrf-load', $request, $response);
    $data['csrf'] = $response->getResults('csrf');

    if ($response->isError()) {
        $response->setFlash($response->getMessage(), 'error');
        $data['errors'] = $response->getValidation();
    }

    //----------------------------//
    // 3. Render Template
    //Render body
    $class = 'page-auth-forgot';
    $title = $this->package('global')->translate('Forgot Password');

    $template = dirname(__DIR__) . '/template';
    if (is_dir($response->getPage('template_root'))) {
        $template = $response->getPage('template_root');
    }

    $partials = dirname(__DIR__) . '/template';
    if (is_dir($response->getPage('partials_root'))) {
        $partials = $response->getPage('partials_root');
    }

    $body = $this
        ->package('cradlephp/cradle-system')
        ->template(
            'forgot',
            $data,
            [],
            $template,
            $partials
        );

    //Set Content
    $response
        ->setPage('title', $title)
        ->setPage('class', $class)
        ->setContent($body);

    //if we only want the body
    if ($request->getStage('render') === 'body') {
        return;
    }

    //Render blank page
    $this->trigger('www-render-blank', $request, $response);
});

/**
 * Render the Recover Page
 *
 * FORGOT FLOW:
 * - GET /forgot
 * - POST /forgot
 * - EMAIL
 * - GET /recover/auth_id/hash
 * - POST /recover/auth_id/hash
 * - GET /login
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/auth/recover/:auth_id/:hash', function ($request, $response) {
    //----------------------------//
    // 1. Security Checks
    //----------------------------//
    // 2. Prepare Data
    if (!$response->isError()) {
        //get the detail
        $this->trigger('auth-detail', $request, $response);

        if ($response->isError()) {
            $response->setFlash($response->getMessage(), 'error');
            return $this->package('global')->redirect('/auth/forgot');
        }
    }

    //form hash
    $authId = $response->getResults('auth_id');
    $authUpdated = $response->getResults('auth_updated');
    $hash = md5($authId.$authUpdated);

    //check the verification hash
    if ($hash !== $request->getStage('hash')) {
        $this->package('global')->flash('Invalid verification. Try again.', 'error');
        return $this->package('global')->redirect('/auth/forgot');
    }

    //Prepare body
    $data = ['item' => $request->getPost()];

    //add CSRF
    $this->trigger('csrf-load', $request, $response);
    $data['csrf'] = $response->getResults('csrf');

    if ($response->isError()) {
        $response->setFlash($response->getMessage(), 'error');
        $data['errors'] = $response->getValidation();
    }

    //----------------------------//
    // 3. Render Template
    //Render body
    $class = 'page-auth-recover';
    $title = $this->package('global')->translate('Recover Password');

    $template = dirname(__DIR__) . '/template';
    if (is_dir($response->getPage('template_root'))) {
        $template = $response->getPage('template_root');
    }

    $partials = dirname(__DIR__) . '/template';
    if (is_dir($response->getPage('partials_root'))) {
        $partials = $response->getPage('partials_root');
    }

    $body = $this
        ->package('cradlephp/cradle-system')
        ->template(
            'recover',
            $data,
            [],
            $template,
            $partials
        );

    //Set Content
    $response
        ->setPage('title', $title)
        ->setPage('class', $class)
        ->setContent($body);

    //if we only want the body
    if ($request->getStage('render') === 'body') {
        return;
    }

    //Render blank page
    $this->trigger('www-render-blank', $request, $response);
});

/**
 * Render the Verify Page
 *
 * VERIFY FLOW:
 * - GET /verify
 * - POST /verify
 * - EMAIL
 * - GET /activate/auth_id/hash
 * - GET /login
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/auth/verify', function ($request, $response) {
    //----------------------------//
    // 1. Security Checks
    //----------------------------//
    // 2. Prepare Data
    //Prepare body
    $data = ['item' => $request->getPost()];

    $package = $this->package('cradlephp/cradle-auth');
    $attempts = $package->getAttempts($request);
    $authConfig = $package->config();

    if (count($attempts) >= $authConfig['captcha']) {
        //add Captcha
        $this->trigger('captcha-load', $request, $response);
        $data['captcha'] = $response->getResults('captcha');
    }

    //add CSRF
    $this->trigger('csrf-load', $request, $response);
    $data['csrf'] = $response->getResults('csrf');

    if ($response->isError()) {
        $response->setFlash($response->getMessage(), 'error');
        $data['errors'] = $response->getValidation();
    }

    //----------------------------//
    // 3. Render Template
    //Render body
    $class = 'page-auth-verify';
    $title = $this->package('global')->translate('Verify Account');

    $template = dirname(__DIR__) . '/template';
    if (is_dir($response->getPage('template_root'))) {
        $template = $response->getPage('template_root');
    }

    $partials = dirname(__DIR__) . '/template';
    if (is_dir($response->getPage('partials_root'))) {
        $partials = $response->getPage('partials_root');
    }

    $body = $this
        ->package('cradlephp/cradle-system')
        ->template(
            'verify',
            $data,
            [],
            $template,
            $partials
        );

    //Set Content
    $response
        ->setPage('title', $title)
        ->setPage('class', $class)
        ->setContent($body);

    //if we only want the body
    if ($request->getStage('render') === 'body') {
        return;
    }

    //Render blank page
    $this->trigger('www-render-blank', $request, $response);
});

/**
 * Process the Account Page
 *
 * @param Request $request
 * @param Response $response
 */
$this->post('/auth/account', function ($request, $response) {
    //----------------------------//
    // 1. Setup Overrides
    //determine route
    $route = '/auth/account';
    if ($request->hasStage('route')) {
        $route = $request->getStage('route');
    }

    //determine redirect
    $redirect = '/';
    if ($request->hasGet('redirect_uri')) {
        $redirect = $request->getGet('redirect_uri');
    }

    //----------------------------//
    // 2. Security Checks
    //need to be online
    $this->package('global')->requireLogin();

    //csrf check
    $this->trigger('csrf-validate', $request, $response);

    if ($response->isError()) {
        return $this->routeTo('get', $route, $request, $response);
    }

    //----------------------------//
    // 3. Prepare Data
    //set the auth_id and profile_id
    $request->setStage('auth_id', $request->getSession('me', 'auth_id'));
    $request->setStage('profile_id', $request->getSession('me', 'profile_id'));
    $request->setStage('permission', $request->getSession('me', 'profile_id'));

    //remove password if empty
    if (!$request->getStage('auth_password')) {
        $request->removeStage('auth_password');
    }

    if (!$request->getStage('confirm')) {
        $request->removeStage('confirm');
    }

    //----------------------------//
    // 4. Process Request
    //trigger the job
    $this->trigger('auth-update', $request, $response);

    //----------------------------//
    // 5. Interpret Results
    if ($response->isError()) {
        return $this->routeTo('get', $route, $request, $response);
    }

    //it was good
    //update the session
    $this->trigger('auth-detail', $request, $response);
    $request->setSession('me', $response->getResults());

    //if we dont want to redirect
    if ($redirect === 'false') {
        return;
    }

    //add a flash
    $message = $this->package('global')->translate('Update Successful');
    $this->package('global')->flash($message, 'success');

    $this->package('global')->redirect($redirect);
});

/**
 * Process the Login Page
 *
 * @param Request $request
 * @param Response $response
 */
$this->post('/auth/login', function ($request, $response) {
    //----------------------------//
    // 1. Setup Overrides
    //determine route
    $route = '/auth/login';
    if ($request->hasStage('route')) {
        $route = $request->getStage('route');
    }

    //determine redirect
    $redirect = '/';
    if ($request->hasGet('redirect_uri')) {
        $redirect = $request->getGet('redirect_uri');
    }

    //----------------------------//
    // 2. Security Checks
    $package = $this->package('cradlephp/cradle-auth');
    $attempts = $package->getAttempts($request);
    $authConfig = $package->config();

    if (count($attempts) >= $authConfig['lockout']) {
        //add attempt
        $package->addAttempt($request);
        $wait = $package->waitFor($request);

        if ($wait) {
            $message = sprintf(
                'Too many submission attempts please wait %s minutes before trying again.',
                number_format(ceil($wait / 60))
            );

            $response->setError(true, $message);
            return $this->routeTo('get', $route, $request, $response);
        }
    } else if (count($attempts) >= $authConfig['captcha']) {
        //captcha check
        $this->trigger('captcha-validate', $request, $response);

        if ($response->isError()) {
            //add attempt
            $package->addAttempt($request);
            return $this->routeTo('get', $route, $request, $response);
        }
    }

    //csrf check
    $this->trigger('csrf-validate', $request, $response);

    if ($response->isError()) {
        //add attempt
        $package->addAttempt($request);
        return $this->routeTo('get', $route, $request, $response);
    }

    //----------------------------//
    // 3. Prepare Data
    //----------------------------//
    // 4. Process Request
    //call the job
    $this->trigger('auth-login', $request, $response);

    //----------------------------//
    // 5. Interpret Results
    if ($response->isError()) {
        //add attempt
        $package->addAttempt($request);
        return $this->routeTo('get', $route, $request, $response);
    }

    // if account is not activated
    if ($response->getResults('auth_active') == 0) {
        //add attempt
        $package->addAttempt($request);
        // set message
        $this->package('global')->flash('Your account is not activated.', 'warning');
        // set redirect
        return $this->package('global')->redirect('/auth/login');
    }

    //it was good
    $package->clearAttempts($request);
    //store to session
    //TODO: Sessions for clusters
    $request->setSession('me', $response->getResults());

    //if we dont want to redirect
    if ($redirect === 'false') {
        return;
    }

    //add a flash
    $message = $this->package('global')->translate('Welcome!');
    $this->package('global')->flash($message, 'success');
    $this->package('global')->redirect($redirect);
});

/**
 * Process the Forgot Page
 *
 * FORGOT FLOW:
 * - GET /forgot
 * - POST /forgot
 * - EMAIL
 * - GET /recover/auth_id/hash
 * - POST /recover/auth_id/hash
 * - GET /login
 *
 * @param Request $request
 * @param Response $response
 */
$this->post('/auth/forgot', function ($request, $response) {
    //----------------------------//
    // 1. Setup Overrides
    //determine route
    $route = '/auth/forgot';
    if ($request->hasStage('route')) {
        $route = $request->getStage('route');
    }

    //determine redirect
    $redirect = '/auth/forgot';
    if ($request->hasGet('redirect_uri')) {
        $redirect = $request->getGet('redirect_uri');
    }

    //----------------------------//
    // 2. Security Checks
    $package = $this->package('cradlephp/cradle-auth');
    $attempts = $package->getAttempts($request);
    $authConfig = $package->config();

    if (count($attempts) >= $authConfig['lockout']) {
        //add attempt
        $package->addAttempt($request);
        $wait = $package->waitFor($request);

        if ($wait) {
            $message = sprintf(
                'Too many submission attempts please wait %s minutes before trying again.',
                number_format(ceil($wait / 60))
            );

            $response->setError(true, $message);
            return $this->routeTo('get', $route, $request, $response);
        }
    } else if (count($attempts) > $authConfig['captcha']) {
        //captcha check
        $this->trigger('captcha-validate', $request, $response);

        if ($response->isError()) {
            //add attempt
            $package->addAttempt($request);
            return $this->routeTo('get', $route, $request, $response);
        }
    }

    //csrf check
    $this->trigger('csrf-validate', $request, $response);

    if ($response->isError()) {
        //add attempt
        $package->addAttempt($request);
        return $this->routeTo('get', $route, $request, $response);
    }

    //----------------------------//
    // 3. Prepare Data
    //----------------------------//
    // 4. Process Request
    //trigger the job
    $this->trigger('auth-forgot', $request, $response);

    //----------------------------//
    // 5. Interpret Results
    if ($response->isError()) {
        //add attempt
        $package->addAttempt($request);
        return $this->routeTo('get', $route, $request, $response);
    }

    //its good
    $package->clearAttempts($request);
    //if we dont want to redirect
    if ($redirect === 'false') {
        return;
    }

    //add a flash
    $message = $this->package('global')->translate('An email with recovery instructions will be sent in a few minutes.');
    $this->package('global')->flash($message, 'success');
    $this->package('global')->redirect($redirect);
});

/**
 * Process the Recover Page
 *
 * FORGOT FLOW:
 * - GET /forgot
 * - POST /forgot
 * - EMAIL
 * - GET /recover/auth_id/hash
 * - POST /recover/auth_id/hash
 * - GET /login
 *
 * @param Request $request
 * @param Response $response
 */
$this->post('/auth/recover/:auth_id/:hash', function ($request, $response) {
    //----------------------------//
    // 1. Setup Overrides
    //----------------------------//
    // 2. Security Checks
    //get the detail
    $this->trigger('auth-detail', $request, $response);

    if ($response->isError()) {
        $response->setFlash($response->getMessage(), 'error');
        return $this->package('global')->redirect('/auth/forgot');
    }

    //----------------------------//
    // 3. Prepare Data
    //form hash
    $authId = $response->getResults('auth_id');
    $authUpdated = $response->getResults('auth_updated');
    $hash = md5($authId.$authUpdated);

    //determine route
    $route = sprintf('/auth/recover/%s/%s', $authId, $hash);

    if ($request->hasStage('route')) {
        $route = $request->getStage('route');
    }

    //determine redirect
    $redirect = '/auth/login';
    if ($request->hasGet('redirect_uri')) {
        $redirect = $request->getGet('redirect_uri');
    }

    //check the recovery hash
    if ($hash !== $request->getStage('hash')) {
        $message = $this->package('global')->translate('This recovery page is expired. Please try again.');

        //if we dont want to redirect
        if ($redirect === 'false') {
            return $response->setError(true, $message);
        }

        $this->package('global')->flash($message, 'error');
        return $this->package('global')->redirect('/auth/forgot');
    }

    //csrf check
    $this->trigger('csrf-validate', $request, $response);

    if ($response->isError()) {
        return $this->routeTo('get', $route, $request, $response);
    }

    //----------------------------//
    // 4. Process Request
    //trigger the job
    $this->trigger('auth-recover', $request, $response);

    //----------------------------//
    // 5. Interpret Results
    if ($response->isError()) {
        return $this->routeTo('get', $route, $request, $response);
    }

    //if we dont want to redirect
    if ($redirect === 'false') {
        return;
    }

    //add a flash
    $message = $this->package('global')->translate('Recovery Successful');
    $this->package('global')->flash($message, 'success');
    $this->package('global')->redirect($redirect);
});

/**
 * Process the Signup Page
 *
 * SIGNUP FLOW:
 * - GET /signup
 * - POST /signup
 * - EMAIL
 * - GET /activate/auth_id/hash
 * - GET /login
 *
 * @param Request $request
 * @param Response $response
 */
$this->post('/auth/signup', function ($request, $response) {
    //----------------------------//
    // 1. Setup Overrides
    //determine route
    $route = '/auth/signup';
    if ($request->hasStage('route')) {
        $route = $request->getStage('route');
    }

    //determine redirect
    $redirect = '/auth/login';
    if ($request->hasGet('redirect_uri')) {
        $redirect = $request->getGet('redirect_uri');
    }

    //----------------------------//
    // 2. Security Checks
    $package = $this->package('cradlephp/cradle-auth');
    $attempts = $package->getAttempts($request);
    $authConfig = $package->config();

    if (count($attempts) >= $authConfig['lockout']) {
        //add attempt
        $package->addAttempt($request);
        $wait = $package->waitFor($request);

        if ($wait) {
            $message = sprintf(
                'Too many submission attempts please wait %s minutes before trying again.',
                number_format(ceil($wait / 60))
            );

            $response->setError(true, $message);
            return $this->routeTo('get', $route, $request, $response);
        }
    }

    //csrf check
    $this->trigger('csrf-validate', $request, $response);

    if ($response->isError()) {
        //add attempt
        $package->addAttempt($request);
        return $this->routeTo('get', $route, $request, $response);
    }

    //captcha check
    $this->trigger('captcha-validate', $request, $response);

    if ($response->isError()) {
        //add attempt
        $package->addAttempt($request);
        return $this->routeTo('get', $route, $request, $response);
    }

    //----------------------------//
    // 3. Prepare Data
    //----------------------------//
    // 4. Process Request
    //trigger the job
    $this->trigger('auth-create', $request, $response);

    //----------------------------//
    // 5. Interpret Results
    if ($response->isError()) {
        //add attempt
        $package->addAttempt($request);
        return $this->routeTo('get', $route, $request, $response);
    }

    //its good
    $package->clearAttempts($request);

    //if we dont want to redirect
    if ($redirect === 'false') {
        return;
    }

    $request->setSoftStage($response->getResults());

    // send verify email
    $this->trigger('auth-verify', $request, $response);

    //add a flash
    $message = $this->package('global')->translate('Sign Up Successful. Please check your email for verification process.');
    $this->package('global')->flash($message, 'success');
    $this->package('global')->redirect($redirect);
});

/**
 * Process the Verify Page
 *
 * VERIFY FLOW:
 * - GET /verify
 * - POST /verify
 * - EMAIL
 * - GET /activate/auth_id/hash
 * - GET /login
 *
 * @param Request $request
 * @param Response $response
 */
$this->post('/auth/verify', function ($request, $response) {
    //----------------------------//
    // 1. Setup Overrides
    //determine route
    $route = '/auth/verify';
    if ($request->hasStage('route')) {
        $route = $request->getStage('route');
    }

    //----------------------------//
    // 2. Security Checks
    $package = $this->package('cradlephp/cradle-auth');
    $attempts = $package->getAttempts($request);
    $authConfig = $package->config();

    if (count($attempts) >= $authConfig['lockout']) {
        //add attempt
        $package->addAttempt($request);
        $wait = $package->waitFor($request);

        if ($wait) {
            $message = sprintf(
                'Too many submission attempts please wait %s minutes before trying again.',
                number_format(ceil($wait / 60))
            );

            $response->setError(true, $message);
            return $this->routeTo('get', $route, $request, $response);
        }
    } else if (count($attempts) > $authConfig['captcha']) {
        //captcha check
        $this->trigger('captcha-validate', $request, $response);

        if ($response->isError()) {
            //add attempt
            $package->addAttempt($request);
            return $this->routeTo('get', $route, $request, $response);
        }
    }

    //csrf check
    $this->trigger('csrf-validate', $request, $response);

    if ($response->isError()) {
        //add attempt
        $package->addAttempt($request);
        return $this->routeTo('get', $route, $request, $response);
    }

    //----------------------------//
    // 3. Prepare Data
    //----------------------------//
    // 3. Process Request
    //trigger the job
    $this->trigger('auth-verify', $request, $response);

    //----------------------------//
    // 4. Interpret Results
    if ($response->isError()) {
        //add attempt
        $package->addAttempt($request);
        return $this->routeTo('get', $route, $request, $response);
    }

    //its good
    $package->clearAttempts($request);

    //determine redirect
    $redirect = '/auth/verify';
    if ($request->hasGet('redirect_uri')) {
        $redirect = $request->getGet('redirect_uri');
    }

    //if we dont want to redirect
    if ($redirect === 'false') {
        return;
    }

    //its good
    $message = $this->package('global')->translate('An email with verification instructions will be sent in a few minutes.');
    $this->package('global')->flash($message, 'success');
    $this->package('global')->redirect($redirect);
});

/**
 * Process the Verification Page
 *
 * SIGNUP FLOW:
 * - GET /signup
 * - POST /signup
 * - EMAIL
 * - GET /activate/auth_id/hash
 * - GET /login
 *
 * VERIFY FLOW:
 * - GET /verify
 * - POST /verify
 * - EMAIL
 * - GET /activate/auth_id/hash
 * - GET /login
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/auth/activate/:auth_id/:hash', function ($request, $response) {
    //get the detail
    $this->trigger('auth-detail', $request, $response);

    //form hash
    $authId = $response->getResults('auth_id');
    $authUpdated = $response->getResults('auth_updated');
    $hash = md5($authId.$authUpdated);

    //check the verification hash
    if ($hash !== $request->getStage('hash')) {
        $this->package('global')->flash('Invalid verification. Try again.', 'danger');
        return $this->package('global')->redirect('/auth/verify');
    }

    //activate
    $request->setStage('auth_active', 1);

    if ($request->hasSession('me')) {
        $request->setSession('me', 'auth_active', 1);
    }

    //trigger the job
    $this->trigger('auth-update', $request, $response);

    if ($response->isError()) {
        $this->package('global')->flash('Invalid verification. Try again.', 'danger');
        return $this->package('global')->redirect('/auth/verify');
    }

    //it was good
    //add a flash
    $this->package('global')->flash('Activation Successful', 'success');

    //redirect
    $this->package('global')->redirect('/auth/login');
});

/**
 * Process an OAuth2 Login
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/auth/sso/login/oauth2/:name', function ($request, $response) {
    //get the global package
    $global = $this->package('global');
    $name = $request->getStage('name');
    // get config
    $config = $global->config('services', 'oauth2-' . $name);

    if (!$config
        || !isset($config['client_id'])
        || !isset($config['client_secret'])
        || !isset($config['url_authorize'])
        || !isset($config['url_access_token'])
        || !isset($config['url_resource'])
        || !$config['active']
    ) {
        $global->flash('Invalid Service. Try again', 'error');
        return $global->redirect('/');
    }

    $protocol = 'http';
    if ($request->getServer('HTTP_CF_VISITOR')) {
        $pos = strpos($request->getServer('HTTP_CF_VISITOR'), 'https');
        if ($pos !== false) {
            $protocol = 'https';
        }
    }

    //host
    $host = $protocol . '://' . $request->getServer('HTTP_HOST');
    $path = $request->get('server', 'REQUEST_URI');

    //get provider
    $provider = new OAuth2(
        $config['client_id'],    // The client ID assigned to you by the provider
        $config['client_secret'],   // The client password assigned to you by the provider
        $host . $path,
        $config['url_authorize'],
        $config['url_access_token'],
        $config['url_resource']
    );

    //if there is not a code
    if (!$request->hasStage('code')) {
        if ($request->hasGet('redirect_uri')) {
            $request->setSession('redirect_uri', $request->getGet('redirect_uri'));
        }

        if (isset($config['scope'])) {
            //set scope
            if (!is_array($config['scope'])){
                $config['scope'] = [ $config['scope'] ];

            }

            $scope = $config['scope'];
            $provider->setScope(...$scope);
        }

        //get redirect url
        $redirect = $provider->getLoginUrl();
        //redirect
        return $global->redirect($redirect);
    }

    //there's a code
    try {
        $accessToken = $provider->getAccessTokens($request->getStage('code'));
    } catch (Exception $e) {
        // When Graph returns an error
        $global->flash($e->getMessage(), 'error');
        return $global->redirect('/');
    }

    if (isset($accessToken['error']) && $accessToken['error']) {
        $global->flash('Access Token Error', 'error');
        return $global->redirect('/');
    }

    if (!isset($accessToken['access_token'])
        || !isset($accessToken['access_secret'])
    ) {
        $global->flash('Access Token Error', 'error');
        return $global->redirect('/');
    }

    $token = $accessToken['access_token'];
    $secret = $accessToken['access_secret'];

    //Now you can get user info
    //access token from $token
    try {
        $user = $provider->get([ 'access_token' => $token ]);
    } catch (Exception $e) {
        $global->flash($e->getMessage(), 'error');
        return $global->redirect('/');
    }

    if (isset($user['error']) && $user['error']) {
        $global->flash('Resource Request Error', 'error');
        return $global->redirect('/');
    }

    //set some defaults
    $request->setStage('profile_email', $user['email']);
    $request->setStage('profile_name', $user['name']);
    $request->setStage('auth_slug', $user['email']);
    $request->setStage('auth_password', $user['id']);
    $request->setStage('auth_active', 1);
    $request->setStage('confirm', $user['id']);
    //there might be more information
    $request->setStage('resource', $user);

    $this->trigger('auth-sso-login', $request, $response);

    if ($response->isError()) {
        $global->flash($response->getMessage(), 'error');
        return $global->redirect('/');
    }

    //it was good
    //store to session
    $_SESSION['me'] = $response->getResults();
    $_SESSION['me']['access_token'] = $token;
    $_SESSION['me']['access_secret'] = $secret;

    //redirect
    $redirect = '/';
    if ($request->hasGet('redirect_uri')) {
        $redirect = $request->getGet('redirect_uri');
    }

    return $global->redirect($redirect);
});
