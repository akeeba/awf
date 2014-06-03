<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Tests\Awf\Inflector;

use Awf\Inflector\Inflector;

class InflectorTest extends \PHPUnit_Framework_TestCase
{
	public function getTestPluralizeData()
	{
		return array(
			array("move", "moves", "Pluralise: Move"),
			array("moves", "moves", "Pluralise: Moves"),
			array("sex", "sexes", "Pluralise: Sex"),
			array("sexes", "sexes", "Pluralise: Sexes"),
			array("child", "children", "Pluralise: Child"),
			array("children", "children", "Pluralise: Children"),
			array("woman", "women", "Pluralisation of words in -an not honoured"),
			array("women", "women", "Should return the same as it's already a plural (words in -an)"),
			array("foot", "feet", "Pluralise: Foot"),
			array("feet", "feet", "Pluralise: Feet"),
			array("person", "people", "Pluralise: Person"),
			array("people", "people", "Pluralise: People"),
			array("taxon", "taxa", "Pluralise: Taxon"),
			array("taxa", "taxa", "Pluralise: Taxa"),
			array("quiz", "quizzes", "Pluralise: Quiz"),
			array("quizzes", "quizzes", "Pluralise: Quizzes"),
			array("ox", "oxen", "Pluralise: Ox"),
			array("oxen", "oxen", "Pluralise: Oxen"),
			array("mouse", "mice", "Pluralise: Mouse"),
			array("mice", "mice", "Pluralise: Mice"),
			array("matrix", "matrices", "Pluralise: Matrix"),
			array("matrices", "matrices", "Pluralise: Matrices"),
			array("vertex", "vertices", "Pluralise: Vertex"),
			array("vertices", "vertices", "Pluralise: Vertices"),
			array("index", "indices", "Pluralise: Index"),
			array("indices", "indices", "Pluralise: Indices"),
			array("suffix", "suffices", "Pluralise: Suffix"),
			array("suffices", "suffices", "Pluralise: Suffices"),
			array("codex", "codices", "Pluralise: Codex"),
			array("codices", "codices", "Pluralise: Codices"),
			array("onyx", "onyxes", "Pluralise: onyx"),
			array("onyxes", "onyxes", "Pluralise: onyxes"),
			array("leech", "leeches", "Pluralise: Leech"),
			array("leeches", "leeches", "Pluralise: Leeches"),
			array("glass", "glasses", "Pluralise: Glass"),
			array("glasses", "glasses", "Pluralise: Glasses"),
			array("mesh", "meshes", "Pluralise: Mesh"),
			array("meshes", "meshes", "Pluralise: Meshes"),
			array("soliloquy", "soliloquies", "Pluralise: Soliloquy"),
			array("soliloquies", "soliloquies", "Pluralise: Soliloquies"),
			array("baby", "babies", "Pluralise: Baby"),
			array("babies", "babies", "Pluralise: Babies"),
			array("elf", "elves", "Pluralise: Elf"),
			array("elves", "elves", "Pluralise: Elves"),
			array("life", "lives", "Pluralise: Life"),
			array("lives", "lives", "Pluralise: Lives"),
			array("antithesis", "antitheses", "Pluralise: Antitheses"),
			array("antitheses", "antitheses", "Pluralise: Antitheses"),
			array("consortium", "consortia", "Pluralise: consortium"),
			array("consortia", "consortia", "Pluralise: consortia"),
			array("addendum", "addenda", "Pluralise: addendum"),
			array("addenda", "addenda", "Pluralise: addenda"),
			array("alumna", "alumnae", "Pluralise: alumna"),
			array("alumnae", "alumnae", "Pluralise: alumnae"),
			array("formula", "formulae", "Pluralise: formula"),
			array("formulae", "formulae", "Pluralise: formulae"),
			array("buffalo", "buffaloes", "Pluralise: buffalo"),
			array("buffaloes", "buffaloes", "Pluralise: buffaloes"),
			array("tomato", "tomatoes", "Pluralise: tomato"),
			array("tomatoes", "tomatoes", "Pluralise: tomatoes"),
			array("hero", "heroes", "Pluralise: hero"),
			array("heroes", "heroes", "Pluralise: heroes"),
			array("bus", "buses", "Pluralise: bus"),
			array("buses", "buses", "Pluralise: buses"),
			array("alias", "aliases", "Pluralise: alias"),
			array("aliases", "aliases", "Pluralise: aliases"),
			array("octopus", "octopi", "Pluralise: octopus"),
			array("octopi", "octopi", "Pluralise: octopi"),
			array("virus", "viri", "Pluralise: virus"),
			array("viri", "viri", "Pluralise: viri"),
			array("genus", "genera", "Pluralise: genus"),
			array("genera", "genera", "Pluralise: genera"),
			array("axis", "axes", "Pluralise: axis"),
			array("axes", "axes", "Pluralise: axes"),
			array("testis", "testes", "Pluralise: testis"),
			array("testes", "testes", "Pluralise: testes"),

			array("dwarf", "dwarves", "Pluralise: Dwarf"),
			array("dwarves", "dwarves", "Pluralise: Dwarves"),
			array("guy", "guys", "Pluralise: Guy"),
			array("guy", "guys", "Pluralise: Guy"),
			array("relief", "reliefs", "Pluralise: Relief"),
			array("reliefs", "reliefs", "Pluralise: Reliefs"),

			array("aircraft", "aircraft", "Pluralise: aircraft (special)"),
			array("cannon", "cannon", "Pluralise: cannon (special)"),
			array("deer", "deer", "Pluralise: deer (special)"),
			array("equipment", "equipment", "Pluralise: equipment (special)"),
			array("fish", "fish", "Pluralise: Fish (special)"),
			array("information", "information", "Pluralise: information (special)"),
			array("money", "money", "Pluralise: money (special)"),
			array("moose", "moose", "Pluralise: moose (special)"),
			array("rice", "rice", "Pluralise: rice (special)"),
			array("series", "series", "Pluralise: series (special)"),
			array("sheep", "sheep", "Pluralise: sheep (special)"),
			array("species", "species", "Pluralise: species (special)"),
			array("swine", "swine", "Pluralise: swine (special)"),

			array("word", "words", 'Should return plural'),
			array("words", "words", "Should return the same as it's already a plural"),

			array("cookie", "cookies", "Pluralise: cookie"),
			array("cookies", "cookies", "Pluralise: cookies"),
			array("database", "databases", "Pluralise: database"),
			array("databases", "databases", "Pluralise: databases"),
			array("crisis", "crises", "Pluralise: crisis"),
			array("crises", "crises", "Pluralise: crises"),
			array("shoe", "shoes", "Pluralise: shoe"),
			array("shoes", "shoes", "Pluralise: shoes"),
			array("backhoe", "backhoes", "Pluralise: backhoe"),
			array("backhoes", "backhoes", "Pluralise: backhoes"),
			array("movie", "movies", "Pluralise: movie"),
			array("movies", "movies", "Pluralise: movies"),
			array("vie", "vies", "Pluralise: vie"),
			array("vies", "vies", "Pluralise: vies"),
			array("narrative", "narratives", "Pluralise: narrative"),
			array("narratives", "narratives", "Pluralise: narratives"),
			array("hive", "hives", "Pluralise: hive"),
			array("hives", "hives", "Pluralise: hives"),
			array("analysis", "analyses", "Pluralise: analysis"),
			array("analyses", "analyses", "Pluralise: analyses"),
			array("basis", "bases", "Pluralise: basis"),
			array("bases", "bases", "Pluralise: bases"),
			array("diagnosis", "diagnoses", "Pluralise: diagnosis"),
			array("diagnoses", "diagnoses", "Pluralise: diagnoses"),
			array("parenthesis", "parentheses", "Pluralise: parenthesis"),
			array("parentheses", "parentheses", "Pluralise: parentheses"),
			array("prognosis", "prognoses", "Pluralise: prognosis"),
			array("prognoses", "prognoses", "Pluralise: prognoses"),
			array("synopsis", "synopses", "Pluralise: synopsis"),
			array("synopses", "synopses", "Pluralise: synopses"),
			array("thesis", "theses", "Pluralise: thesis"),
			array("theses", "theses", "Pluralise: theses"),
			array("news", "news", "Pluralise: news"),
		);
	}

