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
$this->on('auth-create', function ($request, $response) {});

/**
 * Creates a auth
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('auth-detail', function ($request, $response) {});

/**
 * Removes a auth
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('auth-remove', function ($request, $response) {});

/**
 * Restores a auth
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('auth-restore', function ($request, $response) {});

/**
 * Searches auth
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('auth-search', function ($request, $response) {});

/**
 * Updates a auth
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('auth-update', function ($request, $response) {});

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
