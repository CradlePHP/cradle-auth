<?php //-->
/**
 * This file is part of a Custom Package.
 */

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
