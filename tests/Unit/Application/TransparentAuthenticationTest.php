<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Application;

use Awf\Application\TransparentAuthentication;
use Awf\Container\Container;
use Awf\Encrypt\Aes;
use Awf\Encrypt\Totp;
use Awf\Input\Input;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TransparentAuthentication::class)]
class TransparentAuthenticationTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Infrastructure
    // -------------------------------------------------------------------------

    /**
     * Create a Container with a synthetic Input pre-loaded with $params.
     */
    private function makeContainer(array $params = []): Container
    {
        $container = new Container([
            'application_name'     => 'TestApp',
            'applicationNamespace' => '\\TestApp',
            'session_segment_name' => 'testapp',
            'basePath'             => sys_get_temp_dir(),
            'languagePath'         => sys_get_temp_dir(),
            'temporaryPath'        => sys_get_temp_dir(),
            'templatePath'         => sys_get_temp_dir(),
            'filesystemBase'       => sys_get_temp_dir(),
        ]);

        $container['input'] = new Input($params);

        return $container;
    }

    /**
     * Create a TransparentAuthentication instance with a fresh Container.
     */
    private function makeTA(array $params = []): TransparentAuthentication
    {
        return new TransparentAuthentication($this->makeContainer($params));
    }

    // Backup / restore $_SERVER keys that individual tests may touch.
    private array $serverBackup = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
    }

    protected function tearDown(): void
    {
        // Restore any $_SERVER keys that tests modified.
        foreach (['PHP_AUTH_USER', 'PHP_AUTH_PW'] as $key) {
            if (array_key_exists($key, $this->serverBackup)) {
                $_SERVER[$key] = $this->serverBackup[$key];
            } else {
                unset($_SERVER[$key]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // addAuthenticationMethod / removeAuthenticationMethod
    // -------------------------------------------------------------------------

    public function testAddAuthenticationMethodAddsNewMethod(): void
    {
        $ta = $this->makeTA();
        // Default methods are 3, 4, 5
        $ta->setAuthenticationMethods([3]);
        $ta->addAuthenticationMethod(4);
        self::assertContains(4, $ta->getAuthenticationMethods());
    }

    public function testAddAuthenticationMethodDoesNotDuplicate(): void
    {
        $ta = $this->makeTA();
        $ta->setAuthenticationMethods([3]);
        $ta->addAuthenticationMethod(3);
        self::assertCount(1, $ta->getAuthenticationMethods());
    }

    public function testRemoveAuthenticationMethodRemovesExisting(): void
    {
        $ta = $this->makeTA();
        $ta->setAuthenticationMethods([3, 4, 5]);
        $ta->removeAuthenticationMethod(4);
        self::assertNotContains(4, $ta->getAuthenticationMethods());
        self::assertContains(3, $ta->getAuthenticationMethods());
        self::assertContains(5, $ta->getAuthenticationMethods());
    }

    public function testRemoveAuthenticationMethodNopOnMissing(): void
    {
        $ta = $this->makeTA();
        $ta->setAuthenticationMethods([3, 5]);
        $ta->removeAuthenticationMethod(4); // not present – should be a no-op
        self::assertCount(2, $ta->getAuthenticationMethods());
    }

    // -------------------------------------------------------------------------
    // getTransparentAuthenticationCredentials — no methods enabled
    // -------------------------------------------------------------------------

    public function testGetCredentialsReturnsNullWhenNoMethodsEnabled(): void
    {
        $ta = $this->makeTA();
        $ta->setAuthenticationMethods([]);
        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    // -------------------------------------------------------------------------
    // Auth_HTTPBasicAuth_Plaintext (method = 3)
    // -------------------------------------------------------------------------

    public function testHttpBasicPlaintextReturnsCredentialsWhenServerVarsSet(): void
    {
        $_SERVER['PHP_AUTH_USER'] = 'alice';
        $_SERVER['PHP_AUTH_PW']   = 'secret';

        $ta = $this->makeTA();
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_HTTPBasicAuth_Plaintext]);

        $result = $ta->getTransparentAuthenticationCredentials();

        self::assertIsArray($result);
        self::assertSame('alice', $result['username']);
        self::assertSame('secret', $result['password']);
    }

    public function testHttpBasicPlaintextReturnsNullWhenUserMissing(): void
    {
        unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

        $ta = $this->makeTA();
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_HTTPBasicAuth_Plaintext]);

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testHttpBasicPlaintextReturnsNullWhenPasswordMissing(): void
    {
        $_SERVER['PHP_AUTH_USER'] = 'alice';
        unset($_SERVER['PHP_AUTH_PW']);

        $ta = $this->makeTA();
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_HTTPBasicAuth_Plaintext]);

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    // -------------------------------------------------------------------------
    // Auth_QueryString_Plaintext (method = 4)
    // -------------------------------------------------------------------------

    public function testQueryStringPlaintextReturnsCredentials(): void
    {
        $json = json_encode(['username' => 'bob', 'password' => 'pass123']);
        $ta   = $this->makeTA(['_AwfAuthentication' => $json]);
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_QueryString_Plaintext]);

        $result = $ta->getTransparentAuthenticationCredentials();

        self::assertIsArray($result);
        self::assertSame('bob', $result['username']);
        self::assertSame('pass123', $result['password']);
    }

    public function testQueryStringPlaintextReturnsNullWhenParamMissing(): void
    {
        $ta = $this->makeTA(); // no _AwfAuthentication param
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_QueryString_Plaintext]);

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testQueryStringPlaintextReturnsNullWhenJsonInvalid(): void
    {
        $ta = $this->makeTA(['_AwfAuthentication' => 'not-valid-json']);
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_QueryString_Plaintext]);

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testQueryStringPlaintextReturnsNullWhenUsernameMissingInJson(): void
    {
        $json = json_encode(['password' => 'pass123']); // missing username
        $ta   = $this->makeTA(['_AwfAuthentication' => $json]);
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_QueryString_Plaintext]);

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testQueryStringPlaintextReturnsNullWhenPasswordMissingInJson(): void
    {
        $json = json_encode(['username' => 'bob']); // missing password
        $ta   = $this->makeTA(['_AwfAuthentication' => $json]);
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_QueryString_Plaintext]);

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testQueryStringPlaintextReturnsNullWhenQueryParamEmpty(): void
    {
        $json = json_encode(['username' => 'bob', 'password' => 'x']);
        $ta   = $this->makeTA(['_AwfAuthentication' => $json]);
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_QueryString_Plaintext]);
        $ta->setQueryParam(''); // empty param name disables this method

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    // -------------------------------------------------------------------------
    // Auth_SplitQueryString_Plaintext (method = 5)
    // -------------------------------------------------------------------------

    public function testSplitQueryStringPlaintextReturnsCredentials(): void
    {
        $ta = $this->makeTA([
            '_AwfUsername' => 'carol',
            '_AwfPassword' => 'mypass',
        ]);
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_SplitQueryString_Plaintext]);

        $result = $ta->getTransparentAuthenticationCredentials();

        self::assertIsArray($result);
        self::assertSame('carol', $result['username']);
        self::assertSame('mypass', $result['password']);
    }

    public function testSplitQueryStringPlaintextReturnsNullWhenUsernameMissing(): void
    {
        $ta = $this->makeTA(['_AwfPassword' => 'mypass']);
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_SplitQueryString_Plaintext]);

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testSplitQueryStringPlaintextReturnsNullWhenPasswordMissing(): void
    {
        $ta = $this->makeTA(['_AwfUsername' => 'carol']);
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_SplitQueryString_Plaintext]);

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testSplitQueryStringPlaintextReturnsNullWhenBothParamsMissing(): void
    {
        $ta = $this->makeTA();
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_SplitQueryString_Plaintext]);

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testSplitQueryStringPlaintextReturnsNullWhenUsernameParamEmpty(): void
    {
        $ta = $this->makeTA(['_AwfUsername' => 'carol', '_AwfPassword' => 'mypass']);
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_SplitQueryString_Plaintext]);
        $ta->setQueryParamUsername(''); // empty param name disables this method

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testSplitQueryStringPlaintextReturnsNullWhenPasswordParamEmpty(): void
    {
        $ta = $this->makeTA(['_AwfUsername' => 'carol', '_AwfPassword' => 'mypass']);
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_SplitQueryString_Plaintext]);
        $ta->setQueryParamPassword(''); // empty param name disables this method

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    // -------------------------------------------------------------------------
    // Auth_HTTPBasicAuth_TOTP (method = 1)
    // -------------------------------------------------------------------------

    public function testHttpBasicTotpReturnsNullWhenTotpKeyEmpty(): void
    {
        $_SERVER['PHP_AUTH_USER'] = '_AwfAuth';
        $_SERVER['PHP_AUTH_PW']   = 'someencrypteddata';

        $ta = $this->makeTA();
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_HTTPBasicAuth_TOTP]);
        // totpKey defaults to '' — method must be skipped

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testHttpBasicTotpReturnsNullWhenUserMissing(): void
    {
        unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

        $ta = $this->makeTA();
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_HTTPBasicAuth_TOTP]);
        $ta->setTotpKey('JBSWY3DPEHPK3PXP');

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testHttpBasicTotpReturnsNullWhenWrongUsername(): void
    {
        $_SERVER['PHP_AUTH_USER'] = 'wronguser';
        $_SERVER['PHP_AUTH_PW']   = 'someencrypteddata';

        $ta = $this->makeTA();
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_HTTPBasicAuth_TOTP]);
        $ta->setTotpKey('JBSWY3DPEHPK3PXP');

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testHttpBasicTotpReturnsCredentialsWithValidEncryptedPayload(): void
    {
        $totpKey  = 'JBSWY3DPEHPK3PXP';
        $timeStep = 6;

        // Build the encrypted payload exactly as the production code will decrypt it.
        $totp   = new Totp($timeStep);
        $period = $totp->getPeriod();
        $time   = $period * $timeStep;
        $otp    = $totp->getCode($totpKey, $time);
        $cryptoKey = hash('sha256', $totpKey . $otp);

        $plaintext = json_encode(['username' => 'dave', 'password' => 'topsecret']);
        $aes       = new Aes($cryptoKey);
        $encrypted = $aes->encryptString($plaintext);

        $_SERVER['PHP_AUTH_USER'] = '_AwfAuth';
        $_SERVER['PHP_AUTH_PW']   = $encrypted;

        $ta = $this->makeTA();
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_HTTPBasicAuth_TOTP]);
        $ta->setTotpKey($totpKey);
        $ta->setTimeStep($timeStep);

        $result = $ta->getTransparentAuthenticationCredentials();

        self::assertIsArray($result);
        self::assertSame('dave', $result['username']);
        self::assertSame('topsecret', $result['password']);
    }

    public function testHttpBasicTotpReturnsNullWhenEncryptedDataUndecodable(): void
    {
        // Provide data that is valid base64 (≥16 bytes decoded) but not a valid
        // AES ciphertext — decryption will yield garbage, not JSON with username/password.
        $_SERVER['PHP_AUTH_USER'] = '_AwfAuth';
        $_SERVER['PHP_AUTH_PW']   = base64_encode(str_repeat('X', 48));

        $ta = $this->makeTA();
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_HTTPBasicAuth_TOTP]);
        $ta->setTotpKey('JBSWY3DPEHPK3PXP');

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    // -------------------------------------------------------------------------
    // Auth_QueryString_TOTP (method = 2)
    // -------------------------------------------------------------------------

    public function testQueryStringTotpReturnsNullWhenTotpKeyEmpty(): void
    {
        $ta = $this->makeTA(['_AwfAuthentication' => 'somedata']);
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_QueryString_TOTP]);
        // totpKey defaults to ''

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testQueryStringTotpReturnsNullWhenParamMissing(): void
    {
        $ta = $this->makeTA(); // no _AwfAuthentication param
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_QueryString_TOTP]);
        $ta->setTotpKey('JBSWY3DPEHPK3PXP');

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testQueryStringTotpReturnsCredentialsWithValidEncryptedPayload(): void
    {
        $totpKey  = 'JBSWY3DPEHPK3PXP';
        $timeStep = 6;

        $totp   = new Totp($timeStep);
        $period = $totp->getPeriod();
        $time   = $period * $timeStep;
        $otp    = $totp->getCode($totpKey, $time);
        $cryptoKey = hash('sha256', $totpKey . $otp);

        $plaintext = json_encode(['username' => 'eve', 'password' => 'hunter2']);
        $aes       = new Aes($cryptoKey);
        $encrypted = $aes->encryptString($plaintext);

        $ta = $this->makeTA(['_AwfAuthentication' => $encrypted]);
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_QueryString_TOTP]);
        $ta->setTotpKey($totpKey);
        $ta->setTimeStep($timeStep);

        $result = $ta->getTransparentAuthenticationCredentials();

        self::assertIsArray($result);
        self::assertSame('eve', $result['username']);
        self::assertSame('hunter2', $result['password']);
    }

    public function testQueryStringTotpReturnsNullWhenEncryptedDataUndecodable(): void
    {
        // Valid base64 (≥16 bytes decoded) but not a real AES ciphertext — decryption
        // will yield garbage rather than valid JSON with username/password.
        $ta = $this->makeTA(['_AwfAuthentication' => base64_encode(str_repeat('Z', 48))]);
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_QueryString_TOTP]);
        $ta->setTotpKey('JBSWY3DPEHPK3PXP');

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    public function testQueryStringTotpReturnsNullWhenQueryParamNameEmpty(): void
    {
        $ta = $this->makeTA(['_AwfAuthentication' => 'somedata']);
        $ta->setAuthenticationMethods([TransparentAuthentication::Auth_QueryString_TOTP]);
        $ta->setTotpKey('JBSWY3DPEHPK3PXP');
        $ta->setQueryParam(''); // disables the method

        self::assertNull($ta->getTransparentAuthenticationCredentials());
    }

    // -------------------------------------------------------------------------
    // Method fallthrough — first method fails, second succeeds
    // -------------------------------------------------------------------------

    public function testFallsThroughToNextMethodOnFailure(): void
    {
        // Method 4 (QueryString_Plaintext) will fail — no param set.
        // Method 5 (SplitQueryString_Plaintext) should succeed.
        $ta = $this->makeTA([
            '_AwfUsername' => 'frank',
            '_AwfPassword' => 'pass',
        ]);
        $ta->setAuthenticationMethods([
            TransparentAuthentication::Auth_QueryString_Plaintext,
            TransparentAuthentication::Auth_SplitQueryString_Plaintext,
        ]);

        $result = $ta->getTransparentAuthenticationCredentials();

        self::assertIsArray($result);
        self::assertSame('frank', $result['username']);
    }

    // -------------------------------------------------------------------------
    // Class constants
    // -------------------------------------------------------------------------

    public static function constantsProvider(): array
    {
        return [
            'Auth_HTTPBasicAuth_TOTP'       => ['Auth_HTTPBasicAuth_TOTP', 1],
            'Auth_QueryString_TOTP'         => ['Auth_QueryString_TOTP', 2],
            'Auth_HTTPBasicAuth_Plaintext'  => ['Auth_HTTPBasicAuth_Plaintext', 3],
            'Auth_QueryString_Plaintext'    => ['Auth_QueryString_Plaintext', 4],
            'Auth_SplitQueryString_Plaintext' => ['Auth_SplitQueryString_Plaintext', 5],
        ];
    }

    #[DataProvider('constantsProvider')]
    public function testConstantValues(string $constant, int $expectedValue): void
    {
        self::assertSame($expectedValue, constant(TransparentAuthentication::class . '::' . $constant));
    }
}
