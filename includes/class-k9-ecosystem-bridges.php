<?php
/**
 * WM K9 BH Exam Planner — Gutenberg Block Bindings & GenerateBlocks Dynamic Tag Bridges
 *
 * Exposes read-only BH/VT rule constants and site-local exam date to WordPress 6.5+ Block Bindings
 * (`wm-k9/exam-info`) and GenerateBlocks 2.x Dynamic Tags (`{{wm_k9_info}}`).
 *
 * @package WM_K9_BH_Exam_Planner
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WM_K9_Ecosystem_Bridges {

	public const BINDING_SOURCE = 'wm-k9/exam-info';
	public const GB_TAG         = 'wm_k9_info';

	public static function init(): void {
		add_action( 'init', [ self::class, 'register_block_bindings' ], 20 );
		add_action( 'init', [ self::class, 'register_generateblocks_tag' ], 20 );
		add_filter( 'generateblocks_dynamic_tag_replacement', [ self::class, 'filter_generateblocks_output' ], 10, 2 );
	}

	/**
	 * Resolve a named exam-parameter field from the single-source constants.
	 */
	public static function resolve_info( string $key = 'summary' ): ?string {
		$clean_key = '' !== $key ? strtolower( trim( $key ) ) : 'summary';

		switch ( $clean_key ) {
			case 'rule_edition':
				$value = WM_K9_Scoring_Engine::RULE_EDITION;
				break;
			case 'min_age_months':
				$value = (string) WM_K9_Admission_Validator::MIN_AGE_MONTHS;
				break;
			case 'chip_digits':
				$value = (string) WM_K9_Admission_Validator::CHIP_DIGITS;
				break;
			case 'max_points_teil_a':
				$value = WM_K9_Scoring_Engine::format_points( WM_K9_Scoring_Engine::MAX_POINTS_TEIL_A );
				break;
			case 'passing_points_teil_a':
				$value = WM_K9_Scoring_Engine::format_points( WM_K9_Scoring_Engine::PASSING_POINTS_TEIL_A );
				break;
			case 'site_today':
				$value = WM_K9_Shortcodes::site_today();
				break;
			case 'summary':
				$template = function_exists( '__' )
					/* translators: 1: rule edition, 2: minimum age in months, 3: passing points, 4: maximum points */
					? __( '%1$s · Mindestalter %2$d Monate · Teil A ab %3$s von %4$s Punkten', 'wm-k9-bh-exam-planner' )
					: '%1$s · Mindestalter %2$d Monate · Teil A ab %3$s von %4$s Punkten';
				$value = sprintf(
					$template,
					WM_K9_Scoring_Engine::RULE_EDITION,
					WM_K9_Admission_Validator::MIN_AGE_MONTHS,
					WM_K9_Scoring_Engine::format_points( WM_K9_Scoring_Engine::PASSING_POINTS_TEIL_A ),
					WM_K9_Scoring_Engine::format_points( WM_K9_Scoring_Engine::MAX_POINTS_TEIL_A )
				);
				break;
			default:
				$value = null;
				break;
		}

		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters( 'wm_k9_exam_info_value', $value, $clean_key );
			$value    = is_string( $filtered ) || null === $filtered ? $filtered : $value;
		}

		return $value;
	}

	/**
	 * Register WordPress 6.5+ Block Bindings source `wm-k9/exam-info`.
	 */
	public static function register_block_bindings(): void {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}
		if ( function_exists( 'get_block_bindings_source' ) && null !== get_block_bindings_source( 'wm-k9/exam-info' ) ) {
			return;
		}

		register_block_bindings_source(
			'wm-k9/exam-info',
			[
				'label'              => __( 'BH/VT Prüfungsdaten', 'wm-k9-bh-exam-planner' ),
				'get_value_callback' => [ self::class, 'get_block_binding_value' ],
				'uses_context'       => [ 'postId', 'postType' ],
			]
		);
	}

	/**
	 * Callback for WP 6.5+ Block Bindings source.
	 *
	 * @param array<string, mixed> $source_args    Binding source arguments.
	 * @param mixed                $block_instance Block instance.
	 * @param string               $attribute_name Bound attribute name.
	 */
	public static function get_block_binding_value( array $source_args, mixed $block_instance = null, string $attribute_name = 'content' ): ?string {
		$key = isset( $source_args['key'] ) && is_string( $source_args['key'] ) ? $source_args['key'] : 'summary';
		return self::resolve_info( $key );
	}

	/**
	 * Register GenerateBlocks 2.x Dynamic Tag `{{wm_k9_info}}`.
	 */
	public static function register_generateblocks_tag(): void {
		if ( ! class_exists( 'GenerateBlocks_Register_Dynamic_Tag' ) ) {
			return;
		}

		new \GenerateBlocks_Register_Dynamic_Tag(
			[
				'title'    => __( 'K9 BH Prüfungsinfo', 'wm-k9-bh-exam-planner' ),
				'tag'      => 'wm_k9_info',
				'type'     => 'wm-k9',
				'supports' => [],
				'options'  => [
					'key' => [
						'type'  => 'select',
						'label' => __( 'Datenfeld', 'wm-k9-bh-exam-planner' ),
					],
				],
				'return'   => [ self::class, 'render_generateblocks_tag' ],
			]
		);
	}

	/**
	 * Render callback for GenerateBlocks 2.x Dynamic Tag `{{wm_k9_info}}`.
	 *
	 * @param array<string, mixed> $options Tag options (e.g. `key`).
	 * @param array<string, mixed> $block   Block data.
	 * @param mixed                $instance Dynamic tag instance.
	 */
	public static function render_generateblocks_tag( array $options = [], array $block = [], mixed $instance = null ): string {
		$key   = isset( $options['key'] ) && is_string( $options['key'] ) ? $options['key'] : 'summary';
		$value = self::resolve_info( $key );
		return null !== $value ? esc_html( $value ) : '';
	}

	/**
	 * Filter hook fallback for `generateblocks_dynamic_tag_output`.
	 *
	 * @param mixed $output   Rendered output.
	 * @param mixed $tag_data Parsed dynamic tag descriptor.
	 * @param mixed $context  Block context.
	 */
	public static function filter_generateblocks_output( mixed $output, mixed $tag_data = [], mixed $context = null ): mixed {
		if ( is_string( $output ) && '' !== $output ) {
			return $output;
		}
		if ( is_array( $tag_data ) && ( $tag_data['tag'] ?? '' ) === self::GB_TAG ) {
			$options = isset( $tag_data['options'] ) && is_array( $tag_data['options'] ) ? $tag_data['options'] : [];
			return self::render_generateblocks_tag( $options );
		}
		return $output;
	}
}
