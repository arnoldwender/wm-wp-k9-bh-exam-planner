<?php
/**
 * WM K9 BH Exam Planner — Decoupled REST API Controller
 *
 * Exposes endpoints:
 * - POST /wp-json/wm-k9/v1/zulassung
 * - POST /wp-json/wm-k9/v1/bewertung
 *
 * @package WM_K9_BH_Exam_Planner
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WM_K9_REST_Controller {

	public const RATE_LIMIT_MAX    = 60;
	public const RATE_LIMIT_WINDOW = 60;

	/** @var \WeakMap<object, bool>|null Tracks per-request rate-limit evaluation so permission + handler never double-count. */
	private static ?\WeakMap $checked_requests = null;

	public static function init(): void {
		add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
	}

	/**
	 * Argument schemas. WordPress validates each value against its type before the callback runs and answers
	 * 400 `rest_invalid_param` otherwise. Until 2026-09-15 the routes had no `args`: `has_skn=nein` or `off`
	 * reached rest_sanitize_boolean(), which casts every string but "false" and "0" to true, so a handler without
	 * a Sachkundenachweis came back eligible; `14,5` points became 14.0 and flipped a passed sheet to failed.
	 *
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	public static function route_args(): array {
		$boolean = static fn( string $description ): array => [
			'type'        => 'boolean',
			'default'     => false,
			'description' => $description,
		];

		$scoring = [];
		foreach ( WM_K9_Scoring_Engine::EXERCISES as $key => $exercise ) {
			$scoring[ $key ] = [
				'type'        => 'number',
				'minimum'     => 0,
				'maximum'     => $exercise['max'],
				'default'     => 0,
				'description' => $exercise['label'],
			];
		}
		$scoring['teil_b_passed']           = $boolean( 'Teil B passed' );
		$scoring['disqualified']            = $boolean( 'Disqualified' );
		$scoring['disqualification_reason'] = [
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => [ self::class, 'sanitize_text_param' ],
		];

		return [
			'zulassung' => [
				'birth_date' => [ 'type' => 'string', 'required' => true, 'description' => 'YYYY-MM-DD', 'sanitize_callback' => [ self::class, 'sanitize_text_param' ] ],
				'exam_date'  => [ 'type' => 'string', 'required' => true, 'description' => 'YYYY-MM-DD', 'sanitize_callback' => [ self::class, 'sanitize_text_param' ] ],
				'microchip'  => [ 'type' => 'string', 'required' => true, 'sanitize_callback' => [ self::class, 'sanitize_text_param' ] ],
				'has_skn'    => $boolean( 'Handler Sachkundenachweis' ),
			],
			'bewertung' => $scoring,
		];
	}

	public static function register_routes(): void {
		$args = self::route_args();

		register_rest_route( 'wm-k9/v1', '/zulassung', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'handle_admission' ],
			'permission_callback' => [ self::class, 'check_zulassung_access' ],
			'args'                => $args['zulassung'],
		] );

		register_rest_route( 'wm-k9/v1', '/bewertung', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'handle_scoring' ],
			'permission_callback' => [ self::class, 'check_bewertung_access' ],
			'args'                => $args['bewertung'],
		] );
	}

	/** Schema sanitize_callback wrapper for string parameters (runs after core schema type check). */
	public static function sanitize_text_param( mixed $value ): string {
		return self::text( $value );
	}

	/** A scalar request value as text; arrays and objects count as empty (the schema answers 400 before this). */
	private static function text( $value ): string {
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}

	/**
	 * Anonymised transient key using HMAC-SHA256 so client IP addresses are never persisted in plain text.
	 */
	public static function rate_limit_key( string $bucket, ?string $ip = null ): string {
		$raw_ip = null !== $ip
			? $ip
			: ( isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] )
				? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
				: '0.0.0.0' );
		$salt = function_exists( 'wp_salt' ) ? (string) wp_salt( 'auth' ) : __FILE__;
		return 'wm_k9_rl_' . sanitize_key( $bucket ) . '_' . substr( hash_hmac( 'sha256', $raw_ip, $salt ), 0, 24 );
	}

	/**
	 * Evaluate per-IP rate limit at most once per WP_REST_Request instance.
	 */
	private static function is_rate_limited( \WP_REST_Request $request, string $bucket ): bool {
		if ( null === self::$checked_requests ) {
			self::$checked_requests = new \WeakMap();
		}
		if ( isset( self::$checked_requests[ $request ] ) ) {
			return self::$checked_requests[ $request ];
		}
		if ( ! function_exists( 'get_transient' ) || ! function_exists( 'set_transient' ) ) {
			self::$checked_requests[ $request ] = false;
			return false;
		}

		$limit = self::RATE_LIMIT_MAX;
		if ( function_exists( 'apply_filters' ) ) {
			$limit = (int) apply_filters( 'wm_k9_rest_rate_limit', $limit, $bucket );
		}
		if ( $limit <= 0 ) {
			self::$checked_requests[ $request ] = false;
			return false;
		}

		$key   = self::rate_limit_key( $bucket );
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			self::$checked_requests[ $request ] = true;
			return true;
		}

		set_transient( $key, $count + 1, self::RATE_LIMIT_WINDOW );
		self::$checked_requests[ $request ] = false;
		return false;
	}

	private static function rate_limit_message(): string {
		return function_exists( '__' )
			? __( 'Zu viele Anfragen. Bitte kurz warten und erneut versuchen.', 'wm-k9-bh-exam-planner' )
			: 'Zu viele Anfragen. Bitte kurz warten und erneut versuchen.';
	}

	private static function rate_limited_response(): \WP_REST_Response {
		return new \WP_REST_Response(
			[
				'success' => false,
				'code'    => 'wm_k9_rate_limited',
				'message' => self::rate_limit_message(),
			],
			429
		);
	}

	/**
	 * Public calculator access with transient IP-HMAC rate-limiting for `/zulassung`.
	 *
	 * @return bool|\WP_Error
	 */
	public static function check_zulassung_access( \WP_REST_Request $request ) {
		if ( self::is_rate_limited( $request, 'zulassung' ) ) {
			return class_exists( 'WP_Error' )
				? new \WP_Error( 'wm_k9_rate_limited', self::rate_limit_message(), [ 'status' => 429 ] )
				: false;
		}
		return true;
	}

	/**
	 * Public calculator access with transient IP-HMAC rate-limiting for `/bewertung`.
	 *
	 * @return bool|\WP_Error
	 */
	public static function check_bewertung_access( \WP_REST_Request $request ) {
		if ( self::is_rate_limited( $request, 'bewertung' ) ) {
			return class_exists( 'WP_Error' )
				? new \WP_Error( 'wm_k9_rate_limited', self::rate_limit_message(), [ 'status' => 429 ] )
				: false;
		}
		return true;
	}

	public static function handle_admission( \WP_REST_Request $request ): \WP_REST_Response {
		if ( self::is_rate_limited( $request, 'zulassung' ) ) {
			return self::rate_limited_response();
		}

		$birth_date = self::text( $request->get_param( 'birth_date' ) );
		$exam_date  = self::text( $request->get_param( 'exam_date' ) );
		$microchip  = self::text( $request->get_param( 'microchip' ) );
		// rest_sanitize_boolean(), not a (bool) cast: a form posting has_skn=false sent the string
		// "false", which casts to true, and a handler without a Sachkundenachweis came back
		// eligible (reproduced on a local WordPress install, 2026-09-14). The schema above rejects anything else.
		$has_skn = rest_sanitize_boolean( $request->get_param( 'has_skn' ) ?? false );

		$result = WM_K9_Admission_Validator::check_eligibility( $birth_date, $exam_date, $microchip, $has_skn );

		return new \WP_REST_Response( [
			'success'   => true,
			'data'      => $result,
			'timestamp' => gmdate( 'c' ),
		], 200 );
	}

	public static function handle_scoring( \WP_REST_Request $request ): \WP_REST_Response {
		if ( self::is_rate_limited( $request, 'bewertung' ) ) {
			return self::rate_limited_response();
		}

		$scores = [];
		foreach ( array_keys( WM_K9_Scoring_Engine::EXERCISES ) as $key ) {
			$value          = $request->get_param( $key );
			$scores[ $key ] = is_numeric( $value ) ? (float) $value : 0.0;
		}
		// Same cast problem: teil_b_passed=false scored a full Teil A as BESTANDEN and
		// disqualified=false disqualified the dog.
		$teil_b_passed = rest_sanitize_boolean( $request->get_param( 'teil_b_passed' ) ?? false );
		$disqualified  = rest_sanitize_boolean( $request->get_param( 'disqualified' ) ?? false );
		$reason        = self::text( $request->get_param( 'disqualification_reason' ) );

		$result = WM_K9_Scoring_Engine::evaluate( $scores, $teil_b_passed, $disqualified, $reason );

		return new \WP_REST_Response( [
			'success'   => true,
			'data'      => $result,
			'timestamp' => gmdate( 'c' ),
		], 200 );
	}
}
