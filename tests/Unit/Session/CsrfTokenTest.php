<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Session;

use Awf\Session\CsrfToken;
use Awf\Session\CsrfTokenFactory;
use Awf\Session\Manager;
use Awf\Session\Segment;
use Awf\Session\Encoder\Base32Encoder;
use Awf\Session\Encoder\Base64Encoder;
use Awf\Session\Encoder\TransparentEncoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// ---------------------------------------------------------------------------
// Helpers shared across sub-tests
// ---------------------------------------------------------------------------

/**
 * Stubbed Manager that avoids real PHP session interaction.
 * Used only by CsrfTokenFactory tests.
 */
class FakeManagerForCsrf extends Manager
{
    private bool $started   = false;
    private bool $available = true;

    public function __construct()
    {
        // Skip parent constructor — no session_get_cookie_params() needed.
    }

    public function isStarted(): bool   { return $this->started; }
    public function isAvailable(): bool { return $this->available; }
    public function start(): bool       { $this->started = true; return true; }

    /**
     * Return a FakeSegmentForCsrf cast as Segment to satisfy the CsrfToken
     * constructor's type hint (Segment is not final, so we can subclass it,
     * but since we only need magic property access we return the fake object
     * via the newSegment method — CsrfToken accepts Segment; we use a real
     * Segment that wraps our fake storage via a sub-class).
     */
    public function newSegment(string $name): Segment
    {
        return new InMemorySegment($this, $name);
    }
}

/**
 * A Segment subclass that stores data in memory without touching $_SESSION.
 */
class InMemorySegment extends Segment
{
    public function __construct(Manager $session, string $name)
    {
        // Bypass parent constructor to avoid encoder / session interactions.
        $this->session = $session;
        $this->name    = $name;
        $this->data    = [];
        // Use a TransparentEncoder so encode/decode are no-ops.
        $this->encoder = new TransparentEncoder();
    }

    protected function isLoaded(): bool { return true; }
    protected function load(): void     {}
}

// ---------------------------------------------------------------------------

#[CoversClass(CsrfToken::class)]
#[CoversClass(CsrfTokenFactory::class)]
#[CoversClass(Base32Encoder::class)]
#[CoversClass(Base64Encoder::class)]
#[CoversClass(TransparentEncoder::class)]
class CsrfTokenTest extends TestCase
{
    // =======================================================================
    // CsrfToken — lifecycle
    // =======================================================================

    private function makeSegment(): InMemorySegment
    {
        return new InMemorySegment(new FakeManagerForCsrf(), 'test-segment');
    }

    /** CsrfToken generates a value on construction when the segment is empty. */
    public function testConstructorGeneratesValueWhenSegmentIsEmpty(): void
    {
        $token = new CsrfToken($this->makeSegment());

        $this->assertNotEmpty($token->getValue());
    }

    /** CsrfToken reuses an existing value stored in the segment. */
    public function testConstructorReusesExistingSegmentValue(): void
    {
        $segment        = $this->makeSegment();
        $segment->value = 'preset-token-value';

        $token = new CsrfToken($segment);

        $this->assertSame('preset-token-value', $token->getValue());
    }

    /** getValue() returns the same string each time (no regeneration on access). */
    public function testGetValueIsStable(): void
    {
        $token  = new CsrfToken($this->makeSegment());
        $first  = $token->getValue();
        $second = $token->getValue();

        $this->assertSame($first, $second);
    }

    /** regenerateValue() produces a new, non-empty token. */
    public function testRegenerateValueProducesNonEmptyToken(): void
    {
        $token = new CsrfToken($this->makeSegment());

        $old = $token->getValue();
        $token->regenerateValue();
        $new = $token->getValue();

        $this->assertNotEmpty($new);
        // Two randomly generated values almost certainly differ.
        // (The probability of collision is astronomically low.)
        $this->assertNotSame($old, $new);
    }

    // =======================================================================
    // CsrfToken — isValid()
    // =======================================================================

    /** isValid() returns true when the supplied value matches the token. */
    public function testIsValidReturnsTrueForMatchingValue(): void
    {
        $token = new CsrfToken($this->makeSegment());

        $this->assertTrue($token->isValid($token->getValue()));
    }

    /** isValid() returns false for any non-matching value. */
    public function testIsValidReturnsFalseForWrongValue(): void
    {
        $token = new CsrfToken($this->makeSegment());

        $this->assertFalse($token->isValid('wrong-value'));
    }

    /** isValid() returns false for an empty string. */
    public function testIsValidReturnsFalseForEmptyString(): void
    {
        $token = new CsrfToken($this->makeSegment());

        $this->assertFalse($token->isValid(''));
    }

    /** isValid() uses strict equality — type-juggling does not fool it. */
    public function testIsValidIsStrictlyTyped(): void
    {
        $token = new CsrfToken($this->makeSegment());

        // '0' is truthy in loose comparison with some hash values; strict must be false.
        $this->assertFalse($token->isValid('0'));
    }

