<?php
/**
 * WordPress Coding Standard.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     https://make.wordpress.org/core/handbook/best-practices/coding-standards/
 */

if ( ! class_exists( 'Generic_Sniffs_PHP_ForbiddenFunctionsSniff', true ) ) {
	throw new PHP_CodeSniffer_Exception( 'Class Generic_Sniffs_PHP_ForbiddenFunctionsSniff not found' );
}

/**
 * WordPress_Sniffs_VIP_TimezoneChangeSniff.
 *
 * Disallow the changing of timezone
 *
 * @link     http://vip.wordpress.com/documentation/use-current_time-not-date_default_timezone_set/
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @author   Shady Sharaf <shady@x-team.com>
 */
class WordPress_Sniffs_VIP_TimezoneChangeSniff extends Generic_Sniffs_PHP_ForbiddenFunctionsSniff {

	/**
	 * A list of forbidden functions with their alternatives.
	 *
	 * The value is NULL if no alternative exists. IE, the
	 * function should just not be used.
	 *
	 * @var array(string => string|null)
	 */
	public $forbiddenFunctions = array(
		'date_default_timezone_set' => null,
	);

	protected function addError( $phpcsFile, $stackPtr, $function, $pattern = null ) {
		$error = 'Using date_default_timezone_set() and similar isn’t allowed, instead use WP internal timezone support.';
		$phpcsFile->addError( $error, $stackPtr, $function );

	} // end addError()

} // end class
