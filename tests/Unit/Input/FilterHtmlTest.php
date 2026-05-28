<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Input;

use Awf\Input\Filter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Filter HTML/tag filtering, XSS cleaning, and allow/deny list modes.
 *
 * NOTE: The default Filter constructor uses tagsMethod=0 (whitelist) with an
 * empty tagsArray, which means ALL tags are stripped (none are whitelisted).
 * Only the text content of stripped tags is preserved.
 */
#[CoversClass(Filter::class)]
class FilterHtmlTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeFilter(
        array $tagsArray  = [],
        array $attrArray  = [],
        int   $tagsMethod = 0,
        int   $attrMethod = 0,
        int   $xssAuto    = 1
    ): Filter {
        return new Filter($tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto);
    }

    // =========================================================================
    // clean() — HTML type with the default filter (empty tag whitelist)
    //
    // Default: tagsMethod=0 (whitelist), empty tagsArray → all tags stripped.
    // xssAuto=1 → blacklisted tags/attrs are also cleaned automatically.
    // =========================================================================

    public static function htmlDefaultFilterProvider(): array
    {
        return [
            // Plain text is returned unchanged
            'plain text'                           => ['Hello world', 'HTML', 'Hello world'],
            'text with ampersand'                  => ['Just text & more', 'HTML', 'Just text & more'],
            'empty string'                         => ['', 'HTML', ''],

            // XSS: script tag — text content of <script> is preserved, tags stripped
            'script tag stripped, text remains'    => ['<script>alert(1)</script>', 'HTML', 'alert(1)'],
            'script tag with src stripped'         => ['<script src="evil.js"></script>', 'HTML', ''],

            // XSS: blacklisted tags — empty content is empty after stripping
            'iframe stripped'                      => ['<iframe src="evil.html"></iframe>', 'HTML', ''],
            'object stripped'                      => ['<object data="evil.swf"></object>', 'HTML', ''],
            'embed stripped'                       => ['<embed src="evil.swf">', 'HTML', ''],
            'applet stripped'                      => ['<applet code="evil.class"></applet>', 'HTML', ''],
            'frame stripped'                       => ['<frame src="evil.html">', 'HTML', ''],
            'frameset stripped'                    => ['<frameset></frameset>', 'HTML', ''],
            'meta stripped'                        => ['<meta http-equiv="refresh" content="0;url=evil">', 'HTML', ''],
            'link tag stripped'                    => ['<link rel="stylesheet" href="evil.css">', 'HTML', ''],
            'style tag stripped, text remains'     => ['<style>body{background:red}</style>', 'HTML', 'body{background:red}'],
            'base tag stripped'                    => ['<base href="http://evil.com/">', 'HTML', ''],
            'html/body stripped, text remains'     => ['<html><body>test</body></html>', 'HTML', 'test'],
            'head/title stripped, text remains'    => ['<head><title>x</title></head>', 'HTML', 'x'],
            'title stripped, text remains'         => ['<title>Page Title</title>', 'HTML', 'Page Title'],

            // XSS: all tags stripped, so only text content survives
            'onclick attr in anchor stripped'      => ['<a href="page.html" onclick="evil()">link</a>', 'HTML', 'link'],
            'javascript href stripped'             => ['<a href="javascript:alert(1)">x</a>', 'HTML', 'x'],
            'vbscript href stripped'               => ['<a href="vbscript:msgbox(1)">x</a>', 'HTML', 'x'],
            'behaviour href stripped'              => ['<a href="behaviour:something">x</a>', 'HTML', 'x'],
            'mocha href stripped'                  => ['<a href="mocha:something">x</a>', 'HTML', 'x'],
            'livescript href stripped'             => ['<a href="livescript:something">x</a>', 'HTML', 'x'],

            // Safe markup — all tags still stripped (empty whitelist)
            'paragraph tag stripped, text kept'    => ['<p>Hello</p>', 'HTML', 'Hello'],
            'bold tag stripped, text kept'         => ['<b>bold</b>', 'HTML', 'bold'],
            'anchor stripped, link text kept'      => ['<a href="page.html">link</a>', 'HTML', 'link'],

            // Blacklisted attrs stripped when tag is allowed in blacklist mode
            // (with empty tag whitelist all tags are stripped anyway)
            'action attr stripped text remains'    => ['<form action="evil.php">x</form>', 'HTML', 'x'],
            'background attr stripped text remains'=> ['<table background="evil.jpg">x</table>', 'HTML', 'x'],
        ];
    }

    #[DataProvider('htmlDefaultFilterProvider')]
    public function testHtmlDefaultFilter(string $input, string $type, string $expected): void
    {
        $filter = $this->makeFilter();
        self::assertSame($expected, $filter->clean($input, $type));
    }

    // =========================================================================
    // clean() — HTML type with a permissive tag+attr whitelist
    //
    // These tests use specific tagsArray/attrArray to verify actual tag and
    // attribute reconstruction behaviour.
    // =========================================================================

    /**
     * With a tag whitelist containing specific tags, those tags pass through.
     */
    public function testAllowedTagsPassThrough(): void
    {
        // Allow <p> and <b> tags; no attr restrictions
        $filter = $this->makeFilter(['p', 'b'], [], 0, 1, 1); // attrMethod=1 (blacklist) with empty list → all attrs pass
        $result = $filter->clean('<p>Hello</p><b>bold</b>', 'HTML');
        self::assertStringContainsString('<p>', $result);
        self::assertStringContainsString('<b>', $result);
    }

    /**
     * With an attribute whitelist, only listed attributes survive.
     */
    public function testAttrWhitelistKeepsOnlyListedAttrs(): void
    {
        // Allow <a> tag; attr whitelist: only href
        $filter = $this->makeFilter(['a'], ['href'], 0, 0, 1);
        $result = $filter->clean('<a href="page.html" class="foo">link</a>', 'HTML');
        self::assertStringContainsString('href="page.html"', $result);
        self::assertStringNotContainsString('class=', $result);
    }

    /**
     * With an attribute blacklist, listed attributes are stripped.
     */
    public function testAttrBlacklistStripsListedAttr(): void
    {
        // Allow <a> tag; attr blacklist: class is stripped, href kept
        $filter = $this->makeFilter(['a'], ['class'], 0, 1, 1);
        $result = $filter->clean('<a href="page.html" class="foo">link</a>', 'HTML');
        self::assertStringContainsString('href="page.html"', $result);
        self::assertStringNotContainsString('class=', $result);
    }

    /**
     * With allowed tags, blacklisted attributes (action, background) are removed by xssAuto.
     */
    public function testBlacklistedAttrsRemovedByXssAuto(): void
    {
        $filter = $this->makeFilter(['form'], [], 0, 1, 1); // attrMethod=1 (blacklist empty) + xssAuto=1
        $result = $filter->clean('<form action="evil.php">x</form>', 'HTML');
        self::assertStringContainsString('<form>', $result);
        self::assertStringNotContainsString('action=', $result);
    }

    public function testBackgroundAttrRemovedByXssAuto(): void
    {
        $filter = $this->makeFilter(['table'], [], 0, 1, 1);
        $result = $filter->clean('<table background="evil.jpg">x</table>', 'HTML');
        self::assertStringContainsString('<table>', $result);
        self::assertStringNotContainsString('background=', $result);
    }

    /**
     * XSS: javascript: href is blocked by checkAttribute even when tag+attr are whitelisted.
     */
    public function testJavascriptHrefBlockedWhenTagAndAttrAllowed(): void
    {
        $filter = $this->makeFilter(['a'], ['href'], 0, 0, 1);
        $result = $filter->clean('<a href="javascript:alert(1)">x</a>', 'HTML');
        self::assertStringNotContainsString('javascript:', $result);
    }

    /**
     * XSS: safe href passes through when tag+attr are both whitelisted.
     */
    public function testSafeHrefPassesThroughWhenWhitelisted(): void
    {
        $filter = $this->makeFilter(['a'], ['href'], 0, 0, 1);
        $result = $filter->clean('<a href="https://example.com">link</a>', 'HTML');
        self::assertStringContainsString('href="https://example.com"', $result);
    }

    /**
     * XSS: CSS expression in style attribute is stripped.
     */
    public function testCssExpressionInStyleStripped(): void
    {
        $filter = $this->makeFilter(['p'], ['style'], 0, 0, 1);
        $result = $filter->clean('<p style="background:expression(alert(1))">x</p>', 'HTML');
        self::assertStringNotContainsString('expression(', $result);
    }

    // =========================================================================
    // clean() — STRING type (calls _decode then _remove)
    // =========================================================================

    public static function stringCleanProvider(): array
    {
        return [
            // Plain text unchanged
            'plain text'                           => ['Hello world', 'Hello world'],

            // Script tag in STRING mode: tags stripped, text content kept
            'script in string mode stripped'       => ['<script>alert(1)</script>', 'alert(1)'],

            // Blacklisted tags also stripped in STRING mode
            'iframe in string mode stripped'       => ['<iframe src="evil.html"></iframe>', ''],

            // Empty string
            'empty string'                         => ['', ''],

            // Safe markup: all tags stripped in default (empty whitelist) mode
            'paragraph in string mode'             => ['<p>text</p>', 'text'],
        ];
    }

    #[DataProvider('stringCleanProvider')]
    public function testStringClean(string $input, string $expected): void
    {
        $filter = $this->makeFilter();
        self::assertSame($expected, $filter->clean($input, 'STRING'));
    }

    /**
     * STRING mode with entity-encoded XSS: _decode converts &lt; to <, then _remove strips the tag.
     * The inner text "alert(1)" is NOT stripped — it's the text node inside the script tag.
     */
    public function testEncodedScriptTagDecodedThenStripped(): void
    {
        $filter = $this->makeFilter();
        // &lt;script&gt; → <script> after _decode, then stripped by _remove
        // The text node "alert(1)" remains after the script tag is removed
        $result = $filter->clean('&lt;script&gt;alert(1)&lt;/script&gt;', 'STRING');
        self::assertStringNotContainsString('<script>', $result);
        self::assertStringNotContainsString('<script', $result);
    }

    // =========================================================================
    // RAW type — no filtering at all
    // =========================================================================

    public function testRawTypeReturnsUnchanged(): void
    {
        $filter = $this->makeFilter();
        $input  = '<script>alert("xss")</script>';
        self::assertSame($input, $filter->clean($input, 'RAW'));
    }

    public function testRawTypeReturnsArrayUnchanged(): void
    {
        $filter = $this->makeFilter();
        $input  = ['foo' => '<script>evil</script>', 'bar' => 42];
        self::assertSame($input, $filter->clean($input, 'RAW'));
    }

    // =========================================================================
    // Tag whitelist mode (tagsMethod=0): only listed tags survive
    // =========================================================================

    public function testTagWhitelistAllowsListedTag(): void
    {
        // Allow only <b>; <i> should be stripped, <b> should pass
        $filter = $this->makeFilter(['b'], [], 0, 1, 1); // attrMethod=1 to allow attrs
        $result = $filter->clean('<b>bold</b><i>italic</i>', 'HTML');
        // <b> tag is in whitelist → kept
        self::assertStringContainsString('<b>bold</b>', $result);
        // <i> tag is not in whitelist → stripped, text remains
        self::assertStringContainsString('italic', $result);
        self::assertStringNotContainsString('<i>', $result);
    }

    public function testTagWhitelistStripsUnlistedTag(): void
    {
        $filter = $this->makeFilter(['b'], [], 0, 1, 1);
        $result = $filter->clean('<i>italic</i>', 'HTML');
        self::assertStringNotContainsString('<i>', $result);
        self::assertStringContainsString('italic', $result);
    }

    // =========================================================================
    // Tag blacklist mode (tagsMethod=1): listed tags are stripped, others kept
    // =========================================================================

    public function testTagBlacklistStripsListedTag(): void
    {
        // Blacklist <b>; <i> should be kept
        $filter = $this->makeFilter(['b'], [], 1, 1, 1);
        $result = $filter->clean('<b>bold</b><i>italic</i>', 'HTML');
        self::assertStringNotContainsString('<b>', $result);
        self::assertStringContainsString('<i>', $result);
        self::assertStringContainsString('italic', $result);
    }

    public function testTagBlacklistKeepsUnlistedTag(): void
    {
        $filter = $this->makeFilter(['b'], [], 1, 1, 1);
        $result = $filter->clean('<i>italic</i>', 'HTML');
        self::assertStringContainsString('<i>', $result);
    }

    // =========================================================================
    // xssAuto=0: disable automatic blacklist
    // =========================================================================

    public function testXssAutoOffWithWhitelistModeEmptyListStripsEverything(): void
    {
        // xssAuto=0 but tagsMethod=0 (whitelist) with empty list → no tags allowed
        $filter = $this->makeFilter([], [], 0, 0, 0);
        $result = $filter->clean('<script>alert(1)</script>', 'HTML');
        // In whitelist mode with empty list, no tags pass — text content may remain
        self::assertStringNotContainsString('<script>', $result);
    }

    public function testXssAutoOffWithBlacklistModeAllowsAllTags(): void
    {
        // xssAuto=0 and tagsMethod=1 (blacklist mode) with empty tagsArray
        // means ALL tags are allowed (none blacklisted by user, xssAuto off)
        $filter = $this->makeFilter([], [], 1, 0, 0);
        $result = $filter->clean('<p>hello</p>', 'HTML');
        self::assertStringContainsString('<p>', $result);
    }

    public function testXssAutoOffAllowsScriptTagInBlacklistMode(): void
    {
        // xssAuto=0, blacklist mode, empty user blacklist → script passes through
        $filter = $this->makeFilter([], [], 1, 0, 0);
        $result = $filter->clean('<script>alert(1)</script>', 'HTML');
        self::assertStringContainsString('<script>', $result);
    }

    // =========================================================================
    // checkAttribute() static method
    // =========================================================================

    public static function checkAttributeProvider(): array
    {
        return [
            // Dangerous — expect true (these should be blocked)
            'javascript: href'              => [['href', 'javascript:alert(1)'], true],
            'javascript: uppercase'         => [['href', 'JavaScript:alert(1)'], true],
            'vbscript: href'                => [['href', 'vbscript:msgbox(1)'], true],
            'behaviour: href'               => [['href', 'behaviour:something'], true],
            'mocha: href'                   => [['href', 'mocha:something'], true],
            'livescript: href'              => [['href', 'livescript:foo'], true],
            'style with expression'         => [['style', 'color:expression(alert(1))'], true],
            'style expression uppercase'    => [['STYLE', 'color:Expression(alert(1))'], true],

            // Safe — expect false (these should be allowed)
            'normal https href'             => [['href', 'https://example.com'], false],
            'normal http href'              => [['href', 'http://example.com'], false],
            'src attribute'                 => [['src', 'image.png'], false],
            'class attribute'               => [['class', 'my-class'], false],
            'style without expression'      => [['style', 'color:red'], false],
            'empty value'                   => [['href', ''], false],
            'data-attr'                     => [['data-value', 'something'], false],
        ];
    }

    #[DataProvider('checkAttributeProvider')]
    public function testCheckAttribute(array $attrSubSet, bool $expected): void
    {
        self::assertSame($expected, Filter::checkAttribute($attrSubSet));
    }

    // =========================================================================
    // getInstance() caching
    // =========================================================================

    public function testGetInstanceReturnsSameObjectForSameParams(): void
    {
        $a = Filter::getInstance(['b'], ['href'], 0, 0, 1);
        $b = Filter::getInstance(['b'], ['href'], 0, 0, 1);
        self::assertSame($a, $b);
    }

    public function testGetInstanceReturnsDifferentObjectForDifferentParams(): void
    {
        $a = Filter::getInstance(['b'], [], 0, 0, 1);
        $b = Filter::getInstance(['i'], [], 0, 0, 1);
        self::assertNotSame($a, $b);
    }

    public function testGetInstanceReturnsFilterObject(): void
    {
        $instance = Filter::getInstance();
        self::assertInstanceOf(Filter::class, $instance);
    }

    // =========================================================================
    // Constructor normalises tag/attr arrays to lowercase
    // =========================================================================

    public function testConstructorNormalisesTagsToLowercase(): void
    {
        $filter = $this->makeFilter(['B', 'STRONG'], [], 0, 0, 1);
        self::assertContains('b', $filter->tagsArray);
        self::assertContains('strong', $filter->tagsArray);
        self::assertNotContains('B', $filter->tagsArray);
        self::assertNotContains('STRONG', $filter->tagsArray);
    }

    public function testConstructorNormalisesAttrsToLowercase(): void
    {
        $filter = $this->makeFilter([], ['HREF', 'CLASS'], 0, 0, 1);
        self::assertContains('href', $filter->attrArray);
        self::assertContains('class', $filter->attrArray);
        self::assertNotContains('HREF', $filter->attrArray);
        self::assertNotContains('CLASS', $filter->attrArray);
    }

    public function testConstructorAssignsMethodValues(): void
    {
        $filter = $this->makeFilter([], [], 1, 1, 0);
        self::assertSame(1, $filter->tagsMethod);
        self::assertSame(1, $filter->attrMethod);
        self::assertSame(0, $filter->xssAuto);
    }

    // =========================================================================
    // Default blacklist properties are set correctly
    // =========================================================================

    public function testDefaultTagBlacklistContainsExpectedTags(): void
    {
        $filter  = $this->makeFilter();
        $expected = [
            'applet', 'body', 'bgsound', 'base', 'basefont', 'embed',
            'frame', 'frameset', 'head', 'html', 'id', 'iframe', 'ilayer',
            'layer', 'link', 'meta', 'name', 'object', 'script', 'style',
            'title', 'xml',
        ];

        foreach ($expected as $tag) {
            self::assertContains($tag, $filter->tagBlacklist, "Tag '$tag' missing from tagBlacklist");
        }
    }

    public function testDefaultAttrBlacklistContainsExpectedAttrs(): void
    {
        $filter   = $this->makeFilter();
        $expected = ['action', 'background', 'codebase', 'dynsrc', 'lowsrc'];

        foreach ($expected as $attr) {
            self::assertContains($attr, $filter->attrBlacklist, "Attr '$attr' missing from attrBlacklist");
        }
    }

    // =========================================================================
    // Malformed / edge-case HTML
    // =========================================================================

    public function testMalformedUnclosedTagHandled(): void
    {
        $filter = $this->makeFilter();
        // Should not throw; just handle gracefully
        $result = $filter->clean('<b>unclosed', 'HTML');
        self::assertIsString($result);
    }

    public function testMalformedDoubleOpenBracketHandled(): void
    {
        $filter = $this->makeFilter();
        $result = $filter->clean('<<b>>text</b>', 'HTML');
        self::assertIsString($result);
    }

    public function testNestedMalformedScriptTag(): void
    {
        $filter = $this->makeFilter();
        // Classic nested tag XSS attempt: <sc<script>ript>
        // After first pass the inner <script> is stripped, leaving <scr...ript>
        // but that ultimately doesn't form a valid "script" tag
        $result = $filter->clean('<sc<script>ript>alert(1)</sc</script>ript>', 'HTML');
        self::assertIsString($result);
        // The auto-blacklisted <script> portion must be stripped
        self::assertStringNotContainsString('<script>', $result);
    }

    // =========================================================================
    // On* event handler attributes (XSS)
    // =========================================================================

    public function testOnEventAttributeStrippedFromAllowedTag(): void
    {
        // Allow <img> with no attr restrictions (attrMethod=1, empty list)
        // but xssAuto strips on* handlers
        $filter = $this->makeFilter(['img'], [], 0, 1, 1);
        $result = $filter->clean('<img src="img.png" onload="evil()" />', 'HTML');
        self::assertStringNotContainsString('onload', $result);
    }

    public function testOnerrorAttributeStrippedFromAllowedTag(): void
    {
        $filter = $this->makeFilter(['img'], [], 0, 1, 1);
        $result = $filter->clean('<img src="img.png" onerror="evil()" />', 'HTML');
        self::assertStringNotContainsString('onerror', $result);
    }

    public function testOnmouseoverAttributeStrippedFromAllowedTag(): void
    {
        $filter = $this->makeFilter(['a'], ['href'], 0, 0, 1);
        $result = $filter->clean('<a href="page.html" onmouseover="evil()">link</a>', 'HTML');
        self::assertStringNotContainsString('onmouseover', $result);
    }

    // =========================================================================
    // XSS vectors with explicit tag+attr whitelists
    // =========================================================================

    public function testJavascriptInImgSrcBlockedWhenSrcAllowed(): void
    {
        $filter = $this->makeFilter(['img'], ['src'], 0, 0, 1);
        $result = $filter->clean('<img src="javascript:alert(1)" />', 'HTML');
        self::assertStringNotContainsString('javascript:', $result);
    }

    public function testVbscriptHrefBlocked(): void
    {
        $filter = $this->makeFilter(['a'], ['href'], 0, 0, 1);
        $result = $filter->clean('<a href="vbscript:msgbox(1)">x</a>', 'HTML');
        self::assertStringNotContainsString('vbscript:', $result);
    }
}
