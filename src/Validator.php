<?php //-->
/**
 * This file is part of a Custom Project
 * (c) 2017-2019 Acme Inc
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */
namespace Cradle\Package\Auth;

use Cradle\Package\System\Object\Service as ObjectService;
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
        //auth_slug        Required
        if (!isset($data['auth_slug']) || empty($data['auth_slug'])) {
            $errors['auth_slug'] = 'Cannot be empty';
        } else if (Service::get('sql')->exists($data['auth_slug'])) {
            $errors['auth_slug'] = 'User Exists';
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
        $schema = Schema::i('auth');

        //auth_slug        Required
        if (!isset($data['auth_slug']) || empty($data['auth_slug'])) {
            $errors['auth_slug'] = 'Cannot be empty';
        } else if (!$schema->object()->service('sql')->exists('auth_slug', $data['auth_slug'])) {
            $errors['auth_slug'] = 'User does not exist';
        }

        //auth_password        Required
        if (!isset($data['auth_password']) || empty($data['auth_password'])) {
            $errors['auth_password'] = 'Cannot be empty';
        } else if (!isset($errors['auth_slug'])) {
            if (!$schema->object()->service('sql')->exists(
                    'auth_password',
                    md5($data['auth_password'])
                )
            ) {
                $errors['auth_password'] = 'Password is incorrect';
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
                if (Service::get('sql')->exists($data['auth_slug'])) {
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
