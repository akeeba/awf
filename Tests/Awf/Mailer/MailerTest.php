<?php
/**
 * Created by PhpStorm.
 * User: Nicholas
 * Date: 6/6/2014
 * Time: 4:03 μμ
 */

namespace Tests\Awf\Mailer;

use Awf\Mailer\Mailer;
use Tests\Helpers\ReflectionHelper;
use Tests\Stubs\Fakeapp\Container;

/**
 * Class MailerTest
 *
 * @coversDefaultClass Awf\Mailer\Mailer
 *
 * @package            Tests\Awf\Mailer
 */
class MailerTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @var Mailer
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return void
	 */
	protected function setUp()
	{
		parent::setUp();

		$container = new Container();

		$this->object = new Mailer($container);
	}

	/**
	 * Test...
	 *
	 * @todo Implement testSend(). How can you do that without sending a mail?
	 *
	 * @return void
	 */
	public function testSend()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test...
	 *
	 * @dataProvider getTestSetSender
	 *
	 * @return void
	 */
	public function testSetSender($sender, $expectedFrom, $expectedFromName, $expectedSender)
	{
		$this->object->setSender($sender);

		$this->assertEquals(
			$expectedFrom,
			$this->object->From
		);

		$this->assertEquals(
			$expectedFromName,
			$this->object->FromName
		);

		$this->assertEquals(
			$expectedSender,
			$this->object->Sender
		);
	}

	public function getTestSetSender()
	{
		return array(
			array('joe@example.com', 'joe@example.com', '', 'joe@example.com'),
			array(array('joe@example.com', 'Joe'), 'joe@example.com', 'Joe', 'joe@example.com'),
			array(array('mike@example.com', '', false), 'mike@example.com', '', ''),
			array(array('john@example.com', '', true), 'john@example.com', '', 'john@example.com'),
		);
	}

	/**
	 * Test...
	 *
	 * @todo Implement testSetSubject().
	 *
	 * @return void
	 */
	public function testSetSubject()
	{
		$subject = 'Foo bar baz';
		$result = $this->object->setSubject($subject);

		$this->assertEquals(
			$subject,
			$this->object->Subject
		);

		$this->assertInstanceOf(
			'\\Awf\\Mailer\\Mailer',
			$result
		);
	}

	/**
	 * Test...
	 *
	 * @return void
	 */
	public function testSetBody()
	{
		// Plain text
		$body = 'Foo bar baz';
		$result = $this->object->setBody($body);

		$this->assertEquals(
			$body,
			$this->object->Body
		);

		$this->assertInstanceOf(
			'\\Awf\\Mailer\\Mailer',
			$result
		);

		// HTML
		$this->object->isHtml(true);
		$body = '<p>Foo bar baz</p>';
		$result = $this->object->setBody($body);

		$this->assertEquals(
			$body,
			$this->object->Body
		);

		$this->assertEmpty(
			$this->object->AltBody
		);

		$this->assertInstanceOf(
			'\\Awf\\Mailer\\Mailer',
			$result
		);
	}

	/**
	 * Provides test data for request format detection.
	 *
	 * @return array
	 */
	public function seedTestAdd()
	{
		// Recipient, name, method
		return array(
			array('test@example.com', 'test_name', 'AddAddress', array(array('test@example.com', 'test_name'))),
			array(array('test_1@example.com', 'test_2@example.com'), 'test_name', 'AddAddress',
				array(array('test_1@example.com', 'test_name'), array('test_2@example.com', 'test_name'))),
			array(array('test_1@example.com', 'test_2@example.com'), array('test_name1', 'test_name2'), 'AddAddress',
				array(array('test_1@example.com', 'test_name1'), array('test_2@example.com', 'test_name2'))),
			array('test@example.com', 'test_name', 'AddCC', array(array('test@example.com', 'test_name'))),
			array(array('test_1@example.com', 'test_2@example.com'), 'test_name', 'AddCC',
				array(array('test_1@example.com', 'test_name'), array('test_2@example.com', 'test_name'))),
			array(array('test_1@example.com', 'test_2@example.com'), array('test_name1', 'test_name2'), 'AddCC',
				array(array('test_1@example.com', 'test_name1'), array('test_2@example.com', 'test_name2'))),
			array('test@example.com', 'test_name', 'AddBCC', array(array('test@example.com', 'test_name'))),
			array(array('test_1@example.com', 'test_2@example.com'), 'test_name', 'AddBCC',
				array(array('test_1@example.com', 'test_name'), array('test_2@example.com', 'test_name'))),
			array(array('test_1@example.com', 'test_2@example.com'), array('test_name1', 'test_name2'), 'AddBCC',
				array(array('test_1@example.com', 'test_name1'), array('test_2@example.com', 'test_name2'))),
			array('test@example.com', 'test_name', 'AddReplyTo',
				array('test@example.com' => array('test@example.com', 'test_name'))),
			array(array('test_1@example.com', 'test_2@example.com'), 'test_name', 'AddReplyTo',
				array(
					'test_1@example.com' => array('test_1@example.com', 'test_name'),
					'test_2@example.com' => array('test_2@example.com', 'test_name')
				)
			),
			array(array('test_1@example.com', 'test_2@example.com'), array('test_name1', 'test_name2'), 'AddReplyTo',
				array(
					'test_1@example.com' => array('test_1@example.com', 'test_name1'),
					'test_2@example.com' => array('test_2@example.com', 'test_name2')
				)
			)
		);
	}

	/**
	 * Tests the add method
	 *
	 * @param   mixed  $recipient Either a string or array of strings [email address(es)]
	 * @param   mixed  $name      Either a string or array of strings [name(s)]
	 * @param   string $method    The parent method's name.
	 * @param   array  $expected  The expected array.
	 *
	 * @covers        Awf\Mailer\Mailer::add
	 * @dataProvider  seedTestAdd
	 *
	 * @return void
	 */
	public function testAdd($recipient, $name, $method, $expected)
	{
		ReflectionHelper::invoke($this->object, 'add', $recipient, $name, $method);

		switch ($method)
		{
			case 'AddAddress':
				$type = 'to';
				break;
			case 'AddCC':
				$type = 'cc';
				break;
			case 'AddBCC':
				$type = 'bcc';
				break;
			case 'AddReplyTo':
				$type = 'ReplyTo';
				break;
		}

		$this->assertThat($expected, $this->equalTo(ReflectionHelper::getValue($this->object, $type)));
	}

	/**
	 * Tests the addRecipient method.
	 *
	 * @covers  Awf\Mailer\Mailer::addRecipient
	 *
	 * @return void
	 */
	public function testAddRecipient()
	{
		$recipient = 'test@example.com';
		$name      = 'test_name';
		$expected  = array(array('test@example.com', 'test_name'));

		$this->object->addRecipient($recipient, $name);
		$this->assertThat($expected, $this->equalTo(ReflectionHelper::getValue($this->object, 'to')));
	}

	/**
	 * Tests the addCC method.
	 *
	 * @covers  Awf\Mailer\Mailer::addCC
	 *
	 * @return void
	 */
	public function testAddCC()
	{
		$recipient = 'test@example.com';
		$name      = 'test_name';
		$expected  = array(array('test@example.com', 'test_name'));

		$this->object->addCC($recipient, $name);
		$this->assertThat($expected, $this->equalTo(ReflectionHelper::getValue($this->object, 'cc')));
	}

	/**
	 * Tests the addBCC method.
	 *
	 * @covers  Awf\Mailer\Mailer::addBCC
	 *
	 * @return void
	 */
	public function testAddBCC()
	{
		$recipient = 'test@example.com';
		$name      = 'test_name';
		$expected  = array(array('test@example.com', 'test_name'));

		$this->object->addBCC($recipient, $name);
		$this->assertThat($expected, $this->equalTo(ReflectionHelper::getValue($this->object, 'bcc')));
	}

	/**
	 * Test...
	 *
	 * @todo Implement testAddAttachment().
	 *
	 * @return void
	 */
	public function testAddAttachment()
	{
		$attachments = array(__FILE__);
		$names       = array(basename(__FILE__));

		$container = new Container();
		$mail = new Mailer($container);
		$mail->addAttachment($attachments, $names);

		$actual             = $mail->GetAttachments();
		$actual_attachments = array();
		$actual_names       = array();

		foreach ($actual as $attach)
		{
			array_push($actual_attachments, $attach[0]);
			array_push($actual_names, $attach[2]);
		}

		$this->assertThat($attachments, $this->equalTo($actual_attachments));
		$this->assertThat($names, $this->equalTo($actual_names));
	}

	/**
	 * Tests the addReplyTo method.
	 *
	 * @covers  Awf\Mailer\Mailer::addReplyTo
	 *
	 * @return void
	 */
	public function testAddReplyTo()
	{
		$recipient = 'test@example.com';
		$name      = 'test_name';
		$expected  = array('test@example.com' => array('test@example.com', 'test_name'));

		$this->object->addReplyTo($recipient, $name);
		$this->assertThat($expected, $this->equalTo(ReflectionHelper::getValue($this->object, 'ReplyTo')));
	}

	/**
	 * Tests the IsHTML method.
	 *
	 * @covers  Awf\Mailer\Mailer::IsHTML
	 *
	 * @return void
	 */
	public function testIsHTML()
	{
		$returnedObject = $this->object->isHtml(false);

		$this->assertThat('text/plain', $this->equalTo($this->object->ContentType));

		// Test to ensure that a Awf\Mailer\Mailer object is being returned for chaining
		$this->assertInstanceOf('\\Awf\\Mailer\\Mailer', $returnedObject);
	}

	/**
	 * Test...
	 *
	 * @todo Implement testUseSendmail().
	 *
	 * @return void
	 */
	public function testUseSendmail()
	{
		$container = new Container();
		$mail = $this->getMock('Awf\\Mailer\\Mailer', array('SetLanguage', 'IsSendmail', 'IsMail'), array($container));

		$mail->expects(
			$this->once()
		)
			->method('IsSendmail');

		$this->assertThat(
			$mail->useSendmail('/usr/sbin/sendmail'),
			$this->equalTo(true)
		);
	}

	/**
	 * Test data for testUseSMTP method
	 *
	 * @return  array
	 *
	 * @since   12.1
	 */
	public function dataUseSMTP()
	{
		return array(
			'SMTP without Authentication' => array(
				null,
				'example.com',
				null,
				null,
				false,
				null,
				array(
					'called' => 'IsSMTP',
					'return' => true
				)
			)
		);
	}

	/**
	 * Test for the Awf\Mailer\Mailer::useSMTP method.
	 *
	 * @param   string  $auth     SMTP Authentication
	 * @param   string  $host     SMTP Host
	 * @param   string  $user     SMTP Username
	 * @param   string  $pass     SMTP Password
	 * @param   string  $secure   Use secure methods
	 * @param   integer $port     The SMTP port
	 * @param   string  $expected The expected result
	 *
	 * @return  void
	 *
	 * @since         12.1
	 *
	 * @dataProvider  dataUseSMTP
	 */
	public function testUseSMTP($auth, $host, $user, $pass, $secure, $port, $expected)
	{
		$container = new Container();
		$mail = $this->getMock('Awf\\Mailer\\Mailer', array('SetLanguage', 'IsSMTP', 'IsMail'), array($container));

		$mail->expects(
			$this->once()
		)
			->method($expected['called']);

		$this->assertThat(
			$mail->useSMTP($auth, $host, $user, $pass, $secure, $port),
			$this->equalTo($expected['return'])
		);
	}

	/**
	 * Test...
	 *
	 * @todo Implement testSendMail().
	 *
	 * @return void
	 */
	public function testSendMail()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}
}
 