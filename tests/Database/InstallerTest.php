<?php
/**
 * @package        awf
 * @copyright      2014-2015 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 *
 */

namespace Awf\Tests\Database;

use Awf\Application\Application;
use Awf\Database\Installer;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Utils\TestClosure;

require_once 'InstallerDataprovider.php';

/**
 * @covers      Awf\Database\Installer::<protected>
 * @covers      Awf\Database\Installer::<private>
 */
class InstallerTest extends DatabaseMysqliCase
{
    /**
     * @covers          Awf\Database\Installer::__construct
     */
    public function test__construct()
    {
        $container = Application::getInstance()->getContainer();
        $installer = new Installer($container);

        $xml = ReflectionHelper::getValue($installer, 'xmlDirectory');
        $db  = ReflectionHelper::getValue($installer, 'db');

        $this->assertEquals($container->basePath.'/assets/sql/xml', $xml, 'Installer::__construct Failed to set the XML directory');
        $this->assertSame($container->db, $db, 'Installer::__construct Failed to set the db object');
    }

    /**
     * @covers          Awf\Database\Installer::setXmlDirectory
     */
    public function testSetXmlDirectory()
    {
        $dir       = __DIR__.'/xml';
        $container = Application::getInstance()->getContainer();
        $installer = new Installer($container);

        $installer->setXmlDirectory($dir);

        $xml = ReflectionHelper::getValue($installer, 'xmlDirectory');

        $this->assertEquals($xml, $dir, 'Installer::setXmlDirectory Failed to set XML directory');
    }

    /**
     * @covers          Awf\Database\Installer::getXmlDirectory
     */
    public function testGetXmlDirectory()
    {
        $dir       = __DIR__.'/xml';
        $container = Application::getInstance()->getContainer();
        $installer = new Installer($container);

        ReflectionHelper::setValue($installer, 'xmlDirectory', $dir);

        $xml = $installer->getXmlDirectory();

        $this->assertEquals($dir, $xml, 'Installer::getXmlDirectory Failed to get XML directory');
    }
}