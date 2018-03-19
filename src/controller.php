<?php //-->
/**
 * This file is part of a Custom Package.
 */

// Back End Controllers

/**
 * Renders a create form
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/auth/create', function ($request, $response) {});

/**
 * Renders a create form
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/auth/detail/:auth_id', function ($request, $response) {});

/**
 * Removes a auth
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/auth/remove/:auth_id', function ($request, $response) {});

/**
 * Restores a auth
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/auth/restore/:auth_id', function ($request, $response) {});

/**
 * Renders a search page
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/auth/search', function ($request, $response) {});

/**
 * Renders an update form
 *
 * @param Request $request
 * @param Response $response
 */
$this->get('/admin/auth/update/:auth_id', function ($request, $response) {});

/**
 * Processes a create form
 *
 * @param Request $request
 * @param Response $response
 */
$this->post('/admin/auth/create', function ($request, $response) {});

/**
 * Processes an update form
 *
 * @param Request $request
 * @param Response $response
 */
$this->post('/admin/auth/update/:auth_id', function ($request, $response) {});

// Front End Controllers