	public function getTestSingularizeData()
	{
		return array(
			array("move", "move", "Singularise: Move"),
			array("moves", "move", "Singularise: Moves"),
			array("sex", "sex", "Singularise: Sex"),
			array("sexes", "sex", "Singularise: Sexes"),
			array("child", "child", "Singularise: Child"),
			array("children", "child", "Singularise: Children"),
			array("woman", "woman", "Pluralisation of words in -an not honoured"),
			array("women", "woman", "Should return the same as it's already a plural (words in -an)"),
			array("foot", "foot", "Singularise: Foot"),
			array("feet", "foot", "Singularise: Feet"),
			array("person", "person", "Singularise: Person"),
			array("people", "person", "Singularise: People"),
			array("taxon", "taxon", "Singularise: Taxon"),
			array("taxa", "taxon", "Singularise: Taxa"),
			array("quiz", "quiz", "Singularise: Quiz"),
			array("quizzes", "quiz", "Singularise: Quizzes"),
			array("ox", "ox", "Singularise: Ox"),
			array("oxen", "ox", "Singularise: Oxen"),
			array("mouse", "mouse", "Singularise: Mouse"),
			array("mice", "mouse", "Singularise: Mice"),
			array("matrix", "matrix", "Singularise: Matrix"),
			array("matrices", "matrix", "Singularise: Matrices"),
			array("vertex", "vertex", "Singularise: Vertex"),
			array("vertices", "vertex", "Singularise: Vertices"),
			array("index", "index", "Singularise: Index"),
			array("indices", "index", "Singularise: Indices"),
			array("suffix", "suffix", "Singularise: Suffix"),
			array("suffices", "suffix", "Singularise: Suffices"),
			array("codex", "codex", "Singularise: Codex"),
			array("codices", "codex", "Singularise: Codices"),
			array("onyx", "onyx", "Singularise: onyx"),
			array("onyxes", "onyx", "Singularise: onyxes"),
			array("leech", "leech", "Singularise: Leech"),
			array("leeches", "leech", "Singularise: Leeches"),
			array("glass", "glass", "Singularise: Glass"),
			array("glasses", "glass", "Singularise: Glasses"),
			array("mesh", "mesh", "Singularise: Mesh"),
			array("meshes", "mesh", "Singularise: Meshes"),
			array("soliloquy", "soliloquy", "Singularise: Soliloquy"),
			array("soliloquies", "soliloquy", "Singularise: Soliloquies"),
			array("baby", "baby", "Singularise: Baby"),
			array("babies", "baby", "Singularise: Babies"),
			array("elf", "elf", "Singularise: Elf"),
			array("elves", "elf", "Singularise: Elves"),
			array("life", "life", "Singularise: Life"),
			array("lives", "life", "Singularise: Lives"),
			array("antithesis", "antithesis", "Singularise: Antitheses"),
			array("antitheses", "antithesis", "Singularise: Antitheses"),
			array("consortium", "consortium", "Singularise: consortium"),
			array("consortia", "consortium", "Singularise: consortia"),
			array("addendum", "addendum", "Singularise: addendum"),
			array("addenda", "addendum", "Singularise: addenda"),
			array("alumna", "alumna", "Singularise: alumna"),
			array("alumnae", "alumna", "Singularise: alumnae"),
			array("formula", "formula", "Singularise: formula"),
			array("formulae", "formula", "Singularise: formulae"),
			array("buffalo", "buffalo", "Singularise: buffalo"),
			array("buffaloes", "buffalo", "Singularise: buffaloes"),
			array("tomato", "tomato", "Singularise: tomato"),
			array("tomatoes", "tomato", "Singularise: tomatoes"),
			array("hero", "hero", "Singularise: hero"),
			array("heroes", "hero", "Singularise: heroes"),
			array("bus", "bus", "Singularise: bus"),
			array("buses", "bus", "Singularise: buses"),
			array("alias", "alias", "Singularise: alias"),
			array("aliases", "alias", "Singularise: aliases"),
			array("octopus", "octopus", "Singularise: octopus"),
			array("octopi", "octopus", "Singularise: octopi"),
			array("virus", "virus", "Singularise: virus"),
			array("viri", "virus", "Singularise: viri"),
			array("genus", "genus", "Singularise: genus"),
			array("genera", "genus", "Singularise: genera"),
			array("axis", "axis", "Singularise: axis"),
			array("axes", "axis", "Singularise: axes"),
			array("testis", "testis", "Singularise: testis"),
			array("testes", "testis", "Singularise: testes"),

			array("dwarf", "dwarf", "Singularise: Dwarf"),
			array("dwarves", "dwarf", "Singularise: Dwarves"),
			array("guy", "guy", "Singularise: Guy"),
			array("guy", "guy", "Singularise: Guy"),
			array("relief", "relief", "Singularise: Relief"),
			array("reliefs", "relief", "Singularise: Reliefs"),

			array("aircraft", "aircraft", "Singularise: aircraft (special)"),
			array("cannon", "cannon", "Singularise: cannon (special)"),
			array("deer", "deer", "Singularise: deer (special)"),
			array("equipment", "equipment", "Singularise: equipment (special)"),
			array("fish", "fish", "Singularise: Fish (special)"),
			array("information", "information", "Singularise: information (special)"),
			array("money", "money", "Singularise: money (special)"),
			array("moose", "moose", "Singularise: moose (special)"),
			array("rice", "rice", "Singularise: rice (special)"),
			array("series", "series", "Singularise: series (special)"),
			array("sheep", "sheep", "Singularise: sheep (special)"),
			array("species", "species", "Singularise: species (special)"),
			array("swine", "swine", "Singularise: swine (special)"),

			array("word", "word", 'Should return singular'),
			array("words", "word", "Should return the same as it's already a singular"),

			array("cookie", "cookie", "Singularise: cookie"),
			array("cookies", "cookie", "Singularise: cookies"),
			array("database", "database", "Singularise: database"),
			array("databases", "database", "Singularise: databases"),
			array("crisis", "crisis", "Singularise: crisis"),
			array("crises", "crisis", "Singularise: crises"),
			array("shoe", "shoe", "Singularise: shoe"),
			array("shoes", "shoe", "Singularise: shoes"),
			array("backhoe", "backhoe", "Singularise: backhoe"),
			array("backhoes", "backhoe", "Singularise: backhoes"),
			array("menu", "menu", "Singularise: menu"),
			array("menus", "menu", "Singularise: menu"),
			array("movie", "movie", "Singularise: movie"),
			array("movies", "movie", "Singularise: movies"),
			array("vie", "vie", "Singularise: vie"),
			array("vies", "vie", "Singularise: vies"),
			array("narrative", "narrative", "Singularise: narrative"),
			array("narratives", "narrative", "Singularise: narratives"),
			array("hive", "hive", "Singularise: hive"),
			array("hives", "hive", "Singularise: hives"),
			array("analysis", "analysis", "Singularise: analysis"),
			array("analyses", "analysis", "Singularise: analyses"),
			array("basis", "basis", "Singularise: basis"),
			array("bases", "basis", "Singularise: bases"),
			array("diagnosis", "diagnosis", "Singularise: diagnosis"),
			array("diagnoses", "diagnosis", "Singularise: diagnoses"),
			array("parenthesis", "parenthesis", "Singularise: parenthesis"),
			array("parentheses", "parenthesis", "Singularise: parentheses"),
			array("prognosis", "prognosis", "Singularise: prognosis"),
			array("prognoses", "prognosis", "Singularise: prognoses"),
			array("synopsis", "synopsis", "Singularise: synopsis"),
			array("synopses", "synopsis", "Singularise: synopses"),
			array("thesis", "thesis", "Singularise: thesis"),
			array("theses", "thesis", "Singularise: theses"),
			array("news", "news", "Singularise: news"),

		);
	}

