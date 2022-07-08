<?php

namespace BrianHenryIE\WP_Autologin_URLs\API\Integrations;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;

/**
 * @coversNothing
 */
class Klaviyo_Integration_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test a real API call to Klaviyo.
	 */
	public function test_api_call(): void {

		 $this->markTestIncomplete();

		$klaviyo_private_api_key = $_ENV['KLAVIYO_PRIVATE_API_KEY'];

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_klaviyo_private_api_key' => $klaviyo_private_api_key,
			)
		);
		$client   = null;

		$sut = new Klaviyo( $settings, $logger, $client );

		// https://trk.klclick.com/ls/click?upn=TpNVgfWNpAHoEcylFkjYi5tCZ2j7xCLw8j9SqiGDS-2FUQTW60oUBb5vsJzFNGiLY6C9-2BqSuTQNIDkwJvyFHeLgnuvmB1ECb6kTd4yTBlLd9s7uwWgKqkvXGscWXNVN0WrLZncvcbxEFn7QOaz2vjB9g-3D-3DZJbd_29QiiZ2K4aGQ2vLdffUQvW5Frxt4zybwdx9ysVnkeZHduhpBUuWeRxU9XdsDy5xMo5PFzr2ZEfRhlUfLOzTqYKUmfO99pdV4BbvH17u6dKaAqwf3BeIVV3Tmmfs2nZTcTV-2BHLlKTvqxVilb-2FLZVnt59UqnlW8pYu2nVtomhdsI5pd88yUbTP24h1u3oh0w2Eqp00-2F6NgOaJKBGEStPeb-2Bq4aIF4Ykq3b2-2FPxDq4feC-2B8VVbFxT7XLDfNHXFryJBenl8rh4EXSbaea42QXfEp0e-2FKRSw1VXznSsR09GEjvb0T0hkQeCU8KtqUOlA8v0NrU-2BCYfISX-2BlXKC9vjSFCvnakQeyb-2FCZuXtQhrNNhzG1OoQDEbhhVoTNKCu1XZBxbig9wc8JxKdBCXWIR14WgIIQ-3D-3D
		$_GET['_kx'] = '_h14GmLS1K9C377xMgncGUnbBzQJ-NY7PBSbXqnXuj-DB5k3vZNwko7QUU_yzHqz.KxHkN6'; // TODO:

		assert( $sut->is_querystring_valid() );

		$result = $sut->get_wp_user_array();

		// $klaviyo_user = $profiles->getProfile( $klaviyo_user_id );
		// array (
		// 'object' => 'person',
		// 'id' => '01FYS6CDW111GV40DBTE66AFZZ',
		// '$address1' => '1234 56th St',
		// '$address2' => '',
		// '$city' => 'Sacramento',
		// '$country' => 'US',
		// '$latitude' => 33.6096,
		// '$longitude' => -111.4443,
		// '$region' => 'CA',
		// '$zip' => '95815-1234',
		// '$organization' => '',
		// '$first_name' => 'Brian',
		// '$email' => 'notbrian{{@gmail.com',
		// '$phone_number' => '+1612335573',
		// '$title' => '',
		// '$last_name' => 'Henry',
		// '$timezone' => 'America/Los_Angeles',
		// '$id' => '',
		// 'Referral Link with Tracking - ReferralCandy' => 'http://example.refr.cc/brianh?t=kl',
		// 'Referral Link - ReferralCandy' => 'http://example.refr.cc/brianh',
		// 'Referral Portal Link - ReferralCandy' => 'https://example.referralcandy.com/L7VLK9N',
		// 'Referral Friend Offer - ReferralCandy' => '20% off all products',
		// 'Referral Reward - ReferralCandy' => '15% off all products',
		// 'Expected Date Of Next Order' => '05/04/2022',
		// 'email' => 'notbrian{{@gmail.com',
		// 'first_name' => 'Brian',
		// 'last_name' => 'Henry',
		// 'created' => '2022-03-22 16:11:58',
		// 'updated' => '2022-07-07 04:01:26',
		// )
	}
}
