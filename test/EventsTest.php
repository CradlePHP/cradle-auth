<?php //-->
/**
 * This file is part of a package designed for the CradlePHP Project.
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

use PHPUnit\Framework\TestCase;

use Cradle\Http\Request;
use Cradle\Http\Response;

/**
 * Event test
 *
 * @vendor   Cradle
 * @package  Model
 * @author   John Doe <john@acme.com>
 */
class Cradle_Auth_EventsTest extends TestCase
{
    /**
     * @var Request $request
     */
    protected $request;

    /**
     * @var Request $response
     */
    protected $response;

    /**
     * @var int $id
     */
    protected static $id;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->request = new Request();
        $this->response = new Response();

        $this->request->load();
        $this->response->load();
    }

    /**
     * auth-create
     *
     * @covers Cradle\Module\System\Model\Validator::getCreateErrors
     * @covers Cradle\Module\System\Model\Validator::getOptionalErrors
     * @covers Cradle\Module\System\Model\Service\SqlService::create
     * @covers Cradle\Module\System\Utility\Service\AbstractElasticService::create
     * @covers Cradle\Module\System\Utility\Service\AbstractRedisService::createDetail
     */
    public function testAuthCreate()
    {
        $this->request->setStage([
            'auth_slug' => 'jane@doe.com',
            'auth_password' => 'test',
            'confirm' => 'test'
        ]);

        cradle()->trigger('auth-create', $this->request, $this->response);

        $this->assertEquals('jane@doe.com', $this->response->getResults('auth_slug'));

        self::$id = $this->response->getResults('auth_id');
        $this->assertTrue(is_numeric(self::$id));
    }

    /**
     * auth-create
     *
     * @covers Cradle\Module\System\Model\Validator::getCreateErrors
     * @covers Cradle\Module\System\Model\Validator::getOptionalErrors
     * @covers Cradle\Module\System\Model\Service\SqlService::create
     * @covers Cradle\Module\System\Utility\Service\AbstractElasticService::create
     * @covers Cradle\Module\System\Utility\Service\AbstractRedisService::createDetail
     */
    public function testAuthDetail()
    {
        $this->request->setStage([
            'auth_id' => self::$id
        ]);

        cradle()->trigger('auth-detail', $this->request, $this->response);
        $this->assertEquals(self::$id, $this->response->getResults('auth_id'));
    }

    /**
     * auth-forgot
     *
     * @covers Cradle\Module\System\Model\Service\SqlService::get
     * @covers Cradle\Module\System\Model\Service\SqlService::update
     * @covers Cradle\Module\System\Utility\Service\AbstractElasticService::remove
     * @covers Cradle\Module\System\Utility\Service\AbstractRedisService::removeDetail
     */
    public function testAuthForgot()
    {
        $this->request->setStage([
            'auth_slug' => 'jane@doe.com'
        ]);

        cradle()->trigger('auth-forgot', $this->request, $this->response);

        $this->assertEquals(self::$id, $this->response->getResults('auth_id'));
    }

    /**
     * auth-remove
     *
     * @covers Cradle\Module\System\Model\Service\SqlService::get
     * @covers Cradle\Module\System\Model\Service\SqlService::update
     * @covers Cradle\Module\System\Utility\Service\AbstractElasticService::remove
     * @covers Cradle\Module\System\Utility\Service\AbstractRedisService::removeDetail
     */
    public function testAuthRemove()
    {
        $this->request->setStage([
            'auth_id' => self::$id
        ]);

        cradle()->trigger('auth-remove', $this->request, $this->response);
        $this->assertEquals(self::$id, $this->response->getResults('auth_id'));
    }

    /**
     * role-restore
     *
     * @covers Cradle\Module\System\Model\Service\SqlService::get
     * @covers Cradle\Module\System\Model\Service\SqlService::update
     * @covers Cradle\Module\System\Utility\Service\AbstractElasticService::remove
     * @covers Cradle\Module\System\Utility\Service\AbstractRedisService::removeDetail
     */
    public function testAuthRestore()
    {
        $this->request->setStage([
            'auth_id' => self::$id
        ]);

        cradle()->trigger('auth-restore', $this->request, $this->response);
        $this->assertEquals(self::$id, $this->response->getResults('auth_id'));
    }

    /**
     * auth-search
     *
     * @covers Cradle\Module\System\Model\Service\SqlService::search
     * @covers Cradle\Module\System\Model\Service\ElasticService::search
     * @covers Cradle\Module\System\Utility\Service\AbstractRedisService::getSearch
     */
    public function testAuthSearch()
    {
        cradle()->trigger('auth-search', $this->request, $this->response);
        $this->assertEquals(1, $this->response->getResults('rows', 0, 'auth_id'));
    }

    /**
     * auth-update
     *
     * @covers Cradle\Module\System\Model\Service\SqlService::get
     * @covers Cradle\Module\System\Model\Service\SqlService::update
     * @covers Cradle\Module\System\Utility\Service\AbstractElasticService::remove
     * @covers Cradle\Module\System\Utility\Service\AbstractRedisService::removeDetail
     */
    public function testAuthUpdate()
    {
        $this->request->setStage([
            'auth_id' => self::$id,
            'auth_slug' => 'jane@doe.com',
        ]);

        cradle()->trigger('auth-update', $this->request, $this->response);

        $this->assertEquals('jane@doe.com', $this->response->getResults('auth_slug'));
        $this->assertArrayHasKey('original', $this->response->getResults());
    }

    /**
     * auth-update
     *
     * @covers Cradle\Module\System\Model\Service\SqlService::get
     * @covers Cradle\Module\System\Model\Service\SqlService::update
     * @covers Cradle\Module\System\Utility\Service\AbstractElasticService::remove
     * @covers Cradle\Module\System\Utility\Service\AbstractRedisService::removeDetail
     */
    public function testAuthLogin()
    {
        $this->request->setStage([
            'auth_slug' => 'jane@doe.com',
            'auth_password' => 'test',
        ]);

        cradle()->trigger('auth-login', $this->request, $this->response);

        $this->assertEquals('jane@doe.com', $this->response->getResults('auth_slug'));
        $this->assertEquals(self::$id, $this->response->getResults('auth_id'));
    }

    /**
     * auth-update
     *
     * @covers Cradle\Module\System\Model\Service\SqlService::get
     * @covers Cradle\Module\System\Model\Service\SqlService::update
     * @covers Cradle\Module\System\Utility\Service\AbstractElasticService::remove
     * @covers Cradle\Module\System\Utility\Service\AbstractRedisService::removeDetail
     */
    public function testAuthVerify()
    {
        $this->request->setStage([
            'auth_slug' => 'jane@doe.com',
        ]);

        cradle()->trigger('auth-verify', $this->request, $this->response);

        $this->assertEquals('jane@doe.com', $this->response->getResults('auth_slug'));
        $this->assertEquals(self::$id, $this->response->getResults('auth_id'));
    }
}