	public function getTestCamelizeData()
	{
		return array(
			array("foo_bar", "FooBar", 'Underscores must act as camelization points'),
			array("foo bar", "FooBar", 'Spaces must act as camelization points'),
			array("foo's bar", "FooSBar", 'Punctuation must be stripped out'),
			array("foo.bar.123", "FooBar123", 'Numbers must be preserved'),
		);
	}

	public function getTestUnderscoreData()
	{
		return array(
			array("foo bar", "foo_bar", 'Spaces must act as underscore points'),
			array("FooBar", "foo_bar", 'CamelCase must be converted'),
		);
	}

	public function getTestExplodeData()
	{
		return array(
			array("foo bar", array('foo', 'bar'), 'Spaces must act as underscore points'),
			array("FooBar", array('foo', 'bar'), 'CamelCase must be converted'),
		);
	}

	public function getTestImplodeData()
	{
		return array(
			array(array('foo', 'bar'), "FooBar", 'Implosion failed'),
		);
	}

	public function getTestHumanizeData()
	{
		return array(
			array("foo_bar", 'Foo Bar', 'Humanize failed'),
			array("this_is_a_test", 'This Is A Test', 'Humanize failed'),
		);
	}

	public function getTestTableizeData()
	{
		return array(
			array("person", 'people', 'Pluralise words'),
			array("people", 'people', 'Retain plural forms'),
			array("SomeGoodPerson", 'some_good_people', 'Pluralise camelcase words'),
		);
	}

