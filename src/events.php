<?php //-->
/**
 * This file is part of a Custom Package.
 */
use Cradle\Package\Auth\Validator as AuthValidator;

/**
 * Creates a auth
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('auth-create', function ($request, $response) {
    //----------------------------//
    // 1. Get Data
    $data = [];
    if($request->hasStage()) {
        $data = $request->getStage();
    }

    if(!isset($data['auth_active'])) {
        $request->setStage('auth_active', 0);
    }

    //----------------------------//
    // 2. Validate Data
    $errors = AuthValidator::getCreateErrors($data);

    //if there are errors
    if (!empty($errors)) {
        return $response
            ->setError(true, 'Invalid Parameters')
            ->set('json', 'validation', $errors);
    }

    //----------------------------//
    // 3. Process Data
    // check profile
    if (!isset($data['profile_id'])) {
        //create profile
        if (!$request->getStage('profile_name')) {
            // set profile name
            $request->setStage('profile_name', $request->getStage('auth_slug'));
        }

        // set profile as schema
        $request->setStage('schema', 'profile');
        // trigger model create
        $this->trigger('system-model-create', $request, $response);

        if ($response->isError()) {
            return;
        }

        // get profile
        $profile = $response->getResults();
        // set profile id
        $request->setStage('profile_id', $profile['profile_id']);
    }

    //set auth as schema
    $request->setStage('schema', 'auth');

    //trigger model create
    $this->trigger('system-model-create', $request, $response);
});

/**
 * Creates a auth
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('auth-detail', function ($request, $response) {
    //set profile as schema
    $request->setStage('schema', 'auth');

    //trigger model detail
    $this->trigger('system-model-detail', $request, $response);
});

/**
 * Auth Forgot Job
 *
 * @param Request $request
 * @param Response $response
 */
$cradle->on('auth-forgot', function ($request, $response) {
    //----------------------------//
    // 1. Get Data
    $this->trigger('auth-detail', $request, $response);

    if ($response->isError()) {
        return;
    }

    $data = [];
    if ($request->hasStage()) {
        $data = $request->getStage();
    }

    //----------------------------//
    // 3. Validate Data
    //validate
    $errors = AuthValidator::getForgotErrors($data);

    //if there are errors
    if (!empty($errors)) {
        return $response
            ->setError(true, 'Invalid Parameters')
            ->set('json', 'validation', $errors);
    }

    //----------------------------//
    // 4. Process Data
    //send mail
    $request->setSoftStage($response->getResults());

    //because there's no way the CLI queue would know the host
    $protocol = 'http';
    if ($request->getServer('SERVER_PORT') === 443) {
        $protocol = 'https';
    }

    $request->setStage('host', $protocol . '://' . $request->getServer('HTTP_HOST'));

    //try to queue, and if not
    //if (!$this->package('global')->queue('auth-forgot-mail', $data)) {
        //send mail manually
        $this->trigger('auth-forgot-mail', $request, $response);
    //}

    //return response format
    $response->setError(false);
});

/**
 * Auth Forgot Mail Job (supporting job)
 *
 * @param Request $request
 * @param Response $response
 */
$cradle->on('auth-forgot-mail', function ($request, $response) {
    $config = $this->package('global')->service('mail-main');

    if (!$config) {
        return;
    }

    //if it's not configured
    if ($config['user'] === '<EMAIL ADDRESS>'
        || $config['pass'] === '<EMAIL PASSWORD>'
    ) {
        return;
    }

    //form hash
    $authId = $request->getStage('auth_id');
    $authUpdated = $request->getStage('auth_updated');
    $hash = md5($authId.$authUpdated);

    //form link
    $host = $request->getStage('host');
    $link = $host . '/auth/recover/' . $authId . '/' . $hash;

    //prepare data
    $from = [];
    $from[$config['user']] = $config['name'];

    $to = [];
    $to[$request->getStage('auth_slug')] = null;

    $subject = $this->package('global')->translate('Password Recovery from Cradle!');
    $handlebars = $this->package('global')->handlebars();

    $contents = file_get_contents(__DIR__ . '/template/email/recover.txt');
    $template = $handlebars->compile($contents);
    $text = $template(['link' => $link]);

    $contents = file_get_contents(__DIR__ . '/template/email/recover.html');
    $template = $handlebars->compile($contents);
    $html = $template([
        'host' => $host,
        'link' => $link
    ]);

    //send mail
    $message = new Swift_Message($subject);
    $message->setFrom($from);
    $message->setTo($to);
    $message->setBody($html, 'text/html');
    $message->addPart($text, 'text/plain');

    $transport = Swift_SmtpTransport::newInstance();
    $transport->setHost($config['host']);
    $transport->setPort($config['port']);
    $transport->setEncryption($config['type']);
    $transport->setUsername($config['user']);
    $transport->setPassword($config['pass']);

    $swift = Swift_Mailer::newInstance($transport);
    $swift->send($message, $failures);
});

