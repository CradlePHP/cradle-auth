<?php //-->
/**
 * This file is part of a package designed for the CradlePHP Project.
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

use PHPUnit\Framework\TestCase;

use Cradle\Package\Role\Validator;

use Cradle\Package\System\Schema;

/**
 * Validator layer test
 *
 * @vendor   Cradle
 * @package  Role
 * @author   John Doe <john@acme.com>
 */
class Cradle_Role_ValidatorTest extends TestCase
{
    /**
     * @covers Cradle\Package\System\Model\Validator::getCreateErrors
     */
    public function testGetAccessErrors()
    {
        $actual = Validator::getAccessErrors([]);

        $this->assertEquals('Auth Id is required', $actual['auth_id']);
        $this->assertEquals('Role Id is required', $actual['role_id']);
    }
}
