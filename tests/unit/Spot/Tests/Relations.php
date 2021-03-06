<?php
/**
 * @package Spot
 */

namespace Spot\Tests;

class Relations extends SpotTestCase
{
	protected $backupGlobals = false;

	public static function setupBeforeClass()
	{
		$mapper = test_spot_mapper();
		$mapper->migrate('\Spot\Entity\Post');
		$mapper->migrate('\Spot\Entity\Post\Comment');
	}

	public static function tearDownAfterClass()
	{
		$mapper = test_spot_mapper();
		$mapper->truncateDatasource('\Spot\Entity\Post');
		$mapper->truncateDatasource('\Spot\Entity\Post\Comment');
	}

	public function testBlogPostInsert()
	{
		$mapper = test_spot_mapper();
		$post = $mapper->get('\Spot\Entity\Post');
		$post->title = "My Awesome Blog Post";
		$post->body = "<p>This is a really awesome super-duper post.</p><p>It's testing the relationship functions.</p>";
		$post->date_created = $mapper->connection('\Spot\Entity\Post')->dateTime();
		$postId = $mapper->insert($post);

		$this->assertTrue($postId !== false);

		// Test selcting it to ensure it exists
		$postx = $mapper->get('\Spot\Entity\Post', $postId);
		$this->assertTrue($postx instanceof \Spot\Entity\Post);

		return $postId;
	}

	/**
	 * @depends testBlogPostInsert
	 */
	public function testBlogCommentsRelationInsertByObject($postId = 1)
	{
		$mapper = test_spot_mapper();
		$post = $mapper->get('\Spot\Entity\Post', $postId);

		// Array will usually come from POST/JSON data or other source
		$commentSaved = false;
		$comment = $mapper->get('\Spot\Entity\Post\Comment');
		$mapper->data($comment, array(
			'post_id' => $postId,
			'name' => 'Testy McTester',
			'email' => 'test@test.com',
			'body' => 'This is a test comment. Yay!',
			'date_created' => new \DateTime()
		));
		try {
			$commentSaved = $mapper->save($comment);
			if (!$commentSaved) {
				print_r($comment->errors());
				$this->fail("Comment NOT saved");
			}
		} catch(Exception $e) {
			echo __FUNCTION__ . ": " . $e->getMessage() . "\n";
			/*
			echo $e->getTraceAsString();
			$commentMapper->debug();
			exit();
			*/
		}
		$this->assertTrue(false !== $commentSaved);
	}

	/**
	 * @depends testBlogPostInsert
	 */
	public function testBlogCommentsCanIterateEntity($postId = 1)
	{
		$mapper = test_spot_mapper();
		$post = $mapper->get('\Spot\Entity\Post', $postId);

		foreach($post->comments as $comment) {
			$this->assertTrue($comment instanceOf \Spot\Entity\Post\Comment);
		}
	}

	public function testHasManyRelationCountZero()
	{
		$mapper = test_spot_mapper();
		$post = $mapper->get('\Spot\Entity\Post');
		$post->title = "No Comments";
		$post->body = "<p>Comments relation test</p>";
		$mapper->save($post);

		$this->assertSame(0, count($post->comments));
	}

	public function testBlogCommentsIterateEmptySet()
	{
		$mapper = test_spot_mapper();
		$post = $mapper->get('\Spot\Entity\Post');
		$post->title = "No Comments";
		$post->body = "<p>Comments relation test</p>";
		$mapper->save($post);

		// Testing that we can iterate over an empty set
		foreach($post->comments as $comment) {
			$this->assertTrue($comment instanceOf \Spot\Entity\Post\Comment);
		}
	}

	/**
	 * @depends testBlogPostInsert
	 */
	public function testBlogCommentsRelationCountOne($postId = 1)
	{
		$mapper = test_spot_mapper();
		$post = $mapper->get('\Spot\Entity\Post', $postId);
		$this->assertTrue(count($post->comments) == 1);
	}

	/**
	 * @depends testBlogPostInsert
	 */
	public function testBlogCommentsRelationReturnsRelationObject($postId = 1)
	{
		$mapper = test_spot_mapper();
		$post = $mapper->get('\Spot\Entity\Post', $postId);
		$this->assertTrue($post->comments instanceof \Spot\Relation\RelationAbstract);
	}

	/**
	 * @depends testBlogPostInsert
	 */
	public function testBlogCommentsRelationCanBeModified($postId = 1)
	{
		$mapper = test_spot_mapper();
		$post = $mapper->get('\Spot\Entity\Post', $postId);
		$this->assertTrue($post->comments instanceof \Spot\Relation\HasMany);
		$sortedComments = $post->comments->order(array('date_created' => 'DESC'));
		$this->assertTrue($sortedComments instanceof \Spot\Query);
	}


	/**
	 * @depends testBlogPostInsert
	 */
	public function testRelationshipQueryNotReset($postId = 1)
	{
		$mapper = test_spot_mapper();
		$post = $mapper->get('\Spot\Entity\Post', $postId);

		$before_conditions = $post->comments->execute()->conditions;
		$before_count = $post->comments->count();
		foreach($post->comments as $comment) {
		  $query = $comment->post->execute();
		}

		$this->assertSame($before_count, $post->comments->count());
		$this->assertSame($before_conditions, $post->comments->execute()->conditions);
	}

	/**
	 * @depends testBlogPostInsert
	 */
	public function testRelationshipQueryResetting($postId = 1)
	{
		$mapper = test_spot_mapper();
		$post = $mapper->get('\Spot\Entity\Post', $postId);

		$before_conditions = $post->comments->execute()->conditions;
		$before_count = $post->comments->count();

		// Make sure a manual reset doesn't reset
		$post->comments->reset();
		$this->assertSame($before_conditions, $post->comments->execute()->conditions);

		// Make sure a hard reset does
		$post->comments->reset(true);
		$this->assertSame(array(), $post->comments->execute()->conditions);
	}

	/**
	 * @depends testBlogPostInsert
	 */
	public function testRelationshipQueryAdditionalConditionResetting($postId = 1)
	{
		$mapper = test_spot_mapper();
		$post = $mapper->get('\Spot\Entity\Post', $postId);

		$initial_conditions = $post->comments->execute()->conditions;

		$post->comments->where(array('body' => 'hi'));

		$this->assertNotSame($initial_conditions, $post->comments->execute()->conditions);

		// Make sure a manual reset returns to initial relationship state
		$post->comments->reset();
		$this->assertSame($initial_conditions, $post->comments->execute()->conditions);

		// Make sure a hard reset does
		$post->comments->reset(true);
		$this->assertNotSame($initial_conditions, $post->comments->execute()->conditions);
		$this->assertSame(array(), $post->comments->execute()->conditions);
	}

	// TODO: Write query snapshot tests for HasManyThrough relations
}