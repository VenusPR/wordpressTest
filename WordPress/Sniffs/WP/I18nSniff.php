<?php
/**
 * WordPress_Sniffs_WP_I18nSniff
 *
 * Makes sure internationalization functions are used properly
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Shady Sharaf <shady@x-team.com>
 */
class WordPress_Sniffs_WP_I18nSniff extends WordPress_Sniff {

	/**
	 * Text domain.
	 *
	 * @todo Eventually this should be able to be auto-supplied via looking at $phpcs_file->getFilename()
	 * @link https://youtrack.jetbrains.com/issue/WI-17740
	 *
	 * @var string
	 */
	public $text_domain;

	/**
	 * Allow unit tests to override the supplied text_domain.
	 *
	 * @todo While it doesn't work, ideally this should be able to be done in \WordPress_Tests_WP_I18nUnitTest::setUp()
	 *
	 * @var string
	 */
	static $text_domain_override;

	public $i18n_functions = array(
		'translate'                      => 'simple',
		'__'                             => 'simple',
		'esc_attr__'                     => 'simple',
		'esc_html__'                     => 'simple',
		'_e'                             => 'simple',
		'esc_attr_e'                     => 'simple',
		'esc_html_e'                     => 'simple',
		'translate_with_gettext_context' => 'context',
		'_x'                             => 'context',
		'_ex'                            => 'context',
		'esc_attr_x'                     => 'context',
		'esc_html_x'                     => 'context',
		'_n'                             => 'number',
		'_nx'                            => 'number_context',
		'_n_noop'                        => 'noopnumber',
		'_nx_noop'                       => 'noopnumber_context',
	);

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return array(
			T_STRING,
		);
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param PHP_CodeSniffer_File $phpcs_file The file being scanned.
	 * @param int                  $stack_ptr  The position of the current token
	 *                                        in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( PHP_CodeSniffer_File $phpcs_file, $stack_ptr ) {
		$tokens = $phpcs_file->getTokens();
		$token  = $tokens[ $stack_ptr ];

		if ( ! empty( self::$text_domain_override ) ) {
			$this->text_domain = self::$text_domain_override;
		}

		if ( '_' === $token['content'] ) {
			$phpcs_file->addError( 'Found single-underscore "_()" function when double-underscore expected.', $stack_ptr, 'SingleUnderscoreGetTextFunction' );
		}

		if ( ! isset( $this->i18n_functions[ $token['content'] ] ) ) {
			return;
		}
		$translation_function = $token['content'];

		if ( in_array( $translation_function, array( 'translate', 'translate_with_gettext_context' ), true ) ) {
			$phpcs_file->addWarning( 'Use of the "%s()" function is reserved for low-level API usage.', $stack_ptr, 'LowLevelTranslationFunction', array( $translation_function ) );
		}

		$func_open_paren_token = $phpcs_file->findNext( T_WHITESPACE, $stack_ptr + 1, null, true );
		if ( ! $func_open_paren_token || T_OPEN_PARENTHESIS !== $tokens[ $func_open_paren_token ]['code'] ) {
			 return;
		}

		$arguments_tokens = array();
		$argument_tokens = array();

		// Look at arguments.
		for ( $i = $func_open_paren_token + 1; $i < $tokens[ $func_open_paren_token ]['parenthesis_closer']; $i += 1 ) {
			$this_token = $tokens[ $i ];
			$this_token['token_index'] = $i;
			if ( in_array( $this_token['code'], array( T_WHITESPACE, T_COMMENT ), true ) ) {
				continue;
			}
			if ( T_COMMA === $this_token['code'] ) {
				$arguments_tokens[] = $argument_tokens;
				$argument_tokens = array();
				continue;
			}

			// Merge consecutive single or double quoted strings (when they span multiple lines).
			if ( T_CONSTANT_ENCAPSED_STRING === $this_token['code'] || T_DOUBLE_QUOTED_STRING === $this_token['code'] ) {
				for ( $j = $i + 1; $j < $tokens[ $func_open_paren_token ]['parenthesis_closer']; $j += 1 ) {
					if ( $this_token['code'] === $tokens[ $j ]['code'] ) {
						$this_token['content'] .= $tokens[ $j ]['content'];
						$i = $j;
					} else {
						break;
					}
				}
			}
			$argument_tokens[] = $this_token;

			// Include everything up to and including the parenthesis_closer if this token has one.
			if ( ! empty( $this_token['parenthesis_closer'] ) ) {
				for ( $j = $i + 1; $j <= $this_token['parenthesis_closer']; $j += 1 ) {
					$tokens[ $j ]['token_index'] = $j;
					$argument_tokens[] = $tokens[ $j ];
				}
				$i = $this_token['parenthesis_closer'];
			}
		}
		if ( ! empty( $argument_tokens ) ) {
			$arguments_tokens[] = $argument_tokens;
		}
		unset( $argument_tokens );

		$argument_assertions = array();
		if ( 'simple' === $this->i18n_functions[ $translation_function ] ) {
			$argument_assertions[] = array( 'arg_name' => 'text',    'tokens' => array_shift( $arguments_tokens ) );
			$argument_assertions[] = array( 'arg_name' => 'domain',  'tokens' => array_shift( $arguments_tokens ) );
		} elseif ( 'context' === $this->i18n_functions[ $translation_function ] ) {
			$argument_assertions[] = array( 'arg_name' => 'text',    'tokens' => array_shift( $arguments_tokens ) );
			$argument_assertions[] = array( 'arg_name' => 'context', 'tokens' => array_shift( $arguments_tokens ) );
			$argument_assertions[] = array( 'arg_name' => 'domain',  'tokens' => array_shift( $arguments_tokens ) );
		} elseif ( 'number' === $this->i18n_functions[ $translation_function ] ) {
			$argument_assertions[] = array( 'arg_name' => 'single',  'tokens' => array_shift( $arguments_tokens ) );
			$argument_assertions[] = array( 'arg_name' => 'plural',  'tokens' => array_shift( $arguments_tokens ) );
			array_shift( $arguments_tokens );
			$argument_assertions[] = array( 'arg_name' => 'domain',  'tokens' => array_shift( $arguments_tokens ) );
		} elseif ( 'number_context' === $this->i18n_functions[ $translation_function ] ) {
			$argument_assertions[] = array( 'arg_name' => 'single',  'tokens' => array_shift( $arguments_tokens ) );
			$argument_assertions[] = array( 'arg_name' => 'plural',  'tokens' => array_shift( $arguments_tokens ) );
			array_shift( $arguments_tokens );
			$argument_assertions[] = array( 'arg_name' => 'context', 'tokens' => array_shift( $arguments_tokens ) );
			$argument_assertions[] = array( 'arg_name' => 'domain',  'tokens' => array_shift( $arguments_tokens ) );
		} elseif ( 'noopnumber' === $this->i18n_functions[ $translation_function ] ) {
			$argument_assertions[] = array( 'arg_name' => 'single',  'tokens' => array_shift( $arguments_tokens ) );
			$argument_assertions[] = array( 'arg_name' => 'plural',  'tokens' => array_shift( $arguments_tokens ) );
			$argument_assertions[] = array( 'arg_name' => 'domain',  'tokens' => array_shift( $arguments_tokens ) );
		} elseif ( 'noopnumber_context' === $this->i18n_functions[ $translation_function ] ) {
			$argument_assertions[] = array( 'arg_name' => 'single',  'tokens' => array_shift( $arguments_tokens ) );
			$argument_assertions[] = array( 'arg_name' => 'plural',  'tokens' => array_shift( $arguments_tokens ) );
			$argument_assertions[] = array( 'arg_name' => 'context', 'tokens' => array_shift( $arguments_tokens ) );
			$argument_assertions[] = array( 'arg_name' => 'domain',  'tokens' => array_shift( $arguments_tokens ) );
		}

		if ( ! empty( $arguments_tokens ) ) {
			$phpcs_file->addError( 'Too many arguments for function "%s".', $func_open_paren_token, 'TooManyFunctionArgs', array( $translation_function ) );
		}

		foreach ( $argument_assertions as $argument_assertion_context ) {
			if ( empty( $argument_assertion_context['tokens'][0] ) ) {
				$argument_assertion_context['stack_ptr'] = $func_open_paren_token;
			} else {
				$argument_assertion_context['stack_ptr'] = $argument_assertion_context['tokens'][0]['token_index'];
			}
			call_user_func( array( $this, 'check_argument_tokens' ), $phpcs_file, $argument_assertion_context );
		}
	}

