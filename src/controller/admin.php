<?php //-->
/**
 * This file is part of a Custom Project.
 * (c) 2016-2018 Acme Products Inc.
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

use Cradle\Package\System\Schema;

/**
 * Render the Admin Settings Page
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/system/model/auth/settings', function ($request, $response) {
    //----------------------------//
    // 1. Prepare Data
    //get schema data
    $schema = Schema::i('auth');

    //Prepare body
    $data = ['item' => $request->getPost()];

    if (empty($data['item'])) {
        $global = $this->package('global');
        $item['service']['facebook'] = $global->config('services', 'facebook-main');
        $item['service']['twitter'] = $global->config('services', 'twitter-main');
        $item['service']['linkedin'] = $global->config('services', 'linkedin-main');
        $item['service']['google'] = $global->config('services', 'google-main');
        $item['service']['captcha'] = $global->config('services', 'captcha-main');
        $item['submission']['attempt'] = $global->config('auth', 'submission');

        foreach ($item['service'] as $service => $setting) {
            if (!is_array($setting)) {
                continue;
            }

            foreach ($setting as $key => $value) {
                if (strpos($value, '<') === 0) {
                    $setting[$key] = null;
                }
            }

            $item['service'][$service] = $setting;
        }

        $data['item'] = $item;
    }

    //also pass the schema to the template
    $data['schema'] = $schema->getAll();

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
    $class = 'admin-system-model-auth-settings page-admin';
    $title = $this->package('global')->translate('Authentication Settings');

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
            'admin/settings',
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
    $this->trigger('admin-render-page', $request, $response);
});

/**
 * Process the Admin Settings Page
 *
 * @param Request $request
 * @param Response $response
 */
$this->post('/admin/system/model/auth/settings', function ($request, $response) {
    //----------------------------//
    // 1. Prepare Data
    $config = $this->package('global')->config('services');
    $services = $request->getStage('service');
    $submission = $request->getStage('submission');

    //----------------------------//
    // 2. Validate Data
    //----------------------------//
    // 3. Process Request
    if (is_array($services)) {
        $invalid = ['sql', 'elastic', 'redis', 'rabbitmq', 's3', 'mail'];
        foreach ($services as $name => $service) {
            if (in_array($name, $invalid)) {
                continue;
            }

            foreach ($service as $key => $value) {
                if ($key !== 'active' && !trim($value)) {
                    $service[$key] = sprintf(
                        '<%s %s>',
                        strtoupper($name),
                        strtoupper($key)
                    );
                }
            }

            $config[$name . '-main'] = $service;
        }

        $this->package('global')->config('services', $config);
    }

    $submission = [];
    $settings = ['captcha' => 2, 'lockout' => 4, 'wait' => 5];
    foreach ($settings as $setting => $default) {
        $submission[$setting] = $request->getStage(
            'submission',
            'attempt',
            $setting
        );

        if (!is_numeric($submission[$setting])) {
            $submission[$setting] = $default;
        }
    }

    $this->package('global')->config('auth', 'submission', $submission);

    //----------------------------//
    // 4. Interpret Results
    //record logs
    $this->log(
        'updated auth settings',
        $request,
        $response,
        'settings'
    );

    //redirect
    $redirect = '/admin/system/model/auth/search';

    //if there is a specified redirect
    if ($request->getStage('redirect_uri')) {
        //set the redirect
        $redirect = $request->getStage('redirect_uri');
    }

    //if we dont want to redirect
    if ($redirect === 'false') {
        return;
    }

    //add a flash
    $this->package('global')->flash('Auth settings were updated', 'success');
    $this->package('global')->redirect($redirect);
});
