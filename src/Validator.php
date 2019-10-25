<?php //-->
/**
 * This file is part of a Custom Project
 * (c) 2017-2019 Acme Inc
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */
namespace Cradle\Package\Auth;

use Cradle\Package\System\Schema;
use Cradle\Module\System\Utility\Validator as UtilityValidator;

/**
 * Validator layer
 *
 * @vendor   Acme
 * @package  auth
 * @author   John Doe <john@acme.com>
 * @standard PSR-2
 */
class Validator
{
    /**
     * Returns Create Errors
     *
     * @param *array $data
     * @param array  $errors
     *
     * @return array
     */
    public static function getCreateErrors(array $data, array $errors = [])
    {
        $schema = Schema::i('auth');

        //auth_slug        Required
        if (!isset($data['auth_slug']) || empty($data['auth_slug'])) {
            $errors['auth_slug'] = 'Cannot be empty';
        } else if ($schema->model()->service('sql')->exists('auth_slug', $data['auth_slug'])) {
            $errors['auth_slug'] = 'Email Already Exists';
        }

        //auth_password        Required
        if (!isset($data['auth_password']) || empty($data['auth_password'])) {
            $errors['auth_password'] = 'Cannot be empty';
        }

        //confirm        NOT IN SCHEMA
        if (!isset($data['confirm']) || empty($data['confirm'])) {
            $errors['confirm'] = 'Cannot be empty';
        } else if ($data['confirm'] !== $data['auth_password']) {
            $errors['confirm'] = 'Passwords do not match';
        }

        //also add optional errors
        return self::getOptionalErrors($data, $errors);
    }

    /**
     * Returns Login Errors
     *
     * @param *array $data
     * @param array  $errors
     *
     * @return array
     */
    public static function getForgotErrors(array $data, array $errors = [])
    {
        //auth_slug        Required
        if (!isset($data['auth_slug']) || empty($data['auth_slug'])) {
            $errors['auth_slug'] = 'Cannot be empty';
        }

        // if there's a slug and it's not specified
        // to allow forgot password for inactive accounts
        if (isset($data['auth_slug']) && !empty($data['auth_slug'])
            && (!isset($data['allow_inactive']) || empty($data['allow_inactive'])
                || ((isset($data['allow_inactive']) && !$data['allow_inactive'])))
        ) {
            $resource = Schema::i('auth')
                ->model()
                ->service('sql')
                ->getResource();

            // check if exists
            $row = $resource
                ->search('auth')
                ->addFilter('auth_slug = %s', $data['auth_slug'])
                ->getRow();

            if (isset($row['auth_active']) && !$row['auth_active']) {
                $errors['auth_slug'] = 'Account is already deactivated. If you
                    believe this is an error, you may contact admin.';
            }
        }

        return $errors;
    }

    /**
     * Returns Login Errors
     *
     * @param *array $data
     * @param array  $errors
     *
     * @return array
     */
    public static function getLoginErrors(array $data, array $errors = [])
    {
        //auth_slug        Required
        if (!isset($data['auth_slug']) || empty($data['auth_slug'])) {
            $errors['auth_slug'] = 'Cannot be empty';
        }

        //auth_password        Required
        if (!isset($data['auth_password']) || empty($data['auth_password'])) {
            $errors['auth_password'] = 'Cannot be empty';
        } else {
            $resource = Schema::i('auth')
                ->model()
                ->service('sql')
                ->getResource();

            // check if exists
            $row = $resource
                ->search('auth')
                ->addFilter('auth_slug = %s', $data['auth_slug'])
                ->getRow();

            // if it is not a valid password
            if (
                // both updated hashing
                !password_verify(
                    $data['auth_password'],
                    $row['auth_password']
                )
                // and legacy hashing
                && md5($data['auth_password']) !== $row['auth_password']
            ) {
                //report the error
                $errors['auth_password'] = 'Password is incorrect';
            } else if (
                // does the password need to be upgraded?
                password_needs_rehash(
                    $row['auth_password'],
                    PASSWORD_DEFAULT
                )
            ) {
                //upgrade the hash in the database
                $row['auth_password'] = password_hash(
                    $data['auth_password'],
                    PASSWORD_DEFAULT
                );

                $resource->model($row)->save('auth');
            }
        }

        return $errors;
    }

    /**
     * Returns Login Errors
     *
     * @param *array $data
     * @param array  $errors
     *
     * @return array
     */
    public static function getRecoverErrors(array $data, array $errors = [])
    {
        //auth_password        Required
        if (!isset($data['auth_password']) || empty($data['auth_password'])) {
            $errors['auth_password'] = 'Cannot be empty';
        }

        //confirm        NOT IN SCHEMA
        if (!isset($data['confirm']) || empty($data['confirm'])) {
            $errors['confirm'] = 'Cannot be empty';
        } else if ($data['confirm'] !== $data['auth_password']) {
            $errors['confirm'] = 'Passwords do not match';
        }

        return $errors;
    }

    /**
     * Returns Update Errors
     *
     * @param *array $data
     * @param array  $errors
     *
     * @return array
     */
    public static function getUpdateErrors(array $data, array $errors = [])
    {
        // auth_id            Required
        if (!isset($data['auth_id']) || !is_numeric($data['auth_id'])) {
            $errors['auth_id'] = 'Invalid ID';
        }

        //auth_slug        Required
        if (isset($data['auth_slug']) && empty($data['auth_slug'])) {
            $errors['auth_slug'] = 'Cannot be empty, if set';
        //if there are no auth id errors
        } else if (isset($data['auth_slug']) && !isset($errors['auth_id'])) {
            //get the auth that we are updating
            $row = Service::get('sql')
                ->get($data['auth_id']);

            //if the auth slug is changing
            if ($row['auth_slug'] !== $data['auth_slug']) {
                //check if new auth_slug is taken
                if ($schema->model()->service('sql')->exists('auth_slug', $data['auth_slug'])) {
                    $errors['auth_slug'] = 'Already Taken';
                }
            }
        }

        //confirm            NOT IN SCHEMA
        if ((
                !empty($data['auth_password']) || !empty($data['confirm'])
            )
            && $data['confirm'] !== $data['auth_password']
        ) {
            $errors['confirm'] = 'Passwords do not match';
        }

        //also add optional errors
        return self::getOptionalErrors($data, $errors);
    }

    /**
     * Returns Login Errors
     *
     * @param *array $data
     * @param array  $errors
     *
     * @return array
     */
    public static function getVerifyErrors(array $data, array $errors = [])
    {
        //auth_slug        Required
        if (!isset($data['auth_slug']) || empty($data['auth_slug'])) {
            $errors['auth_slug'] = 'Cannot be empty';
        }

        return $errors;
    }

    /**
     * Returns Optional Errors
     *
     * @param *array $data
     * @param array  $errors
     *
     * @return array
     */
    public static function getOptionalErrors(array $data, array $errors = [])
    {
        // auth_flag - small
        if (isset($data['auth_flag']) && !UtilityValidator::isSmall($data['auth_flag'])) {
            $errors['auth_flag'] = 'Should be between 0 and 9';
        }

        return $errors;
    }
}
