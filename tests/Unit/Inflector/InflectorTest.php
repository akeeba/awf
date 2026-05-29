<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Inflector;

use Awf\Inflector\Inflector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InflectorTest extends TestCase
{
    protected function setUp(): void
    {
        Inflector::deleteCache();
    }

    protected function tearDown(): void
    {
        Inflector::deleteCache();
    }

    // -------------------------------------------------------------------------
    // pluralize
    // -------------------------------------------------------------------------

    public static function pluralizeProvider(): array
    {
        return [
            // Regular nouns
            'cat → cats'             => ['cat', 'cats'],
            'dog → dogs'             => ['dog', 'dogs'],
            'phone → phones'         => ['phone', 'phones'],
            // -x/-ch/-sh/-ss get -es
            'box → boxes'            => ['box', 'boxes'],
            'watch → watches'        => ['watch', 'watches'],
            'dish → dishes'          => ['dish', 'dishes'],
            // consonant + y → -ies
            'city → cities'          => ['city', 'cities'],
            'party → parties'        => ['party', 'parties'],
            // Specific irregular mappings
            'move → moves'           => ['move', 'moves'],
            'sex → sexes'            => ['sex', 'sexes'],
            'child → children'       => ['child', 'children'],
            'man → men'              => ['man', 'men'],
            'foot → feet'            => ['foot', 'feet'],
            'person → people'        => ['person', 'people'],
            'quiz → quizzes'         => ['quiz', 'quizzes'],
            'ox → oxen'              => ['ox', 'oxen'],
            'mouse → mice'           => ['mouse', 'mice'],
            'louse → lice'           => ['louse', 'lice'],
            // Latin/Greek endings
            'matrix → matrices'      => ['matrix', 'matrices'],
            'suffix → suffices'      => ['suffix', 'suffices'],
            'analysis → analyses'    => ['analysis', 'analyses'],
            'datum → data'           => ['datum', 'data'],
            'addendum → addenda'     => ['addendum', 'addenda'],
            'genus → genera'         => ['genus', 'genera'],
            'axis → axes'            => ['axis', 'axes'],
            'octopus → octopi'       => ['octopus', 'octopi'],
            'virus → viri'           => ['virus', 'viri'],
            'taxon → taxa'           => ['taxon', 'taxa'],
            'alumna → alumnae'       => ['alumna', 'alumnae'],
            // -o → -oes
            'hero → heroes'          => ['hero', 'heroes'],
            'tomato → tomatoes'      => ['tomato', 'tomatoes'],
            // -bus → -buses
            'bus → buses'            => ['bus', 'buses'],
            // -alias/-status → -es
            'alias → aliases'        => ['alias', 'aliases'],
            'status → statuses'      => ['status', 'statuses'],
            // Already plural forms remain plural
            'men (already plural)'        => ['men', 'men'],
            'children (already plural)'   => ['children', 'children'],
            'feet (already plural)'       => ['feet', 'feet'],
            'people (already plural)'     => ['people', 'people'],
            'taxa (already plural)'       => ['taxa', 'taxa'],
            'mice (already plural)'       => ['mice', 'mice'],
            // Uncountable nouns are returned unchanged
            'aircraft (uncountable)'     => ['aircraft', 'aircraft'],
            'cannon (uncountable)'       => ['cannon', 'cannon'],
            'deer (uncountable)'         => ['deer', 'deer'],
            'equipment (uncountable)'    => ['equipment', 'equipment'],
            'fish (uncountable)'         => ['fish', 'fish'],
            'information (uncountable)'  => ['information', 'information'],
            'money (uncountable)'        => ['money', 'money'],
            'moose (uncountable)'        => ['moose', 'moose'],
            'news (uncountable)'         => ['news', 'news'],
            'rice (uncountable)'         => ['rice', 'rice'],
            'series (uncountable)'       => ['series', 'series'],
            'sheep (uncountable)'        => ['sheep', 'sheep'],
            'species (uncountable)'      => ['species', 'species'],
            'swine (uncountable)'        => ['swine', 'swine'],
        ];
    }

    #[DataProvider('pluralizeProvider')]
    public function testPluralize(string $word, string $expected): void
    {
        self::assertSame($expected, Inflector::pluralize($word));
    }

    // -------------------------------------------------------------------------
    // singularize
    // -------------------------------------------------------------------------

    public static function singularizeProvider(): array
    {
        return [
            // Regular plurals
            'cats → cat'             => ['cats', 'cat'],
            'dogs → dog'             => ['dogs', 'dog'],
            'phones → phone'         => ['phones', 'phone'],
            // -xes/-ches/-shes → remove -es
            'boxes → box'            => ['boxes', 'box'],
            'watches → watch'        => ['watches', 'watch'],
            'dishes → dish'          => ['dishes', 'dish'],
            // -ies → -y
            'cities → city'          => ['cities', 'city'],
            'parties → party'        => ['parties', 'party'],
            // Specific irregular mappings
            'moves → move'           => ['moves', 'move'],
            'sexes → sex'            => ['sexes', 'sex'],
            'children → child'       => ['children', 'child'],
            'men → man'              => ['men', 'man'],
            'feet → foot'            => ['feet', 'foot'],
            'people → person'        => ['people', 'person'],
            'taxa → taxon'           => ['taxa', 'taxon'],
            'databases → database'   => ['databases', 'database'],
            'menus → menu'           => ['menus', 'menu'],
            'cookies → cookie'       => ['cookies', 'cookie'],
            'quizzes → quiz'         => ['quizzes', 'quiz'],
            'mice → mouse'           => ['mice', 'mouse'],
            // Latin/Greek endings
            'matrices → matrix'      => ['matrices', 'matrix'],
            'suffices → suffix'      => ['suffices', 'suffix'],
            'analyses → analysis'    => ['analyses', 'analysis'],
            'data → datum'           => ['data', 'datum'],
            'addenda → addendum'     => ['addenda', 'addendum'],
            'genera → genus'         => ['genera', 'genus'],
            'axes → axis'            => ['axes', 'axis'],
            // -oes → -o
            'heroes → hero'          => ['heroes', 'hero'],
            'tomatoes → tomato'      => ['tomatoes', 'tomato'],
            // -buses → -bus
            'buses → bus'            => ['buses', 'bus'],
            // -aliases/-statuses
            'aliases → alias'        => ['aliases', 'alias'],
            'statuses → status'      => ['statuses', 'status'],
            // Uncountable nouns are returned unchanged
            'news (uncountable)'     => ['news', 'news'],
            'series (uncountable)'   => ['series', 'series'],
        ];
    }

    #[DataProvider('singularizeProvider')]
    public function testSingularize(string $word, string $expected): void
    {
        self::assertSame($expected, Inflector::singularize($word));
    }

    // -------------------------------------------------------------------------
    // camelize
    // -------------------------------------------------------------------------

    public static function camelizeProvider(): array
    {
        return [
            'underscore_separated'        => ['foo_bar', 'FooBar'],
            'multi-part underscore'        => ['foo_bar_baz', 'FooBarBaz'],
            'space separated'              => ['foo bar', 'FooBar'],
            'hyphen becomes space then UC' => ['foo-bar', 'FooBar'],
            'apostrophe stripped'          => ["who's online", 'WhoSOnline'],
            'already CamelCase lowercased' => ['FooBar', 'Foobar'],
            'single word'                  => ['foo', 'Foo'],
        ];
    }

    #[DataProvider('camelizeProvider')]
    public function testCamelize(string $word, string $expected): void
    {
        self::assertSame($expected, Inflector::camelize($word));
    }

    // -------------------------------------------------------------------------
    // underscore
    // -------------------------------------------------------------------------

    public static function underscoreProvider(): array
    {
        return [
            'CamelCase'           => ['FooBar', 'foo_bar'],
            'multi-part CamelCase' => ['FooBarBaz', 'foo_bar_baz'],
            'already underscored' => ['foo_bar', 'foo_bar'],
            'space separated'     => ['foo bar', 'foo_bar'],
            'single word'         => ['Foo', 'foo'],
            'all lowercase'       => ['foo', 'foo'],
        ];
    }

    #[DataProvider('underscoreProvider')]
    public function testUnderscore(string $word, string $expected): void
    {
        self::assertSame($expected, Inflector::underscore($word));
    }

    // -------------------------------------------------------------------------
    // humanize
    // -------------------------------------------------------------------------

    public static function humanizeProvider(): array
    {
        return [
            'single underscore'   => ['foo_bar', 'Foo Bar'],
            'multi underscore'    => ['foo_bar_baz', 'Foo Bar Baz'],
            'all uppercase input' => ['FOO_BAR', 'Foo Bar'],
            'single word'         => ['foo', 'Foo'],
        ];
    }

    #[DataProvider('humanizeProvider')]
    public function testHumanize(string $word, string $expected): void
    {
        self::assertSame($expected, Inflector::humanize($word));
    }

    // -------------------------------------------------------------------------
    // tableize / classify (round-trip)
    // -------------------------------------------------------------------------

    public static function tableizeProvider(): array
    {
        return [
            'Person → people'        => ['Person', 'people'],
            'Cat → cats'             => ['Cat', 'cats'],
            'DataModel → data_models' => ['DataModel', 'data_models'],
            'Menu → menus'           => ['Menu', 'menus'],
        ];
    }

    #[DataProvider('tableizeProvider')]
    public function testTableize(string $className, string $expected): void
    {
        self::assertSame($expected, Inflector::tableize($className));
    }

    public static function classifyProvider(): array
    {
        return [
            'people → Person'       => ['people', 'Person'],
            'cats → Cat'            => ['cats', 'Cat'],
            'data_models → DataModel' => ['data_models', 'DataModel'],
            'menus → Menu'          => ['menus', 'Menu'],
        ];
    }

    #[DataProvider('classifyProvider')]
    public function testClassify(string $tableName, string $expected): void
    {
        self::assertSame($expected, Inflector::classify($tableName));
    }

    // -------------------------------------------------------------------------
    // variablize
    // -------------------------------------------------------------------------

    public static function variablizeProvider(): array
    {
        return [
            'CamelCase'             => ['FooBar', 'fooBar'],
            'underscore_separated'  => ['foo_bar', 'fooBar'],
            'single word'           => ['Foo', 'foo'],
        ];
    }

    #[DataProvider('variablizeProvider')]
    public function testVariablize(string $string, string $expected): void
    {
        self::assertSame($expected, Inflector::variablize($string));
    }

    // -------------------------------------------------------------------------
    // explode / implode
    // -------------------------------------------------------------------------

    public function testExplode(): void
    {
        self::assertSame(['foo', 'bar'], Inflector::explode('FooBar'));
        self::assertSame(['foo', 'bar', 'baz'], Inflector::explode('FooBarBaz'));
        self::assertSame(['foo'], Inflector::explode('Foo'));
    }

    public function testImplode(): void
    {
        self::assertSame('FooBar', Inflector::implode(['foo', 'bar']));
        self::assertSame('FooBarBaz', Inflector::implode(['foo', 'bar', 'baz']));
        self::assertSame('Foo', Inflector::implode(['foo']));
    }

    public function testExplodeImplodeRoundTrip(): void
    {
        $original = 'FooBarBaz';
        self::assertSame($original, Inflector::implode(Inflector::explode($original)));
    }

    // -------------------------------------------------------------------------
    // getPart
    // -------------------------------------------------------------------------

    public function testGetPartPositiveIndex(): void
    {
        self::assertSame('foo', Inflector::getPart('FooBarBaz', 0));
        self::assertSame('bar', Inflector::getPart('FooBarBaz', 1));
        self::assertSame('baz', Inflector::getPart('FooBarBaz', 2));
    }

    public function testGetPartNegativeIndex(): void
    {
        self::assertSame('baz', Inflector::getPart('FooBarBaz', -1));
        self::assertSame('bar', Inflector::getPart('FooBarBaz', -2));
        self::assertSame('foo', Inflector::getPart('FooBarBaz', -3));
    }

    public function testGetPartOutOfBoundsReturnsDefault(): void
    {
        self::assertNull(Inflector::getPart('FooBarBaz', 10));
        self::assertSame('fallback', Inflector::getPart('FooBarBaz', 10, 'fallback'));
    }

    // -------------------------------------------------------------------------
    // isSingular / isPlural
    // -------------------------------------------------------------------------

    public static function isSingularProvider(): array
    {
        return [
            'cat is singular'         => ['cat', true],
            'cats is not singular'    => ['cats', false],
            'person is singular'      => ['person', true],
            'people is not singular'  => ['people', false],
            'child is singular'       => ['child', true],
            'children is not singular' => ['children', false],
            // Uncountable are always treated as singular
            'information (uncountable)' => ['information', true],
            'news (uncountable)'        => ['news', true],
        ];
    }

    #[DataProvider('isSingularProvider')]
    public function testIsSingular(string $word, bool $expected): void
    {
        self::assertSame($expected, Inflector::isSingular($word));
    }

    public static function isPluralProvider(): array
    {
        return [
            'cats is plural'          => ['cats', true],
            'cat is not plural'       => ['cat', false],
            'people is plural'        => ['people', true],
            'person is not plural'    => ['person', false],
            'children is plural'      => ['children', true],
            'child is not plural'     => ['child', false],
            // Uncountable are always treated as not plural
            'information (uncountable)' => ['information', false],
            'news (uncountable)'        => ['news', false],
        ];
    }

    #[DataProvider('isPluralProvider')]
    public function testIsPlural(string $word, bool $expected): void
    {
        self::assertSame($expected, Inflector::isPlural($word));
    }

    // -------------------------------------------------------------------------
    // addWord / deleteCache
    // -------------------------------------------------------------------------

    public function testAddWordOverridesPluralize(): void
    {
        Inflector::addWord('platypus', 'platypodes');

        self::assertSame('platypodes', Inflector::pluralize('platypus'));
    }

    public function testAddWordOverridesSingularize(): void
    {
        Inflector::addWord('platypus', 'platypodes');

        self::assertSame('platypus', Inflector::singularize('platypodes'));
    }

    public function testDeleteCacheRestoresRuleBasedBehaviour(): void
    {
        // Use a word whose custom plural differs from what the rules would produce.
        Inflector::addWord('foo', 'foos_custom');
        self::assertSame('foos_custom', Inflector::pluralize('foo'));

        Inflector::deleteCache();

        // After clearing the cache the rules apply: '/$/' appends 's'.
        self::assertSame('foos', Inflector::pluralize('foo'));
    }
}
