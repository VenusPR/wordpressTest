<?php
/**
 * WordPress Coding Standard.
 *
 * @package WPCS\WordPressCodingStandards
 * @link    https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace WordPress\Sniffs\Security;

use WordPress\AbstractFunctionRestrictionsSniff;

/**
 * Encourages use of wp_safe_redirect() to avoid open redirect vulnerabilities.
 *
 * @package WPCS\WordPressCodingStandards
 *
 * @since   1.0.0
 */
class SafeRedirectSniff extends AbstractFunctionRestrictionsSniff {

	/**
	 * Groups of functions to restrict.
	 *
	 * Example: groups => array(
	 *  'lambda' => array(
	 *      'type'      => 'error' | 'warning',
	 *      'message'   => 'Use anonymous functions instead please!',
	 *      'functions' => array( 'file_get_contents', 'create_function' ),
	 *  )
	 * )
	 *
	 * @return array
	 */
	public function getGroups() {
		return array(
			'wp_redirect' => array(
				'type'      => 'warning',
				'message'   => '%s() found. Using wp_safe_redirect(), along with the allowed_redirect_hosts filter if needed, can help avoid any chances of malicious redirects within code.',
				'functions' => array(
					'wp_redirect',
				),
			),
		);
	} // End getGroups().

} // End class.