	public function getTestClassifyData()
	{
		return array(
			array("people", 'Person', 'Singularize words'),
			array("person", 'Person', 'Retain singular forms'),
			array("SomeGoodPeople", 'Somegoodperson', 'Singularize camelcase words'),
		);
	}

	public function getTestVariableizeData()
	{
		return array(
			array("foo_bar", "fooBar", 'Underscores must act as camelization points'),
			array("foo bar", "fooBar", 'Spaces must act as camelization points'),
			array("foo's bar", "fooSBar", 'Punctuation must be stripped out'),
			array("foo.bar.123", "fooBar123", 'Numbers must be preserved'),
		);
	}

	/**
	 * Test addWord method
	 */
	public function testAddWord()
	{
		Inflector::addWord('xoxosingular', 'xoxoplural');

		$res = Inflector::singularize('xoxoplural');
		$this->assertEquals($res, 'xoxosingular', 'Custom word could not be singularized');

		$res = Inflector::pluralize('xoxosingular');
		$this->assertEquals($res, 'xoxoplural', 'Custom word could not be pluralized');
	}

	/**
	 * Test pluralize method
	 *
	 * @dataProvider getTestPluralizeData
	 */
	public function testPluralize($word, $expect, $message)
	{
		Inflector::deleteCache();
		$res = Inflector::pluralize($word);
		$this->assertEquals(
			$res,
			$expect,
			$message
		);
	}

