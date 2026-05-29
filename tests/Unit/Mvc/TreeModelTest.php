<?php

declare(strict_types=1);

/**
 * Concrete TreeModel subclass that lives under a "Model\" sub-namespace
 * so that DataModel::getName() can correctly derive the model name.
 */

namespace Awf\Tests\Unit\Mvc\TreeModel\Model;

use Awf\Mvc\TreeModel;

/**
 * Minimal TreeModel fixture backed by the "tree_nodes" table:
 *   id    INTEGER PRIMARY KEY AUTOINCREMENT
 *   title TEXT NOT NULL DEFAULT ''
 *   lft   INTEGER NOT NULL DEFAULT 0
 *   rgt   INTEGER NOT NULL DEFAULT 0
 */
class Node extends TreeModel
{
    /** Reset the protected static caches so each test starts clean. */
    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

// ============================================================
// Test class
// ============================================================

namespace Awf\Tests\Unit\Mvc\TreeModel;

use Awf\Container\Container;
use Awf\Database\Driver\Sqlite as SqliteDriver;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Session\Segment;
use Awf\Text\Language;
use Awf\User\ManagerInterface as UserManagerInterface;
use Awf\User\UserInterface;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for TreeModel nested-set operations against in-memory SQLite.
 *
 * Covers: insertAsRoot, insertAsFirstChildOf, insertAsLastChildOf, insertLeftOf,
 * insertRightOf, moveLeft, moveRight, moveToLeftOf, moveToRightOf, makeFirstChildOf,
 * makeLastChildOf, makeRoot, forceDelete, getRoot, getParent, getLevel,
 * getDescendants, isLeaf, isRoot, isDescendantOf, isSelfOrDescendantOf,
 * insideSubtree, equals, inSameScope, withoutNode, getNestedList,
 * reorder/move exception, and lft/rgt integrity invariants.
 */
class TreeModelTest extends TestCase
{
    private SqliteDriver $db;
    private Container $container;

    // -------------------------------------------------------------------------
    // Infrastructure
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();