	/**
	 * Check if supplied tokens represent a translation text string literal.
	 *
	 * @param PHP_CodeSniffer_File $phpcs_file The file being scanned.
	 * @param array $context
	 * @return bool
	 */
	protected function check_argument_tokens( PHP_CodeSniffer_File $phpcs_file, $context ) {
		$stack_ptr = $context['stack_ptr'];
		$tokens = $context['tokens'];
		$arg_name = $context['arg_name'];
		$method = empty( $context['warning'] ) ? 'addError' : 'addWarning';

		if ( 0 === count( $tokens ) ) {
			$code = 'MissingArg' . ucfirst( $arg_name );
			if ( 'domain' !== $arg_name || ! empty( $this->text_domain ) ) {
				$phpcs_file->$method( 'Missing $%s arg.', $stack_ptr, $code, array( $arg_name ) );
			}
			return false;
		}
		if ( count( $tokens ) > 1 ) {
			$contents = '';
			foreach ( $tokens as $token ) {
				$contents .= $token['content'];
			}
			$code = 'NonSingularStringLiteral' . ucfirst( $arg_name );
			$phpcs_file->$method( 'The $%s arg must be a single string literal, not "%s".', $stack_ptr, $code, array( $arg_name, $contents ) );
			return false;
		}
		if ( T_CONSTANT_ENCAPSED_STRING === $tokens[0]['code'] ) {
			if ( 'domain' === $arg_name && ! empty( $this->text_domain ) && trim( $tokens[0]['content'], '\'""' ) !== $this->text_domain ) {
				$phpcs_file->$method( 'Mismatch text domain. Expected \'%s\' but got %s.', $stack_ptr, 'TextDomainMismatch', array( $this->text_domain, $tokens[0]['content'] ) );
				return false;
			}
			return true;
		}
		if ( T_DOUBLE_QUOTED_STRING === $tokens[0]['code'] ) {
			$interpolated_variables = $this->get_interpolated_variables( $tokens[0]['content'] );
			foreach ( $interpolated_variables as $interpolated_variable ) {
				$code = 'InterpolatedVariable' . ucfirst( $arg_name );
				$phpcs_file->$method( 'The $%s arg must not contain interpolated variables. Found "$%s".', $stack_ptr, $code, array( $arg_name, $interpolated_variable ) );
			}
			if ( ! empty( $interpolated_variables ) ) {
				return false;
			}
			if ( 'domain' === $arg_name && ! empty( $this->text_domain ) && trim( $tokens[0]['content'], '\'""' ) !== $this->text_domain ) {
				$phpcs_file->$method( 'Mismatch text domain. Expected \'%s\' but got %s.', $stack_ptr, 'TextDomainMismatch', array( $this->text_domain, $tokens[0]['content'] ) );
				return false;
			}
			return true;
		}

		$code = 'NonSingularStringLiteral' . ucfirst( $arg_name );
		$phpcs_file->$method( 'The $%s arg should be single a string literal, not "%s".', $stack_ptr, $code, array( $arg_name, $tokens[0]['content'] ) );
		return false;
	}
}
