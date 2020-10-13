<?php

namespace BH_WP_Autologin_URLs\api;

class DB_Data_Store_Integration_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Create the database, add a code to it, try to retrieve it.
	 */
	public function test_happy_store_retrieve() {

		$this->markTestIncomplete();

		$ds = new DB_Data_Store();

		$ds->create_db();

		$user_id = $this->factory->user->create(
			array(
				'user_login' => 'brian',
				'user_email' => 'brianhenryie@gmail.com',
			)
		);

		$ds->save( $user_id, 'autologincode', DAY_IN_SECONDS );

		$result = $ds->get_value_for_code('autologincode' );

		$this->assertNotNull( $result );
	}

}