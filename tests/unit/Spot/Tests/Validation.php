<?php
/**
 * @package Spot
 */

namespace Spot\Tests;

class Validation extends SpotTestCase
{
	protected $backupGlobals = false;

	public static function setupBeforeClass()
	{
		$mapper = test_spot_mapper();
		$mapper->migrate('\Spot\Entity\User');
	}
	public static function tearDownAfterClass()
	{
		$mapper = test_spot_mapper();
		$mapper->truncateDatasource('\Spot\Entity\User');
	}

	public function testUniqueFieldCreatesValidationError()
	{
		$mapper = test_spot_mapper();

		// Setup new user
		$user1 = new \Spot\Entity\User(array(
			'email' => 'test@test.com',
			'password' => 'test',
			'is_admin' => true
		));
		$mapper->save($user1);

		// Setup new user (identical, expecting a validation error)
		$user2 = new \Spot\Entity\User(array(
			'email' => 'test@test.com',
			'password' => 'test',
			'is_admin' => false
		));
		$mapper->save($user2);

		$this->assertFalse($user1->hasErrors());
		$this->assertTrue($user2->hasErrors());
		$this->assertEquals($user2->errors('email'), array("Email 'test@test.com' is already taken."));
	}
}