/**
 * Removes a auth
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('auth-remove', function ($request, $response) {
    // set auth as schema
    $request->setStage('schema', 'auth');
    // trigger model create
    $this->trigger('system-model-remove', $request, $response);
});

/**
 * Restores a auth
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('auth-restore', function ($request, $response) {
    // set auth as schema
    $request->setStage('schema', 'auth');
    // trigger model create
    $this->trigger('system-model-restore', $request, $response);
});

/**
 * Searches auth
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('auth-search', function ($request, $response) {
    //set auth as schema
    $request->setStage('schema', 'auth');

    //trigger model search
    $this->trigger('system-model-search', $request, $response);
});

/**
 * Updates a auth
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('auth-update', function ($request, $response) {
    //set auth as schema
    $request->setStage('schema', 'auth');

    //trigger model search
    $this->trigger('system-model-update', $request, $response);
});

/**
 * Auth Login Job
 *
 * @param Request $request
 * @param Response $response
 */
$cradle->on('auth-login', function ($request, $response) {
    //----------------------------//
    // 1. Get Data
    $data = [];
    if ($request->hasStage()) {
        $data = $request->getStage();
    }

    //----------------------------//
    // 2. Validate Data
    $errors = AuthValidator::getLoginErrors($data);

    //if there are errors
    if (!empty($errors)) {
        return $response
            ->setError(true, 'Invalid Parameters')
            ->set('json', 'validation', $errors);
    }

    //----------------------------//
    // 3. Process Data
    $this->trigger('auth-detail', $request, $response);
});

/**
 * Auth Recover Job
 *
 * @param Request $request
 * @param Response $response
 */
$cradle->on('auth-recover', function ($request, $response) {
    //----------------------------//
    // 1. Get Data
    $data = [];
    if ($request->hasStage()) {
        $data = $request->getStage();
    }

    //----------------------------//
    // 2. Validate Data
    $errors = AuthValidator::getRecoverErrors($data);

    //if there are errors
    if (!empty($errors)) {
        return $response
            ->setError(true, 'Invalid Parameters')
            ->set('json', 'validation', $errors);
    }

    //----------------------------//
    // 3. Process Data
    //update
    $this->trigger('auth-update', $request, $response);

    //return response format
    $response->setError(false);
});

/**
 * Auth Verify Job
 *
 * @param Request $request
 * @param Response $response
 */
$cradle->on('auth-verify', function ($request, $response) {
    //----------------------------//
    // 1. Get Data
    $data = [];
    if ($request->hasStage()) {
        $data = $request->getStage();
    }

    //----------------------------//
    // 2. Validate Data
    $errors = AuthValidator::getVerifyErrors($data);

    //if there are errors
    if (!empty($errors)) {
        return $response
            ->setError(true, 'Invalid Parameters')
            ->set('json', 'validation', $errors);
    }

    //----------------------------//
    // 3. Prepare Data
    //get the auth detail
    $this->trigger('auth-detail', $request, $response);

    //if there's an error
    if ($response->isError()) {
        return;
    }

    //send mail
    $request->setSoftStage($response->getResults());

    //because there's no way the CLI queue would know the host
    $protocol = 'http';
    if ($request->getServer('SERVER_PORT') === 443) {
        $protocol = 'https';
    }

    $request->setStage('host', $protocol . '://' . $request->getServer('HTTP_HOST'));
    $data = $request->getStage();

    //----------------------------//
    // 3. Process Data
    //try to queue, and if not
    //if (!$this->package('global')->queue('auth-verify-mail', $data)) {
        //send mail manually
        $this->trigger('auth-verify-mail', $request, $response);
    //}

    //return response format
    $response->setError(false);
});

/**
 * Auth Verify Mail Job (supporting job)
 *
 * @param Request $request
 * @param Response $response
 */
$cradle->on('auth-verify-mail', function ($request, $response) {
    $config = $this->package('global')->service('mail-main');

    if (!$config) {
        return;
    }

    //if it's not configured
    if ($config['user'] === '<EMAIL ADDRESS>'
        || $config['pass'] === '<EMAIL PASSWORD>'
    ) {
        return;
    }

    //form hash
    $authId = $request->getStage('auth_id');
    $authUpdated = $request->getStage('auth_updated');
    $hash = md5($authId.$authUpdated);

    //form link
    $host = $request->getStage('host');
    $link = $host . '/auth/activate/' . $authId . '/' . $hash;

    //prepare data
    $from = [];
    $from[$config['user']] = $config['name'];

    $to = [];
    $to[$request->getStage('auth_slug')] = null;

    $subject = $this->package('global')->translate('Account Verification from Cradle!');
    $handlebars = $this->package('global')->handlebars();

    $contents = file_get_contents(__DIR__ . '/template/email/verify.txt');
    $template = $handlebars->compile($contents);
    $text = $template(['link' => $link]);

    $contents = file_get_contents(__DIR__ . '/template/email/verify.html');
    $template = $handlebars->compile($contents);
    $html = $template([
        'host' => $host,
        'link' => $link
    ]);

    //send mail
    $message = new Swift_Message($subject);
    $message->setFrom($from);
    $message->setTo($to);
    $message->setBody($html, 'text/html');
    $message->addPart($text, 'text/plain');

    $transport = Swift_SmtpTransport::newInstance();
    $transport->setHost($config['host']);
    $transport->setPort($config['port']);
    $transport->setEncryption($config['type']);
    $transport->setUsername($config['user']);
    $transport->setPassword($config['pass']);

    $swift = Swift_Mailer::newInstance($transport);
    $swift->send($message, $failures);
});
