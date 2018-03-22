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

    $userSchema = SystemSchema::i('profile');

    $data['schema'] = $userSchema->getAll();

    //add CSRF
    $this->trigger('csrf-load', $request, $response);
    $data['csrf'] = $response->getResults('csrf');

    //add captcha
    $this->trigger('captcha-load', $request, $response);
    $data['captcha'] = $response->getResults('captcha');

    if ($response->isError()) {
        if ($response->getValidation('auth_slug')) {
            $message = $response->getValidation('auth_slug');
            $response->addValidation('user_email', $message);
        }

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
    $class = 'page-auth-signup';
    $title = $this->package('global')->translate('Sign Up');
    $body = $this->package('cradlephp/cradle-auth')->template('signup', $data, [
        'partial_fields'
    ]);

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
    // 1. Security Checks
    // double check if session exists
    if (!empty($request->getSession('me'))) {
        // redirect to home
        return $this->package('global')->redirect('/');
    }

    //----------------------------//
    // 2. Prepare Data
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
    $class = 'page-auth-login';
    $title = $this->package('global')->translate('Log In');

    $body = $this
        ->package('cradlephp/cradle-auth')
        ->template('login', $data);

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

    $userSchema = SystemSchema::i('profile');

    $data['schema'] = $userSchema->getAll();

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
    $body = $this->package('cradlephp/cradle-auth')->template('account', $data, [
        'partial_fields'
    ]);

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
    $body =$this->package('cradlephp/cradle-auth')->template('forgot', $data);

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
    //get the detail
    $this->trigger('auth-detail', $request, $response);

    //form hash
    $authId = $response->getResults('auth_id');
    $authUpdated = $response->getResults('auth_updated');
    $hash = md5($authId.$authUpdated);

    //check the verification hash
    if ($hash !== $request->getStage('hash')) {
        $this->package('global')->flash('Invalid verification. Try again.', 'error');
        return $this->package('global')->redirect('/auth/verify');
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
    $body = $this->package('cradlephp/cradle-auth')->template('recover', $data);

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
    $body = $this->package('cradlephp/cradle-auth')->template('verify', $data);

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
    //csrf check
    $this->trigger('csrf-validate', $request, $response);

    if ($response->isError()) {
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
        return $this->routeTo('get', $route, $request, $response);
    }

    // if accoutn is not activated
    if ($response->getResults('auth_active') == 0) {
        // set message
        $this->package('global')->flash('Your account is not activated.', 'warning');
        // set redirect
        $this->package('global')->redirect('/auth/login');
    }

    //it was good
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
    //csrf check
    $this->trigger('csrf-validate', $request, $response);

    if ($response->isError()) {
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
        return $this->routeTo('get', $route, $request, $response);
    }

    //its good

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

    //----------------------------//
    // 2. Security Checks
    //----------------------------//
    // 3. Prepare Data
    //get the detail
    $this->trigger('auth-detail', $request, $response);

    //form hash
    $authId = $response->getResults('auth_id');
    $authUpdated = $response->getResults('auth_updated');
    $hash = md5($authId.$authUpdated);

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
    //csrf check
    $this->trigger('csrf-validate', $request, $response);

    if ($response->isError()) {
        return $this->routeTo('get', $route, $request, $response);
    }

    //captcha check
    $this->trigger('captcha-validate', $request, $response);

    if ($response->isError()) {
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
        return $this->routeTo('get', $route, $request, $response);
    }

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
    //csrf check
    $this->trigger('csrf-validate', $request, $response);

    if ($response->isError()) {
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
        return $this->routeTo('get', $route, $request, $response);
    }

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
$cradle->get('/auth/activate/:auth_id/:hash', function ($request, $response) {
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