	/**
	 * Test singularize method
	 *
	 * @dataProvider getTestSingularizeData
	 */
	public function testSingularize($word, $expect, $message)
	{
		Inflector::deleteCache();
		$res = Inflector::singularize($word);
		$this->assertEquals(
			$res,
			$expect,
			$message
		);
	}

	/**
	 * Test camelize method
	 *
	 * @dataProvider getTestCamelizeData
	 */
	public function testCamelize($word, $expect, $message)
	{
		$res = Inflector::camelize($word);
		$this->assertEquals(
			$res,
			$expect,
			$message
		);
	}

	/**
	 * Test underscore method
	 *
	 * @dataProvider getTestUnderscoreData
	 */
	public function testUnderscore($word, $expect, $message)
	{
		$res = Inflector::underscore($word);
		$this->assertEquals(
			$res,
			$expect,
			$message
		);
	}

	/**
	 * Test explode method
	 *
	 * @dataProvider getTestExplodeData
	 */
	public function testExplode($word, $expect, $message)
	{
		$res = Inflector::explode($word);
		$this->assertEquals(
			$res,
			$expect,
			$message
		);
	}

	/**
	 * Test implode method
	 *
	 * @dataProvider getTestImplodeData
	 */
	public function testImplode($word, $expect, $message)
	{
		$res = Inflector::implode($word);
		$this->assertEquals(
			$res,
			$expect,
			$message
		);
	}

	/**
	 * Test humanize method
	 *
	 * @dataProvider getTestHumanizeData
	 */
	public function testHumanize($word, $expect, $message)
	{
		$res = Inflector::humanize($word);
		$this->assertEquals(
			$res,
			$expect,
			$message
		);
	}

	/**
	 * Test tableize method
	 *
	 * @dataProvider getTestTableizeData
	 */
	public function testTableize($word, $expect, $message)
	{
		$res = Inflector::tableize($word);
		$this->assertEquals(
			$res,
			$expect,
			$message
		);
	}

	/**
	 * Test classify method
	 *
	 * @dataProvider getTestClassifyData
	 */
	public function testClassify($word, $expect, $message)
	{
		$res = Inflector::classify($word);
		$this->assertEquals(
			$res,
			$expect,
			$message
		);
	}

	/**
	 * Test variableize method
	 *
	 * @dataProvider getTestVariableizeData
	 */
	public function testVariableize($word, $expect, $message)
	{
		$res = Inflector::variablize($word);
		$this->assertEquals(
			$res,
			$expect,
			$message
		);
	}
}
 