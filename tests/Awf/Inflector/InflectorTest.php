<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Tests\Awf\Inflector;

use Awf\Inflector\Inflector;
use Tests\Helpers\ReflectionHelper;

/**
 * Test class for Awf\Inflector\Inflector.
 *
 * @since  1.0
 */
class InflectorTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Returns test data for pluralize()
	 *
	 * @return array
	 */
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

	/**
	 * Returns test data for testIsSingular()
	 *
	 * @return array
	 */
	public function getTestIsSingular()
	{
		return array(
			array("move", true, "isSingular: Move"),
			array("moves", false, "isSingular: Moves"),
			array("sex", true, "isSingular: Sex"),
			array("sexes", false, "isSingular: Sexes"),
			array("child", true, "isSingular: Child"),
			array("children", false, "isSingular: Children"),
			array("woman", true, "Pluralisation of words in -an not honoured"),
			array("women", false, "Should return the same as it's already a plural (words in -an)"),
			array("foot", true, "isSingular: Foot"),
			array("feet", false, "isSingular: Feet"),
			array("person", true, "isSingular: Person"),
			array("people", false, "isSingular: People"),
			array("taxon", true, "isSingular: Taxon"),
			array("taxa", false, "isSingular: Taxa"),
			array("quiz", true, "isSingular: Quiz"),
			array("quizzes", false, "isSingular: Quizzes"),
			array("ox", true, "isSingular: Ox"),
			array("oxen", false, "isSingular: Oxen"),
			array("mouse", true, "isSingular: Mouse"),
			array("mice", false, "isSingular: Mice"),
			array("matrix", true, "isSingular: Matrix"),
			array("matrices", false, "isSingular: Matrices"),
			array("vertex", true, "isSingular: Vertex"),
			array("vertices", false, "isSingular: Vertices"),
			array("index", true, "isSingular: Index"),
			array("indices", false, "isSingular: Indices"),
			array("suffix", true, "isSingular: Suffix"),
			array("suffices", false, "isSingular: Suffices"),
			array("codex", true, "isSingular: Codex"),
			array("codices", false, "isSingular: Codices"),
			array("onyx", true, "isSingular: onyx"),
			array("onyxes", false, "isSingular: onyxes"),
			array("leech", true, "isSingular: Leech"),
			array("leeches", false, "isSingular: Leeches"),
			array("glass", true, "isSingular: Glass"),
			array("glasses", false, "isSingular: Glasses"),
			array("mesh", true, "isSingular: Mesh"),
			array("meshes", false, "isSingular: Meshes"),
			array("soliloquy", true, "isSingular: Soliloquy"),
			array("soliloquies", false, "isSingular: Soliloquies"),
			array("baby", true, "isSingular: Baby"),
			array("babies", false, "isSingular: Babies"),
			array("elf", true, "isSingular: Elf"),
			array("elves", false, "isSingular: Elves"),
			array("life", true, "isSingular: Life"),
			array("lives", false, "isSingular: Lives"),
			array("antithesis", true, "isSingular: Antitheses"),
			array("antitheses", false, "isSingular: Antitheses"),
			array("consortium", true, "isSingular: consortium"),
			array("consortia", false, "isSingular: consortia"),
			array("addendum", true, "isSingular: addendum"),
			array("addenda", false, "isSingular: addenda"),
			array("alumna", true, "isSingular: alumna"),
			array("alumnae", false, "isSingular: alumnae"),
			array("formula", true, "isSingular: formula"),
			array("formulae", false, "isSingular: formulae"),
			array("buffalo", true, "isSingular: buffalo"),
			array("buffaloes", false, "isSingular: buffaloes"),
			array("tomato", true, "isSingular: tomato"),
			array("tomatoes", false, "isSingular: tomatoes"),
			array("hero", true, "isSingular: hero"),
			array("heroes", false, "isSingular: heroes"),
			array("bus", true, "isSingular: bus"),
			array("buses", false, "isSingular: buses"),
			array("alias", true, "isSingular: alias"),
			array("aliases", false, "isSingular: aliases"),
			array("octopus", true, "isSingular: octopus"),
			array("octopi", false, "isSingular: octopi"),
			array("virus", true, "isSingular: virus"),
			array("viri", false, "isSingular: viri"),
			array("genus", true, "isSingular: genus"),
			array("genera", false, "isSingular: genera"),
			array("axis", true, "isSingular: axis"),
			array("axes", false, "isSingular: axes"),
			array("testis", true, "isSingular: testis"),
			array("testes", false, "isSingular: testes"),

			array("dwarf", true, "isSingular: Dwarf"),
			array("dwarves", false, "isSingular: Dwarves"),
			array("guy", true, "isSingular: Guy"),
			array("guys", false, "isSingular: Guys"),
			array("relief", true, "isSingular: Relief"),
			array("reliefs", false, "isSingular: Reliefs"),

			array("aircraft", true, "isSingular: aircraft (special)"),
			array("cannon", true, "isSingular: cannon (special)"),
			array("deer", true, "isSingular: deer (special)"),
			array("equipment", true, "isSingular: equipment (special)"),
			array("fish", true, "isSingular: Fish (special)"),
			array("information", true, "isSingular: information (special)"),
			array("money", true, "isSingular: money (special)"),
			array("moose", true, "isSingular: moose (special)"),
			array("rice", true, "isSingular: rice (special)"),
			array("series", true, "isSingular: series (special)"),
			array("sheep", true, "isSingular: sheep (special)"),
			array("species", true, "isSingular: species (special)"),
			array("swine", true, "isSingular: swine (special)"),

			array("word", true, 'isSingular: word'),
			array("words", false, "isSingular: words"),

			array("cookie", true, "isSingular: cookie"),
			array("cookies", false, "isSingular: cookies"),
			array("database", true, "isSingular: database"),
			array("databases", false, "isSingular: databases"),
			array("crisis", true, "isSingular: crisis"),
			array("crises", false, "isSingular: crises"),
			array("shoe", true, "isSingular: shoe"),
			array("shoes", false, "isSingular: shoes"),
			array("backhoe", true, "isSingular: backhoe"),
			array("backhoes", false, "isSingular: backhoes"),
			array("movie", true, "isSingular: movie"),
			array("movies", false, "isSingular: movies"),
			array("vie", true, "isSingular: vie"),
			array("vies", false, "isSingular: vies"),
			array("narrative", true, "isSingular: narrative"),
			array("narratives", false, "isSingular: narratives"),
			array("hive", true, "isSingular: hive"),
			array("hives", false, "isSingular: hives"),
			array("analysis", true, "isSingular: analysis"),
			array("analyses", false, "isSingular: analyses"),
			array("basis", true, "isSingular: basis"),
			array("bases", false, "isSingular: bases"),
			array("diagnosis", true, "isSingular: diagnosis"),
			array("diagnoses", false, "isSingular: diagnoses"),
			array("parenthesis", true, "isSingular: parenthesis"),
			array("parentheses", false, "isSingular: parentheses"),
			array("prognosis", true, "isSingular: prognosis"),
			array("prognoses", false, "isSingular: prognoses"),
			array("synopsis", true, "isSingular: synopsis"),
			array("synopses", false, "isSingular: synopses"),
			array("thesis", true, "isSingular: thesis"),
			array("theses", false, "isSingular: theses"),
			array("news", true, "isSingular: news"),
		);
	}

	/**
	 * Returns test data for testIsPlural()
	 *
	 * @return array
	 */
	public function getTestIsPlural()
	{
		$temp = $this->getTestIsSingular();
		$ret = array();

		foreach ($temp as $items)
		{
			$items[1] = !$items[1];
			$items[2] = str_replace('isSingular:', 'isPlural:', $items[2]);
			$ret[] = $items;
		}

		return $ret;
	}

	/**
	 * Returns test data for singularize()
	 *
	 * @return array
	 */
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

	/**
	 * Returns test data for camelize()
	 *
	 * @return array
	 */
	public function getTestCamelizeData()
	{
		return array(
			array("foo_bar", "FooBar", 'Underscores must act as camelization points'),
			array("foo bar", "FooBar", 'Spaces must act as camelization points'),
			array("foo's bar", "FooSBar", 'Punctuation must be stripped out'),
			array("foo.bar.123", "FooBar123", 'Numbers must be preserved'),
		);
	}

	/**
	 * Returns test data for underscore()
	 *
	 * @return array
	 */
	public function getTestUnderscoreData()
	{
		return array(
			array("foo bar", "foo_bar", 'Spaces must act as underscore points'),
			array("FooBar", "foo_bar", 'CamelCase must be converted'),
		);
	}

	/**
	 * Returns test data for explode()
	 *
	 * @return array
	 */
	public function getTestExplodeData()
	{
		return array(
			array("foo bar", array('foo', 'bar'), 'Spaces must act as underscore points'),
			array("FooBar", array('foo', 'bar'), 'CamelCase must be converted'),
		);
	}

	/**
	 * Returns test data for implode()
	 *
	 * @return array
	 */
	public function getTestImplodeData()
	{
		return array(
			array(array('foo', 'bar'), "FooBar", 'Implosion failed'),
		);
	}

	/**
	 * Returns test data for humanize()
	 *
	 * @return array
	 */
	public function getTestHumanizeData()
	{
		return array(
			array("foo_bar", 'Foo Bar', 'Humanize failed'),
			array("this_is_a_test", 'This Is A Test', 'Humanize failed'),
		);
	}

	/**
	 * Returns test data for tableize()
	 *
	 * @return array
	 */
	public function getTestTableizeData()
	{
		return array(
			array("person", 'people', 'Pluralise words'),
			array("people", 'people', 'Retain plural forms'),
			array("SomeGoodPerson", 'some_good_people', 'Pluralise camelcase words'),
		);
	}

	/**
	 * Returns test data for classify()
	 *
	 * @return array
	 */
	public function getTestClassifyData()
	{
		return array(
			array("people", 'Person', 'Singularize words'),
			array("person", 'Person', 'Retain singular forms'),
			array("SomeGoodPeople", 'Somegoodperson', 'Singularize camelcase words'),
		);
	}

	/**
	 * Returns test data for variableize()
	 *
	 * @return array
	 */
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
	 * Test deleteCache method
	 *
	 * @covers Awf\Inflector\Inflector::deleteCache
	 * @uses Tests\Helpers\ReflectionHelper::setValue
	 * @uses Tests\Helpers\ReflectionHelper::getValue
	 *
	 * @return  void
	 */
	public function testDeleteCache()
	{
		$myCache = array(
			'singularized' => array('foobar' => 'foobars'),
			'pluralized'   => array('foobars' => 'foobar'),
		);
		ReflectionHelper::setValue('\\Awf\\Inflector\\Inflector', '_cache', $myCache);

		Inflector::deleteCache();

		$newCache = ReflectionHelper::getValue('\\Awf\\Inflector\\Inflector', '_cache');

		$this->assertEmpty(
			$newCache['singularized'],
			'Line: ' . __LINE__ . '.'
		);

		$this->assertEmpty(
			$newCache['pluralized'],
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * Test addWord method
	 *
	 * @covers Awf\Inflector\Inflector::addWord
	 * @uses Awf\Inflector\Inflector::singularize
	 * @uses Awf\Inflector\Inflector::pluralize
	 *
	 * @return  void
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
	 * @covers Awf\Inflector\Inflector::pluralize
	 * @uses Awf\Inflector\Inflector::deleteCache
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
	 * @covers Awf\Inflector\Inflector::singularize
	 * @uses Awf\Inflector\Inflector::deleteCache
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
	 * @covers Awf\Inflector\Inflector::camelize
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
	 * @covers Awf\Inflector\Inflector::underscore
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
	 * @covers Awf\Inflector\Inflector::explode
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
	 * @covers Awf\Inflector\Inflector::implode
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
	 * @covers Awf\Inflector\Inflector::humanize
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
	 * @covers Awf\Inflector\Inflector::tableize
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
	 * @covers Awf\Inflector\Inflector::classify
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
	 * @covers Awf\Inflector\Inflector::variablize
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

	/**
	 * Test isSingular method
	 *
	 * @covers Awf\Inflector\Inflector::isSingular
	 * @uses Awf\Inflector\Inflector::deleteCache
	 *
	 * @dataProvider getTestIsSingular
	 */
	public function testIsSingular($word, $expect, $message)
	{
		Inflector::deleteCache();

		$res = Inflector::isSingular($word);
		$this->assertEquals(
			$res,
			$expect,
			$message
		);
	}

	/**
	 * Test isPlural method
	 *
	 * @covers Awf\Inflector\Inflector::isPlural
	 * @uses Awf\Inflector\Inflector::deleteCache
	 *
	 * @dataProvider getTestIsPlural
	 */
	public function testIsPlural($word, $expect, $message)
	{
		Inflector::deleteCache();

		$res = Inflector::isPlural($word);
		$this->assertEquals(
			$res,
			$expect,
			$message
		);
	}
}
 