<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Utils;

use Awf\Utils\Ip;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IpTest extends TestCase
{
    /**
     * A snapshot of the $_SERVER superglobal so we can restore it after touching it.
     *
     * @var array
     */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;

        // Reset the static state of the Ip class to its defaults before every test.
        Ip::setIp(null);
        Ip::setAllowIpOverrides(true);
        Ip::setUseFirstIpInChain(true);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;

        // Reset the static state of the Ip class to its defaults after every test.
        Ip::setIp(null);
        Ip::setAllowIpOverrides(true);
        Ip::setUseFirstIpInChain(true);
    }

    // -------------------------------------------------------------------------
    // setIp / getUserIP
    // -------------------------------------------------------------------------

    public function testSetIpIsReturnedVerbatim(): void
    {
        Ip::setIp('203.0.113.5');

        self::assertSame('203.0.113.5', Ip::getUserIP());
    }

    public function testGetUserIPReadsRemoteAddr(): void
    {
        Ip::setIp(null);
        $_SERVER['REMOTE_ADDR'] = '198.51.100.10';

        self::assertSame('198.51.100.10', Ip::getUserIP());
    }

    public function testGetUserIPNormalisesIPv6(): void
    {
        Ip::setIp(null);
        // An IPv6 address with leading zeroes is normalised by inet_pton/inet_ntop.
        $_SERVER['REMOTE_ADDR'] = '2001:0db8:0000:0000:0000:0000:0000:0001';

        self::assertSame('2001:db8::1', Ip::getUserIP());
    }

    public function testGetUserIPIsCached(): void
    {
        Ip::setIp(null);
        $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
        self::assertSame('192.0.2.1', Ip::getUserIP());

        // Changing $_SERVER should NOT change the cached value.
        $_SERVER['REMOTE_ADDR'] = '192.0.2.99';
        self::assertSame('192.0.2.1', Ip::getUserIP());
    }

    public function testGetUserIPHonoursXForwardedFor(): void
    {
        Ip::setIp(null);
        $_SERVER['REMOTE_ADDR']          = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7';

        self::assertSame('203.0.113.7', Ip::getUserIP());
    }

    public function testGetUserIPFirstIpInChain(): void
    {
        Ip::setIp(null);
        Ip::setUseFirstIpInChain(true);
        $_SERVER['REMOTE_ADDR']          = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7, 70.41.3.18, 150.172.238.178';

        self::assertSame('203.0.113.7', Ip::getUserIP());
    }

    public function testGetUserIPLastIpInChain(): void
    {
        Ip::setIp(null);
        Ip::setUseFirstIpInChain(false);
        $_SERVER['REMOTE_ADDR']          = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7, 70.41.3.18, 150.172.238.178';

        self::assertSame('150.172.238.178', Ip::getUserIP());
    }

    public function testGetUserIPCloudflareHeader(): void
    {
        Ip::setIp(null);
        $_SERVER['REMOTE_ADDR']             = '10.0.0.1';
        $_SERVER['HTTP_CF_CONNECTING_IP']   = '203.0.113.42';

        self::assertSame('203.0.113.42', Ip::getUserIP());
    }

    public function testGetUserIPSucuriHeader(): void
    {
        Ip::setIp(null);
        $_SERVER['REMOTE_ADDR']              = '10.0.0.1';
        $_SERVER['HTTP_X_SUCURI_CLIENTIP']   = '203.0.113.43';

        self::assertSame('203.0.113.43', Ip::getUserIP());
    }

    public function testGetUserIPClientIpHeader(): void
    {
        Ip::setIp(null);
        $_SERVER['REMOTE_ADDR']      = '10.0.0.1';
        $_SERVER['HTTP_CLIENT_IP']   = '203.0.113.44';

        self::assertSame('203.0.113.44', Ip::getUserIP());
    }

    public function testGetUserIPHeaderPrecedence(): void
    {
        Ip::setIp(null);
        $_SERVER['REMOTE_ADDR']              = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR']     = '203.0.113.1';
        $_SERVER['HTTP_CF_CONNECTING_IP']    = '203.0.113.2';
        $_SERVER['HTTP_X_SUCURI_CLIENTIP']   = '203.0.113.3';
        $_SERVER['HTTP_CLIENT_IP']           = '203.0.113.4';

        // X-Forwarded-For takes precedence over all the other override headers.
        self::assertSame('203.0.113.1', Ip::getUserIP());
    }

    public function testGetUserIPIgnoresOverrideHeadersWhenDisabled(): void
    {
        Ip::setIp(null);
        Ip::setAllowIpOverrides(false);
        $_SERVER['REMOTE_ADDR']          = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7';

        self::assertSame('10.0.0.1', Ip::getUserIP());
    }

    public function testGetUserIPEmbeddedIPv4InIPv6IsExtracted(): void
    {
        Ip::setIp(null);
        $_SERVER['REMOTE_ADDR']          = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '::FFFF:192.168.1.1';

        self::assertSame('192.168.1.1', Ip::getUserIP());
    }

    public function testGetUserIPWithNoServerVarReturnsEmpty(): void
    {
        Ip::setIp(null);
        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['HTTP_X_SUCURI_CLIENTIP'], $_SERVER['HTTP_CLIENT_IP']);

        self::assertSame('', Ip::getUserIP());
    }

    // -------------------------------------------------------------------------
    // workaroundIPIssues
    // -------------------------------------------------------------------------

    public function testWorkaroundIPIssuesRewritesRemoteAddr(): void
    {
        Ip::setIp(null);
        $_SERVER['REMOTE_ADDR']          = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7';

        Ip::workaroundIPIssues();

        self::assertSame('203.0.113.7', $_SERVER['REMOTE_ADDR']);
        self::assertSame('10.0.0.1', $_SERVER['FOF_REMOTE_ADDR']);
    }

    public function testWorkaroundIPIssuesNoOpWhenAlreadyCorrect(): void
    {
        Ip::setIp(null);
        $_SERVER['REMOTE_ADDR'] = '198.51.100.10';
        unset($_SERVER['FOF_REMOTE_ADDR']);

        Ip::workaroundIPIssues();

        self::assertSame('198.51.100.10', $_SERVER['REMOTE_ADDR']);
        self::assertArrayNotHasKey('FOF_REMOTE_ADDR', $_SERVER);
    }

    // -------------------------------------------------------------------------
    // IPinList - empty / invalid inputs
    // -------------------------------------------------------------------------

    public function testIPinListEmptyTableReturnsFalse(): void
    {
        self::assertFalse(Ip::IPinList('192.0.2.1', ''));
        self::assertFalse(Ip::IPinList('192.0.2.1', []));
    }

    public function testIPinListEmptyIpReturnsFalse(): void
    {
        self::assertFalse(Ip::IPinList('', '192.0.2.1'));
    }

    public function testIPinListZeroIpReturnsFalse(): void
    {
        self::assertFalse(Ip::IPinList('0.0.0.0', '0.0.0.0'));
    }

    public function testIPinListMalformedIpReturnsFalse(): void
    {
        self::assertFalse(Ip::IPinList('not-an-ip', '192.0.2.1'));
    }

    // -------------------------------------------------------------------------
    // IPinList - exact IPv4 matching
    // -------------------------------------------------------------------------

    public static function exactIPv4Provider(): array
    {
        return [
            'exact match'              => ['192.0.2.1', '192.0.2.1', true],
            'no match'                 => ['192.0.2.1', '192.0.2.2', false],
            'match in comma list'      => ['192.0.2.5', '192.0.2.1, 192.0.2.5, 192.0.2.9', true],
            'no match in comma list'   => ['192.0.2.6', '192.0.2.1, 192.0.2.5, 192.0.2.9', false],
        ];
    }

    #[DataProvider('exactIPv4Provider')]
    public function testIPinListExactIPv4(string $ip, string $table, bool $expected): void
    {
        self::assertSame($expected, Ip::IPinList($ip, $table));
    }

    public function testIPinListAcceptsArrayTable(): void
    {
        self::assertTrue(Ip::IPinList('192.0.2.5', ['192.0.2.1', '192.0.2.5']));
        self::assertFalse(Ip::IPinList('192.0.2.6', ['192.0.2.1', '192.0.2.5']));
    }

    // -------------------------------------------------------------------------
    // IPinList - IPv4 ranges (a-b)
    // -------------------------------------------------------------------------

    public static function ipv4RangeProvider(): array
    {
        return [
            'lower bound'        => ['192.0.2.1', '192.0.2.1-192.0.2.10', true],
            'upper bound'        => ['192.0.2.10', '192.0.2.1-192.0.2.10', true],
            'middle'             => ['192.0.2.5', '192.0.2.1-192.0.2.10', true],
            'below range'        => ['192.0.2.0', '192.0.2.1-192.0.2.10', false],
            'above range'        => ['192.0.2.11', '192.0.2.1-192.0.2.10', false],
            'reversed range'     => ['192.0.2.5', '192.0.2.10-192.0.2.1', true],
            'IPv6 in IPv4 range' => ['2001:db8::1', '192.0.2.1-192.0.2.10', false],
        ];
    }

    #[DataProvider('ipv4RangeProvider')]
    public function testIPinListIPv4Range(string $ip, string $table, bool $expected): void
    {
        self::assertSame($expected, Ip::IPinList($ip, $table));
    }

    // -------------------------------------------------------------------------
    // IPinList - IPv4 CIDR / netmask
    // -------------------------------------------------------------------------

    public static function ipv4CidrProvider(): array
    {
        return [
            '/24 in'              => ['192.0.2.50', '192.0.2.0/24', true],
            '/24 out'             => ['192.0.3.50', '192.0.2.0/24', false],
            '/16 in'              => ['192.0.99.1', '192.0.0.0/16', true],
            '/16 out'            => ['192.1.0.1', '192.0.0.0/16', false],
            '/32 exact'           => ['192.0.2.1', '192.0.2.1/32', true],
            '/32 not'             => ['192.0.2.2', '192.0.2.1/32', false],
            'netmask form in'     => ['192.0.2.50', '192.0.2.0/255.255.255.0', true],
            'netmask form out'    => ['192.0.3.50', '192.0.2.0/255.255.255.0', false],
            'IPv6 in IPv4 CIDR'   => ['2001:db8::1', '192.0.2.0/24', false],
        ];
    }

    #[DataProvider('ipv4CidrProvider')]
    public function testIPinListIPv4Cidr(string $ip, string $table, bool $expected): void
    {
        self::assertSame($expected, Ip::IPinList($ip, $table));
    }

    // -------------------------------------------------------------------------
    // IPinList - partial IPv4 addresses (trailing dot)
    // -------------------------------------------------------------------------

    public static function partialIPv4Provider(): array
    {
        return [
            'class C prefix in'  => ['192.0.2.50', '192.0.2.', true],
            'class C prefix out' => ['192.0.3.50', '192.0.2.', false],
            'class B prefix in'  => ['192.0.99.1', '192.0.', true],
            'class B prefix out' => ['192.1.0.1', '192.0.', false],
        ];
    }

    #[DataProvider('partialIPv4Provider')]
    public function testIPinListPartialIPv4(string $ip, string $table, bool $expected): void
    {
        self::assertSame($expected, Ip::IPinList($ip, $table));
    }

    // -------------------------------------------------------------------------
    // IPinList - IPv6
    // -------------------------------------------------------------------------

    public static function ipv6Provider(): array
    {
        return [
            'exact match'            => ['2001:db8::1', '2001:db8::1', true],
            'exact no match'         => ['2001:db8::2', '2001:db8::1', false],
            'CIDR /64 in'            => ['2001:db8::abcd', '2001:db8::/64', true],
            'CIDR /64 out'           => ['2001:db9::abcd', '2001:db8::/64', false],
            'CIDR /32 in'            => ['2001:db8:1234::1', '2001:db8::/32', true],
            'IPv4 against IPv6'      => ['192.0.2.1', '2001:db8::/64', false],
            'range in'               => ['2001:db8::5', '2001:db8::1-2001:db8::10', true],
            'range out'              => ['2001:db8::20', '2001:db8::1-2001:db8::10', false],
        ];
    }

    #[DataProvider('ipv6Provider')]
    public function testIPinListIPv6(string $ip, string $table, bool $expected): void
    {
        self::assertSame($expected, Ip::IPinList($ip, $table));
    }
}
