<?php
/**
 * WM K9 BH Exam Planner — Suite Hub Spoke Adapter
 *
 * Implements the WM Suite Hub contract (WM_Plugin_Module_Interface): health from the self-test,
 * telemetry counts, one SBOM component (a fragment the hub places in its document; not validated against a
 * CycloneDX schema here).
 *
 * @package WM_K9_BH_Exam_Planner
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface' ) ) {
	return;
}

use ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface;

final class WM_K9_Spoke_Adapter implements WM_Plugin_Module_Interface {

	/** @var (callable(): bool)|null Replaces the self-test; the hub constructs the adapter without it. */
	private $self_test;

	public function __construct( ?callable $self_test = null ) {
		$this->self_test = $self_test;
	}

	public function get_module_slug(): string {
		return 'wm-k9-bh-exam-planner';
	}

	public function get_name(): string {
		return 'WM K9 BH Exam Planner';
	}

	public function get_version(): string {
		return defined( 'WM_K9_PLANNER_VERSION' ) ? WM_K9_PLANNER_VERSION : '1.2.2';
	}

	public function get_description(): string {
		return 'Zulassungs-Vorabprüfung und Punkterechner für die Begleithundeprüfung (BH/VT).';
	}

	/**
	 * `ERROR` when a calculator no longer returns its known values. Until 2026-09-15 the literal `HEALTHY`,
	 * also with a failing self-test.
	 */
	public function get_health_status(): string {
		return $this->self_test_passes() ? 'HEALTHY' : 'ERROR';
	}

	/** Both calculators against known values (the `self_test` action). */
	private function self_test_passes(): bool {
		if ( null !== $this->self_test ) {
			return (bool) ( $this->self_test )();
		}
		$old   = WM_K9_Admission_Validator::check_eligibility( '2024-05-01', '2025-10-01', '276098100123456', true );
		$young = WM_K9_Admission_Validator::check_eligibility( '2025-01-01', '2025-10-01', '276098100123456', true );
		$pass  = WM_K9_Scoring_Engine::evaluate( [ 'leinenfuehrigkeit' => 14, 'freifolge' => 13.5, 'sitz_bewegung' => 9, 'ablegen_herankommen' => 8.5, 'ablegen_ablenkung' => 9 ], true );
		return $old['eligible'] && 17 === $old['age_months'] && ! $young['eligible'] && 'BESTANDEN' === $pass['verdict'] && 54.0 === $pass['teil_a_points'];
	}

	public function get_sbom_data(): array {
		return [
			'bom-ref'     => 'pkg:wordpress/wm-k9-bh-exam-planner@' . $this->get_version(),
			'type'        => 'application',
			'name'        => $this->get_name(),
			'version'     => $this->get_version(),
			'description' => $this->get_description(),
			'scope'       => 'required',
			'hashes'      => [
				[
					'alg'     => 'SHA-256',
					'content' => hash_file( 'sha256', dirname( __DIR__ ) . '/wm-k9-bh-exam-planner.php' ),
				],
			],
			'licenses'    => [
				[
					'license' => [
						'id' => 'GPL-2.0-or-later',
					],
				],
			],
			'properties'  => [
				[
					'name'  => 'wm:features:rules',
					'value' => sprintf( 'Hardcoded values: minimum age %d months, %d-digit transponder code, handler SKN, Teil A %d points with %d to pass (%s layout)', WM_K9_Admission_Validator::MIN_AGE_MONTHS, WM_K9_Admission_Validator::CHIP_DIGITS, (int) WM_K9_Scoring_Engine::MAX_POINTS_TEIL_A, (int) WM_K9_Scoring_Engine::PASSING_POINTS_TEIL_A, WM_K9_Scoring_Engine::RULE_EDITION ),
				],
			],
		];
	}

	public function get_dependencies(): array {
		return [];
	}

	public function get_settings_url(): ?string {
		return admin_url( 'admin.php?page=wm-k9-planner' );
	}

	/**
	 * The plugin stores no exams; until 2026-09-14 this reported a literal `exams: 0`.
	 */
	public function get_telemetry_metrics(): array {
		return [
			'status'      => $this->get_health_status(),
			'shortcodes'  => 2,
			'rest_routes' => 2,
		];
	}

	/**
	 * `self_test` runs both calculations against known values. Every other action is unknown and
	 * says so; until 2026-09-14 any action name returned "executed successfully".
	 */
	public function execute_action( string $action, array $params = [] ): array {
		if ( 'self_test' === $action ) {
			$ok = $this->self_test_passes();
			return [
				'success' => $ok,
				'message' => $ok
					? 'Zulassungsprüfung und Punkterechner liefern die erwarteten Werte.'
					: 'Selbsttest fehlgeschlagen: ein Rechner liefert abweichende Werte.',
			];
		}
		// Plain text: the hub escapes where it renders. Until 2026-09-15 esc_html() here, so the hub CLI printed `&quot;`.
		return [
			'success' => false,
			'message' => sprintf( 'Unbekannte Spoke-Aktion "%s".', $action ),
		];
	}
}