    // =======================================================================
    // CsrfToken — algorithm selection
    // =======================================================================

    public static function algorithmProvider(): array
    {
        return [
            'sha512'       => ['sha512'],
            'sha256'       => ['sha256'],
            'sha384'       => ['sha384'],
            'sha1'         => ['sha1'],
            'md5'          => ['md5'],
            'sha3-256'     => ['sha3-256'],
        ];
    }

    /**
     * Token values look like hash output (hex string) for a set of acceptable algorithms.
     */
    #[DataProvider('algorithmProvider')]
    public function testValidAlgorithmProducesHexToken(string $algo): void
    {
        if (!in_array($algo, hash_algos(), true))
        {
            $this->markTestSkipped("Algorithm {$algo} not available on this PHP build.");
        }

        $token = new CsrfToken($this->makeSegment(), $algo);
        $value = $token->getValue();
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $value);
    }

    /** An unrecognised algorithm silently falls back to sha512 (or best available). */
    public function testInvalidAlgorithmFallsBackToDefault(): void
    {
        $token = new CsrfToken($this->makeSegment(), 'not-a-real-algorithm');

        // The token should still be generated — just with a different algorithm.
        $this->assertNotEmpty($token->getValue());
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $token->getValue());
    }

    // =======================================================================
    // CsrfTokenFactory
    // =======================================================================

    /** Factory creates a CsrfToken instance via newInstance(). */
    public function testFactoryCreatesToken(): void
    {
        $manager = new FakeManagerForCsrf();
        $factory = new CsrfTokenFactory();
        $token   = $factory->newInstance($manager);

        $this->assertInstanceOf(CsrfToken::class, $token);
    }

    /** Factory-created token has a non-empty value. */
    public function testFactoryCreatedTokenHasValue(): void
    {
        $manager = new FakeManagerForCsrf();
        $factory = new CsrfTokenFactory();
        $token   = $factory->newInstance($manager);

        $this->assertNotEmpty($token->getValue());
    }

    /** setAlgorithm() changes the algorithm used for subsequent tokens. */
    public function testFactorySetAlgorithmIsRespected(): void
    {
        $manager = new FakeManagerForCsrf();
        $factory = new CsrfTokenFactory('sha512');
        $factory->setAlgorithm('sha256');

        // Token must be valid (non-empty hex); we can't inspect private state
        // directly, but we can verify the factory still creates a working token.
        $token = $factory->newInstance($manager);
        $this->assertNotEmpty($token->getValue());
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $token->getValue());
    }

    /** Factory passes the algorithm to the CsrfToken constructor. */
    public function testFactoryDefaultAlgorithmIsPassedToToken(): void
    {
        $manager = new FakeManagerForCsrf();
        $factory = new CsrfTokenFactory('sha256');

        $token = $factory->newInstance($manager);

        // sha256 produces a 64-char hex string.
        $this->assertSame(64, strlen($token->getValue()));
    }

    // =======================================================================
    // TransparentEncoder
    // =======================================================================

    /** TransparentEncoder is always available. */
    public function testTransparentEncoderIsAvailable(): void
    {
        $encoder = new TransparentEncoder();
        $this->assertTrue($encoder->isAvailable());
    }

    /** Encoding a non-null array returns the array unchanged. */
    public function testTransparentEncoderEncodeReturnsArray(): void
    {
        $encoder = new TransparentEncoder();
        $data    = ['foo' => 'bar', 'baz' => 42];

        $this->assertSame($data, $encoder->encode($data));
    }

    /** Encoding null returns null. */
    public function testTransparentEncoderEncodeNullReturnsNull(): void
    {
        $encoder = new TransparentEncoder();
        $this->assertNull($encoder->encode(null));
    }

    /** Decoding an array returns it unchanged. */
    public function testTransparentEncoderDecodeArrayReturnsArray(): void
    {
        $encoder = new TransparentEncoder();
        $data    = ['key' => 'value'];

        $this->assertSame($data, $encoder->decode($data));
    }

    /** Decoding a non-array (e.g. a string) returns an empty array. */
    public function testTransparentEncoderDecodeNonArrayReturnsEmpty(): void
    {
        $encoder = new TransparentEncoder();

        $this->assertSame([], $encoder->decode('not-an-array'));
        $this->assertSame([], $encoder->decode(null));
        $this->assertSame([], $encoder->decode(42));
    }

    /** Round-trip: encode then decode returns the original array. */
    public function testTransparentEncoderRoundTrip(): void
    {
        $encoder = new TransparentEncoder();
        $data    = ['a' => 1, 'b' => [2, 3]];

        $this->assertSame($data, $encoder->decode($encoder->encode($data)));
    }

    // =======================================================================
    // Base64Encoder
    // =======================================================================

    /** Base64Encoder is available when base64_encode / base64_decode exist. */
    public function testBase64EncoderIsAvailable(): void
    {
        $encoder = new Base64Encoder();
        $this->assertTrue($encoder->isAvailable());
    }

    /** Encoding null returns null. */
    public function testBase64EncoderEncodeNullReturnsNull(): void
    {
        $encoder = new Base64Encoder();
        $this->assertNull($encoder->encode(null));
    }

    /** Encoding produces a non-empty string. */
    public function testBase64EncoderEncodeProducesString(): void
    {
        $encoder = new Base64Encoder();
        $result  = $encoder->encode(['hello' => 'world']);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /** Decoding an empty string returns an empty array. */
    public function testBase64EncoderDecodeEmptyReturnsEmpty(): void
    {
        $encoder = new Base64Encoder();
        $this->assertSame([], $encoder->decode(''));
        $this->assertSame([], $encoder->decode(null));
    }

    /** Decoding garbage returns an empty array (no exception). */
    public function testBase64EncoderDecodeGarbageReturnsEmpty(): void
    {
        $encoder = new Base64Encoder();
        // Valid base64 but not a serialised array.
        $this->assertSame([], $encoder->decode(base64_encode('this is not serialized php')));
    }

    /** Round-trip: encode then decode returns the original array. */
    public function testBase64EncoderRoundTrip(): void
    {
        $encoder  = new Base64Encoder();
        $original = ['name' => 'Alice', 'score' => 99, 'tags' => ['a', 'b']];

        $encoded = $encoder->encode($original);
        $decoded = $encoder->decode($encoded);

        $this->assertSame($original, $decoded);
    }

    /** Round-trip works with an empty array. */
    public function testBase64EncoderRoundTripEmptyArray(): void
    {
        $encoder = new Base64Encoder();
        $encoded = $encoder->encode([]);
        $decoded = $encoder->decode($encoded);

        $this->assertSame([], $decoded);
    }

    /** Round-trip preserves nested arrays. */
    public function testBase64EncoderRoundTripNestedData(): void
    {
        $encoder  = new Base64Encoder();
        $original = ['level1' => ['level2' => ['level3' => 'deep']]];

        $this->assertSame($original, $encoder->decode($encoder->encode($original)));
    }

    // =======================================================================
    // Base32Encoder
    // =======================================================================

    /** Base32Encoder is available when the Awf\Encrypt\Base32 class exists. */
    public function testBase32EncoderIsAvailable(): void
    {
        $encoder = new Base32Encoder();
        // Just check the method returns a bool — availability depends on build.
        $this->assertIsBool($encoder->isAvailable());
    }

    /** Encoding null returns null. */
    public function testBase32EncoderEncodeNullReturnsNull(): void
    {
        if (!(new Base32Encoder())->isAvailable())
        {
            $this->markTestSkipped('Awf\Encrypt\Base32 not available.');
        }

        $encoder = new Base32Encoder();
        $this->assertNull($encoder->encode(null));
    }

    /** Encoding an array produces a non-empty string. */
    public function testBase32EncoderEncodeProducesString(): void
    {
        if (!(new Base32Encoder())->isAvailable())
        {
            $this->markTestSkipped('Awf\Encrypt\Base32 not available.');
        }

        $encoder = new Base32Encoder();
        $result  = $encoder->encode(['hello' => 'world']);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /** Decoding an empty string returns an empty array. */
    public function testBase32EncoderDecodeEmptyReturnsEmpty(): void
    {
        $encoder = new Base32Encoder();
        $this->assertSame([], $encoder->decode(''));
        $this->assertSame([], $encoder->decode(null));
    }

    /** Decoding garbage returns an empty array (no exception). */
    public function testBase32EncoderDecodeGarbageReturnsEmpty(): void
    {
        $encoder = new Base32Encoder();
        // Pass some invalid base32-ish string.
        $this->assertSame([], $encoder->decode('!@#$%^&*'));
    }

    /** Round-trip: encode then decode recovers the original data. */
    public function testBase32EncoderRoundTrip(): void
    {
        if (!(new Base32Encoder())->isAvailable())
        {
            $this->markTestSkipped('Awf\Encrypt\Base32 not available.');
        }

        $encoder  = new Base32Encoder();
        $original = ['user' => 'Bob', 'roles' => ['admin', 'editor']];

        $encoded = $encoder->encode($original);
        $decoded = $encoder->decode($encoded);

        $this->assertSame($original, $decoded);
    }

    /** Round-trip works with an empty array. */
    public function testBase32EncoderRoundTripEmptyArray(): void
    {
        if (!(new Base32Encoder())->isAvailable())
        {
            $this->markTestSkipped('Awf\Encrypt\Base32 not available.');
        }

        $encoder = new Base32Encoder();
        $encoded = $encoder->encode([]);
        $decoded = $encoder->decode($encoded);

        $this->assertSame([], $decoded);
    }
}