        if (!SqliteDriver::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        \Awf\Tests\Unit\Mvc\TreeModel\Model\Node::flushCaches();

        $this->db = new SqliteDriver([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $this->db->connect();

        $this->db->setQuery(
            'CREATE TABLE tree_nodes (
                id    INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT    NOT NULL DEFAULT \'\',
                lft   INTEGER NOT NULL DEFAULT 0,
                rgt   INTEGER NOT NULL DEFAULT 0
            )'
        )->execute();

        $tmpDir = sys_get_temp_dir();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);

        $input = new Input([]);

        $segment = $this->createMock(Segment::class);
        $segment->method('get')->willReturn(0);
        $segment->method('__get')->willReturn(null);

        $application = $this->createMock(\Awf\Application\Application::class);
        $application->method('getName')->willReturn('Testapp');

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn(0);

        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager->method('getUser')->willReturn($user);

        $db = $this->db;

        $this->container = new Container([
            'application_name'     => 'Testapp',
            'applicationNamespace' => '\\Testapp',
            'session_segment_name' => 'testapp_seg',
            'basePath'             => $tmpDir,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $tmpDir,
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $tmpDir,
            'eventDispatcher'      => $ed,
            'language'             => $language,
            'input'                => $input,
            'application'          => $application,
            'segment'              => $segment,
            'userManager'          => $userManager,
            'db'                   => $db,
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->disconnect();
    }

    /** Return a freshly instantiated Node model. */
    private function makeModel(): \Awf\Tests\Unit\Mvc\TreeModel\Model\Node
    {
        $this->container['mvc_config'] = [
            'tableName'   => 'tree_nodes',
            'idFieldName' => 'id',
            'autoChecks'  => false,
        ];

        return new \Awf\Tests\Unit\Mvc\TreeModel\Model\Node($this->container);
    }

    /**
     * Assert that every row's lft < rgt and that the nested-set invariants hold:
     * - All values are positive
     * - No two nodes share the same lft or rgt
     * - The set of all lft+rgt values is contiguous from 1..2N
     */
    private function assertTreeIntegrity(): void
    {
        $rows = $this->db->setQuery('SELECT lft, rgt FROM tree_nodes ORDER BY lft ASC')->loadAssocList();

        self::assertNotEmpty($rows, 'Tree must not be empty for integrity check.');

        $allValues = [];

        foreach ($rows as $row) {
            $lft = (int) $row['lft'];
            $rgt = (int) $row['rgt'];

            self::assertGreaterThan(0, $lft, 'lft must be positive');
            self::assertGreaterThan(0, $rgt, 'rgt must be positive');
            self::assertLessThan($rgt, $lft, 'lft must be less than rgt');

            self::assertNotContains($lft, $allValues, "Duplicate lft value $lft");
            self::assertNotContains($rgt, $allValues, "Duplicate rgt value $rgt");

            $allValues[] = $lft;
            $allValues[] = $rgt;
        }

        $n = count($rows);
        sort($allValues);

        // Values should be contiguous: 1, 2, 3, ..., 2*N
        $expected = range(1, 2 * $n);
        self::assertSame($expected, $allValues, 'lft/rgt values must form a contiguous range 1..2N');
    }

    // -------------------------------------------------------------------------
    // Constructor: requires lft/rgt columns
    // -------------------------------------------------------------------------

    public function testConstructorThrowsWhenTableHasNoLftRgtColumns(): void
    {
        $this->db->setQuery(
            'CREATE TABLE no_lft_rgt (id INTEGER PRIMARY KEY, title TEXT)'
        )->execute();

        $this->container['mvc_config'] = [
            'tableName'   => 'no_lft_rgt',
            'idFieldName' => 'id',
            'autoChecks'  => false,
        ];

        $this->expectException(\RuntimeException::class);

        new \Awf\Tests\Unit\Mvc\TreeModel\Model\Node($this->container);
    }

    // -------------------------------------------------------------------------
    // insertAsRoot
    // -------------------------------------------------------------------------

    public function testInsertAsRootCreatesRootNodeWithLft1Rgt2(): void
    {
        $node = $this->makeModel();
        $node->title = 'Root';
        $node->insertAsRoot();

        self::assertSame(1, (int) $node->lft);
        self::assertSame(2, (int) $node->rgt);

        $this->assertTreeIntegrity();
    }

    public function testInsertAsRootOnExistingNodeThrows(): void
    {
        $node = $this->makeModel();
        $node->title = 'Root';
        $node->insertAsRoot();

        $this->expectException(\RuntimeException::class);
        $node->insertAsRoot();
    }

    public function testInsertSecondRootGetsLft3Rgt4(): void
    {
        $root1 = $this->makeModel();
        $root1->title = 'Root1';
        $root1->insertAsRoot();

        $root2 = $this->makeModel();
        $root2->title = 'Root2';
        $root2->insertAsRoot();

        self::assertSame(3, (int) $root2->lft);
        self::assertSame(4, (int) $root2->rgt);

        $this->assertTreeIntegrity();
    }

    // -------------------------------------------------------------------------
    // insertAsFirstChildOf / insertAsLastChildOf
    // -------------------------------------------------------------------------

    public function testInsertAsFirstChildOfAddsChildImmediatelyAfterParentLft(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsFirstChildOf($root);

        self::assertSame((int)$root->lft + 1, (int)$child->lft, 'First child lft = parent lft + 1');
        self::assertSame((int)$root->lft + 2, (int)$child->rgt, 'First child rgt = parent lft + 2');

        $this->assertTreeIntegrity();
    }

    public function testInsertAsLastChildOfAddsChildBeforeParentRgt(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        self::assertSame((int)$root->rgt - 2, (int)$child->lft, 'Last child lft = parent rgt - 2');
        self::assertSame((int)$root->rgt - 1, (int)$child->rgt, 'Last child rgt = parent rgt - 1');

        $this->assertTreeIntegrity();
    }

    public function testInsertTwoChildrenKeepsIntegrity(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child1 = $this->makeModel();
        $child1->title = 'ChildA';
        $root->find($root->getId());
        $child1->insertAsLastChildOf($root);

        $child2 = $this->makeModel();
        $child2->title = 'ChildB';
        $root->find($root->getId());
        $child2->insertAsLastChildOf($root);

        $this->assertTreeIntegrity();

        // child1 should be left of child2
        self::assertLessThan((int)$child2->lft, (int)$child1->lft);
    }

    public function testInsertAsFirstChildOfInvalidParentThrows(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        // Manually corrupt the parent node's position values
        $fakeParent = $this->makeModel();
        $fakeParent->lft = 5;
        $fakeParent->rgt = 3; // rgt < lft — invalid

        $child = $this->makeModel();
        $child->title = 'Child';

        $this->expectException(\RuntimeException::class);
        $child->insertAsFirstChildOf($fakeParent);
    }

    public function testInsertAsLastChildOfInvalidParentThrows(): void
    {
        $fakeParent = $this->makeModel();
        $fakeParent->lft = 5;
        $fakeParent->rgt = 3;

        $child = $this->makeModel();

        $this->expectException(\RuntimeException::class);
        $child->insertAsLastChildOf($fakeParent);
    }

    // -------------------------------------------------------------------------
    // insertLeftOf / insertRightOf
    // -------------------------------------------------------------------------

    public function testInsertLeftOfPlacesNodeBeforeSibling(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $sibling = $this->makeModel();
        $sibling->title = 'Sibling';
        $sibling->insertAsLastChildOf($root);

        $newNode = $this->makeModel();
        $newNode->title = 'LeftOf';
        $newNode->insertLeftOf($sibling);

        $this->assertTreeIntegrity();

        // newNode must be to the left of sibling
        self::assertLessThan((int)$sibling->lft, (int)$newNode->lft);
    }

    public function testInsertRightOfPlacesNodeAfterSibling(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $sibling = $this->makeModel();
        $sibling->title = 'Sibling';
        $sibling->insertAsLastChildOf($root);

        $newNode = $this->makeModel();
        $newNode->title = 'RightOf';
        $newNode->insertRightOf($sibling);

        $this->assertTreeIntegrity();

        // newNode must be to the right of sibling
        self::assertGreaterThan((int)$sibling->rgt, (int)$newNode->lft);
    }

    public function testInsertLeftOfInvalidSiblingThrows(): void
    {
        $fakeSibling = $this->makeModel();
        $fakeSibling->lft = 10;
        $fakeSibling->rgt = 5;

        $node = $this->makeModel();

        $this->expectException(\RuntimeException::class);
        $node->insertLeftOf($fakeSibling);
    }

    public function testInsertRightOfInvalidSiblingThrows(): void
    {
        $fakeSibling = $this->makeModel();
        $fakeSibling->lft = 10;
        $fakeSibling->rgt = 5;

        $node = $this->makeModel();

        $this->expectException(\RuntimeException::class);
        $node->insertRightOf($fakeSibling);
    }

    // -------------------------------------------------------------------------
    // isRoot / isLeaf / isChild
    // -------------------------------------------------------------------------

    public function testRootNodeIsDetectedAsRoot(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        self::assertTrue($root->isRoot());
        self::assertFalse($root->isChild());
    }

    public function testLeafNodeIsDetected(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        // The child is a leaf (no children)
        self::assertTrue($child->isLeaf());
        // Root is not a leaf (has one child)
        self::assertFalse($root->isLeaf());
    }

    public function testIsLeafWithInvalidPositionThrows(): void
    {
        $node = $this->makeModel();
        $node->lft = 5;
        $node->rgt = 3;

        $this->expectException(\RuntimeException::class);
        $node->isLeaf();
    }

    // -------------------------------------------------------------------------
    // getLevel
    // -------------------------------------------------------------------------

    public function testRootLevelIsZero(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        self::assertSame('0', (string)$root->getLevel());
    }

    public function testChildLevelIsOne(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        self::assertSame('1', (string)$child->getLevel());
    }

    public function testGrandchildLevelIsTwo(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        $grandchild = $this->makeModel();
        $grandchild->title = 'Grandchild';
        $grandchild->insertAsLastChildOf($child);

        self::assertSame('2', (string)$grandchild->getLevel());
    }

    public function testGetLevelWithInvalidPositionThrows(): void
    {
        $node = $this->makeModel();
        $node->lft = 5;
        $node->rgt = 3;

        $this->expectException(\RuntimeException::class);
        $node->getLevel();
    }

    // -------------------------------------------------------------------------
    // getParent
    // -------------------------------------------------------------------------

    public function testGetParentReturnsParentNode(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        $parent = $child->getParent();

        self::assertSame((int)$root->lft, (int)$parent->lft);
    }

    public function testGetParentOnRootReturnsSelf(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $parent = $root->getParent();

        self::assertSame((int)$root->lft, (int)$parent->lft);
    }

    public function testGetParentWithInvalidPositionThrows(): void
    {
        $node = $this->makeModel();
        $node->lft = 5;
        $node->rgt = 3;

        $this->expectException(\RuntimeException::class);
        $node->getParent();
    }

    // -------------------------------------------------------------------------
    // getRoot
    // -------------------------------------------------------------------------

    public function testGetRootReturnsRootNode(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        $grandchild = $this->makeModel();
        $grandchild->title = 'Grandchild';
        $grandchild->insertAsLastChildOf($child);

        $foundRoot = $grandchild->getRoot();

        self::assertSame((int)$root->lft, (int)$foundRoot->lft);
    }

    public function testGetRootOnRootReturnsSelf(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $foundRoot = $root->getRoot();

        self::assertSame((int)$root->lft, (int)$foundRoot->lft);
    }

    public function testGetRootWithInvalidPositionThrows(): void
    {
        $node = $this->makeModel();
        $node->lft = 5;
        $node->rgt = 3;

        $this->expectException(\RuntimeException::class);
        $node->getRoot();
    }

    // -------------------------------------------------------------------------
    // isDescendantOf / isSelfOrDescendantOf / insideSubtree
    // -------------------------------------------------------------------------

    public function testIsDescendantOfReturnsTrueForChild(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        self::assertTrue($child->isDescendantOf($root));
        self::assertFalse($root->isDescendantOf($child));
    }

    public function testIsDescendantOfWithInvalidPositionThrows(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $node = $this->makeModel();
        $node->lft = 5;
        $node->rgt = 3;

        $this->expectException(\RuntimeException::class);
        $node->isDescendantOf($root);
    }

    public function testIsSelfOrDescendantOfReturnsTrueForSelf(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        self::assertTrue($root->isSelfOrDescendantOf($root));
    }

    public function testInsideSubtreeDetectsCorrectly(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        self::assertTrue($child->insideSubtree($root));
        self::assertFalse($root->insideSubtree($child));
    }

    public function testInsideSubtreeWithInvalidCurrentNodeThrows(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $node = $this->makeModel();
        $node->lft = 5;
        $node->rgt = 3;

        $this->expectException(\RuntimeException::class);
        $node->insideSubtree($root);
    }

    // -------------------------------------------------------------------------
    // equals
    // -------------------------------------------------------------------------

    public function testEqualsReturnsTrueForSameNode(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        // Load a fresh copy
        $copy = $this->makeModel();
        $copy->find($root->getId());

        self::assertTrue($root->equals($copy));
    }

    public function testEqualsReturnsFalseForDifferentNodes(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        self::assertFalse($root->equals($child));
    }

    // -------------------------------------------------------------------------
    // inSameScope
    // -------------------------------------------------------------------------

    public function testInSameScopeLeafWithLeafReturnsTrue(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child1 = $this->makeModel();
        $child1->title = 'Child1';
        $child1->insertAsLastChildOf($root);

        $child2 = $this->makeModel();
        $child2->title = 'Child2';
        $child2->insertAsLastChildOf($root);

        self::assertTrue($child1->inSameScope($child2));
    }

    public function testInSameScopeRootWithNonRootReturnsFalse(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        self::assertFalse($root->inSameScope($child));
    }

    // -------------------------------------------------------------------------
    // getDescendants
    // -------------------------------------------------------------------------

    public function testGetDescendantsReturnsAllDescendants(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child1 = $this->makeModel();
        $child1->title = 'Child1';
        $child1->insertAsLastChildOf($root);

        // Reload root before inserting child2 (lft/rgt changed after child1 insertion)
        $root->find($root->getId());

        $child2 = $this->makeModel();
        $child2->title = 'Child2';
        $child2->insertAsLastChildOf($root);

        // Reload child1 before inserting grandchild (lft/rgt may have changed)
        $child1->find($child1->getId());

        $grandchild = $this->makeModel();
        $grandchild->title = 'Grandchild';
        $grandchild->insertAsLastChildOf($child1);

        // Re-load root so its lft/rgt are fresh
        $root->find($root->getId());
        $descendants = $root->getDescendants();

        self::assertCount(3, $descendants, 'Root should have 3 descendants');

        // getDescendants() returns a Collection of DataModel objects (not arrays)
        $titles = [];
        foreach ($descendants as $item) {
            $titles[] = $item->title;
        }
        self::assertContains('Child1', $titles);
        self::assertContains('Child2', $titles);
        self::assertContains('Grandchild', $titles);
    }

    public function testGetDescendantsOnLeafReturnsEmpty(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        // Re-load child so its lft/rgt are fresh
        $child->find($child->getId());

        $descendants = $child->getDescendants();
        self::assertCount(0, $descendants);
    }

    // -------------------------------------------------------------------------
    // forceDelete (subtree deletion)
    // -------------------------------------------------------------------------

    public function testForceDeleteLeafNodeUpdatesTree(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        $childId = $child->getId();

        // Re-load child so its lft/rgt are fresh
        $child->find($childId);
        $child->forceDelete($childId);

        // Root should now be a leaf
        $root->find($root->getId());
        self::assertTrue($root->isLeaf());

        $this->assertTreeIntegrity();
    }

    public function testForceDeleteSubtreeRemovesAllDescendants(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        $grandchild = $this->makeModel();
        $grandchild->title = 'Grandchild';
        $grandchild->insertAsLastChildOf($child);

        $childId = $child->getId();

        // Re-load child so its lft/rgt are fresh
        $child->find($childId);
        $child->forceDelete($childId);

        // Only root should remain
        $count = (int) $this->db->setQuery('SELECT COUNT(*) FROM tree_nodes')->loadResult();
        self::assertSame(1, $count, 'Only root should remain after subtree deletion');

        $this->assertTreeIntegrity();
    }

    public function testForceDeleteWithNullPkThrows(): void
    {
        $node = $this->makeModel();

        $this->expectException(\UnexpectedValueException::class);
        $node->forceDelete(null);
    }

    // -------------------------------------------------------------------------
    // moveLeft / moveRight
    // -------------------------------------------------------------------------

    public function testMoveLeftSwapsSiblings(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child1 = $this->makeModel();
        $child1->title = 'Child1';
        $child1->insertAsLastChildOf($root);

        $child2 = $this->makeModel();
        $child2->title = 'Child2';
        $child2->insertAsLastChildOf($root);

        // Re-load child2
        $child2->find($child2->getId());

        $lftBefore = (int)$child2->lft;
        $child2->moveLeft();
        $child2->find($child2->getId());

        self::assertLessThan($lftBefore, (int)$child2->lft, 'After moveLeft, child2 should have smaller lft');

        $this->assertTreeIntegrity();
    }

    public function testMoveRightSwapsSiblings(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child1 = $this->makeModel();
        $child1->title = 'Child1';
        $child1->insertAsLastChildOf($root);

        $child2 = $this->makeModel();
        $child2->title = 'Child2';
        $child2->insertAsLastChildOf($root);

        // Re-load child1
        $child1->find($child1->getId());

        $lftBefore = (int)$child1->lft;
        $child1->moveRight();
        $child1->find($child1->getId());

        self::assertGreaterThan($lftBefore, (int)$child1->lft, 'After moveRight, child1 should have larger lft');

        $this->assertTreeIntegrity();
    }

    public function testMoveLeftOnLeftmostNodeDoesNothing(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        // Re-load child (it's the only child, so it's the leftmost)
        $child->find($child->getId());
        $lftBefore = (int)$child->lft;

        $child->moveLeft();

        self::assertSame($lftBefore, (int)$child->lft, 'moveLeft on leftmost node must not move it');
    }

    public function testMoveRightOnRightmostNodeDoesNothing(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        $child->find($child->getId());
        $rgtBefore = (int)$child->rgt;

        $child->moveRight();

        self::assertSame($rgtBefore, (int)$child->rgt, 'moveRight on rightmost node must not move it');
    }

    public function testMoveLeftOnRootDoesNothing(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $lftBefore = (int)$root->lft;
        $root->moveLeft();

        self::assertSame($lftBefore, (int)$root->lft, 'moveLeft on root must not move it');
    }

    public function testMoveRightOnRootDoesNothing(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $rgtBefore = (int)$root->rgt;
        $root->moveRight();

        self::assertSame($rgtBefore, (int)$root->rgt, 'moveRight on root must not move it');
    }

    public function testMoveLeftWithInvalidPositionThrows(): void
    {
        $node = $this->makeModel();
        $node->lft = 5;
        $node->rgt = 3;

        $this->expectException(\RuntimeException::class);
        $node->moveLeft();
    }

    public function testMoveRightWithInvalidPositionThrows(): void
    {
        $node = $this->makeModel();
        $node->lft = 5;
        $node->rgt = 3;

        $this->expectException(\RuntimeException::class);
        $node->moveRight();
    }

    // -------------------------------------------------------------------------
    // moveToLeftOf / moveToRightOf / makeFirstChildOf / makeLastChildOf
    // -------------------------------------------------------------------------

    public function testMoveToLeftOfPreservesIntegrity(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child1 = $this->makeModel();
        $child1->title = 'Child1';
        $child1->insertAsLastChildOf($root);

        $child2 = $this->makeModel();
        $child2->title = 'Child2';
        $child2->insertAsLastChildOf($root);

        $child2->find($child2->getId());
        $child1->find($child1->getId());

        // Move child2 to the left of child1
        $child2->moveToLeftOf($child1);

        $this->assertTreeIntegrity();
    }

    public function testMoveToRightOfPreservesIntegrity(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child1 = $this->makeModel();
        $child1->title = 'Child1';
        $child1->insertAsLastChildOf($root);

        $child2 = $this->makeModel();
        $child2->title = 'Child2';
        $child2->insertAsLastChildOf($root);

        $child1->find($child1->getId());
        $child2->find($child2->getId());

        // Move child1 to the right of child2
        $child1->moveToRightOf($child2);

        $this->assertTreeIntegrity();
    }

    public function testMakeFirstChildOfMovesSubtree(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child1 = $this->makeModel();
        $child1->title = 'Child1';
        $child1->insertAsLastChildOf($root);

        $child2 = $this->makeModel();
        $child2->title = 'Child2';
        $child2->insertAsLastChildOf($root);

        // Move child2 to be the first child of root (before child1)
        $child2->find($child2->getId());
        $root->find($root->getId());
        $child2->makeFirstChildOf($root);

        $this->assertTreeIntegrity();

        // child2 should now be at a position before child1
        $child2->find($child2->getId());
        $child1->find($child1->getId());
        self::assertLessThan((int)$child1->lft, (int)$child2->lft, 'child2 should now be before child1');
    }

    public function testMakeLastChildOfMovesSubtree(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child1 = $this->makeModel();
        $child1->title = 'Child1';
        $child1->insertAsLastChildOf($root);

        $child2 = $this->makeModel();
        $child2->title = 'Child2';
        $child2->insertAsLastChildOf($root);

        // Move child1 to be the last child of root (after child2)
        $child1->find($child1->getId());
        $root->find($root->getId());
        $child1->makeLastChildOf($root);

        $this->assertTreeIntegrity();

        // child1 should now be at a position after child2
        $child1->find($child1->getId());
        $child2->find($child2->getId());
        self::assertGreaterThan((int)$child2->lft, (int)$child1->lft, 'child1 should now be after child2');
    }

    public function testMoveToLeftOfWithInvalidCurrentNodeThrows(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $node = $this->makeModel();
        $node->lft = 5;
        $node->rgt = 3;

        $this->expectException(\RuntimeException::class);
        $node->moveToLeftOf($root);
    }

    public function testMoveToRightOfWithInvalidSiblingThrows(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $fakeSibling = $this->makeModel();
        $fakeSibling->lft = 10;
        $fakeSibling->rgt = 5;

        $root->find($root->getId());

        $this->expectException(\RuntimeException::class);
        $root->moveToRightOf($fakeSibling);
    }

    public function testMakeFirstChildOfWithInvalidCurrentNodeThrows(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $node = $this->makeModel();
        $node->lft = 5;
        $node->rgt = 3;

        $this->expectException(\RuntimeException::class);
        $node->makeFirstChildOf($root);
    }

    public function testMakeLastChildOfWithInvalidCurrentNodeThrows(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $node = $this->makeModel();
        $node->lft = 5;
        $node->rgt = 3;

        $this->expectException(\RuntimeException::class);
        $node->makeLastChildOf($root);
    }

    // -------------------------------------------------------------------------
    // makeRoot
    // -------------------------------------------------------------------------

    public function testMakeRootOnRootDoesNothing(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $lftBefore = (int)$root->lft;
        $root->makeRoot();

        self::assertSame($lftBefore, (int)$root->lft);
    }

    public function testMakeRootElevatesChildToTopLevel(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        $child->find($child->getId());
        $child->makeRoot();

        $this->assertTreeIntegrity();

        // After makeRoot, the child should be a root (depth 0)
        $child->find($child->getId());
        self::assertSame('0', (string)$child->getLevel());
    }

    // -------------------------------------------------------------------------
    // reorder() / move() must throw
    // -------------------------------------------------------------------------

    public function testReorderThrowsRuntimeException(): void
    {
        $node = $this->makeModel();

        $this->expectException(\RuntimeException::class);
        $node->reorder();
    }

    public function testMoveThrowsRuntimeException(): void
    {
        $node = $this->makeModel();

        $this->expectException(\RuntimeException::class);
        $node->move(1);
    }

    // -------------------------------------------------------------------------
    // create() / copy()
    // -------------------------------------------------------------------------

    public function testCreateInsertAsChildOfParent(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $root->find($root->getId());
        $newNode = $root->create(['title' => 'NewChild']);

        $this->assertTreeIntegrity();

        self::assertNotNull($newNode->getId());
    }

    public function testCopyCreatesANewRecord(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        $child->find($child->getId());
        $copy = $child->copy();

        $this->assertTreeIntegrity();

        self::assertNotSame($child->getId(), $copy->getId(), 'Copy must produce a different ID');
        self::assertSame('Child', $copy->title);
    }

    // -------------------------------------------------------------------------
    // withoutNode
    // -------------------------------------------------------------------------

    public function testWithoutNodeExcludesNodeFromGet(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child1 = $this->makeModel();
        $child1->title = 'Child1';
        $child1->insertAsLastChildOf($root);

        $child2 = $this->makeModel();
        $child2->title = 'Child2';
        $child2->insertAsLastChildOf($root);

        // Get all nodes but exclude child1
        $root->find($root->getId());
        $all = $root->getDescendantsAndSelf();

        // withoutNode must reduce the result
        $root->find($root->getId());
        $child1->find($child1->getId());
        $root->withoutNode($child1);
        $filtered = $root->getDescendantsAndSelf();

        self::assertCount(count($all) - 1, $filtered, 'withoutNode must exclude one node');
    }

    // -------------------------------------------------------------------------
    // getNestedList
    // -------------------------------------------------------------------------

    public function testGetNestedListReturnsCorrectStructure(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeModel();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        $list = $root->getNestedList('title');

        self::assertIsArray($list);
        self::assertCount(2, $list, 'Nested list must have 2 entries (root + child)');

        // Root is at depth 0: no prefix
        // Child is at depth 1: two-space prefix
        $values = array_values($list);
        self::assertSame('Root', $values[0]);
        self::assertStringStartsWith('  ', $values[1], 'Child entry must be indented by 2 spaces');
    }

    // -------------------------------------------------------------------------
    // lft/rgt integrity after complex mutations
    // -------------------------------------------------------------------------

    public function testComplexTreeBuildMaintainsIntegrity(): void
    {
        // Build a tree:
        //   Root
        //   ├── A
        //   │   ├── A1
        //   │   └── A2
        //   └── B
        //       └── B1
        //
        // NOTE: Each insertAsLastChildOf mutates the DB, so parent nodes
        // must be reloaded from the DB before further insertions.

        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $a = $this->makeModel();
        $a->title = 'A';
        $root->find($root->getId());
        $a->insertAsLastChildOf($root);

        $b = $this->makeModel();
        $b->title = 'B';
        $root->find($root->getId());
        $b->insertAsLastChildOf($root);

        $a1 = $this->makeModel();
        $a1->title = 'A1';
        $a->find($a->getId());
        $a1->insertAsLastChildOf($a);

        $a2 = $this->makeModel();
        $a2->title = 'A2';
        $a->find($a->getId());
        $a2->insertAsLastChildOf($a);

        $b1 = $this->makeModel();
        $b1->title = 'B1';
        $b->find($b->getId());
        $b1->insertAsLastChildOf($b);

        $this->assertTreeIntegrity();

        // Expected lft/rgt for 6 nodes: 1..12
        $root->find($root->getId());
        self::assertSame(1, (int)$root->lft);
        self::assertSame(12, (int)$root->rgt);
    }

    public function testDeleteAndRebuildMaintainsIntegrity(): void
    {
        $root = $this->makeModel();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child1 = $this->makeModel();
        $child1->title = 'Child1';
        $child1->insertAsLastChildOf($root);

        $child2 = $this->makeModel();
        $child2->title = 'Child2';
        $child2->insertAsLastChildOf($root);

        $child3 = $this->makeModel();
        $child3->title = 'Child3';
        $child3->insertAsLastChildOf($root);

        // Delete the middle child
        $child2->find($child2->getId());
        $child2->forceDelete($child2->getId());

        $this->assertTreeIntegrity();

        // Verify we have 3 nodes remaining
        $count = (int)$this->db->setQuery('SELECT COUNT(*) FROM tree_nodes')->loadResult();
        self::assertSame(3, $count);
    }
}
