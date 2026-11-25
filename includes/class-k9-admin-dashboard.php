<?php
/**
 * WM K9 BH Exam Planner — Admin Dashboard
 *
 * An information screen: the plugin stores no dogs, trials or scores, so the screen explains what
 * the two calculators check and where they run. Until 2026-09-14 it showed three KPI tiles fixed at
 * 0 ("Registered Dogs", "Eligible", "Exams Planned") and three tabs with placeholder comments
 * promising registration lists, score sheets and settings that do not exist.
 *
 * @package WM_K9_BH_Exam_Planner
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WM_K9_Admin_Dashboard {
	public const SETTINGS_GROUP          = 'wm_k9_planner_options';
	public const OPT_DELETE_ON_UNINSTALL = 'wm_k9_delete_data_on_uninstall';

	private static ?WM_K9_Admin_Dashboard $instance = null;

	public static function init(): void {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
	}

	/**
	 * Entrypoint for unified WM Suite Hub dispatcher.
	 */
	public static function render_page(): void {
		self::init();
		if ( null !== self::$instance ) {
			self::$instance->render_dashboard();
		}
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Register opt-in uninstallation setting (`wm_k9_delete_data_on_uninstall`, default `'0'`).
	 */
	public function register_settings(): void {
		register_setting(
			'wm_k9_planner_options',
			'wm_k9_delete_data_on_uninstall',
			[
				'type'              => 'boolean',
				'sanitize_callback' => [ self::class, 'sanitize_uninstall_flag' ],
				'default'           => '0',
			]
		);
	}

	public static function sanitize_uninstall_flag( mixed $value ): string {
		return in_array( $value, [ 1, '1', true, 'true', 'on' ], true ) ? '1' : '0';
	}

	/**
	 * Persist the opt-in uninstallation flag explicitly.
	 */
	public static function update_uninstall_flag( mixed $value ): bool {
		return update_option( 'wm_k9_delete_data_on_uninstall', self::sanitize_uninstall_flag( $value ), false );
	}

	/**
	 * Family menu icon: a monochrome hub-and-spoke mark, filled shapes only so the WordPress
	 * svg-painter can recolour it with the admin colour scheme. Placeholder until the family
	 * icon is decided (hackaton plan D12).
	 */
	public static function family_menu_icon(): string {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="10" r="2.6" fill="black"/><circle cx="10" cy="3" r="1.8" fill="black"/><circle cx="16.1" cy="13.5" r="1.8" fill="black"/><circle cx="3.9" cy="13.5" r="1.8" fill="black"/><rect x="9.3" y="4.5" width="1.4" height="3.4" fill="black"/><rect x="9.3" y="12.1" width="1.4" height="3.4" fill="black" transform="rotate(-60 10 10)"/><rect x="9.3" y="12.1" width="1.4" height="3.4" fill="black" transform="rotate(60 10 10)"/></svg>';
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'K9 BH Exam Planner', 'wm-k9-bh-exam-planner' ),
			__( 'BH Exam Planner', 'wm-k9-bh-exam-planner' ),
			'manage_options',
			'wm-k9-planner',
			[ $this, 'render_dashboard' ],
			self::family_menu_icon(),
			84
		);
	}

	/**
	 * The two family sheets on the plugin's own screen. The hook contains the page slug both
	 * standalone ("toplevel_page_wm-k9-planner") and under a parent menu.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, 'wm-k9-planner' ) ) {
			return;
		}

		$root    = dirname( __DIR__ ) . '/assets/css/';
		$version = static fn( string $file ): string => is_file( $root . $file ) ? (string) filemtime( $root . $file ) : WM_K9_PLANNER_VERSION;
		wp_enqueue_style(
			'wm-admin-tokens',
			plugin_dir_url( WM_K9_PLANNER_FILE ) . 'assets/css/wm-admin-tokens.css',
			[],
			$version( 'wm-admin-tokens.css' )
		);
		wp_enqueue_style(
			'wm-admin-ui',
			plugin_dir_url( WM_K9_PLANNER_FILE ) . 'assets/css/wm-admin-ui.css',
			[ 'wm-admin-tokens' ],
			$version( 'wm-admin-ui.css' )
		);
	}

	/**
	 * Tab slug => label and dashicon. Unknown slugs fall back to the overview.
	 *
	 * @return array<string, array{label: string, icon: string}>
	 */
	private function tabs(): array {
		return [
			'overview'   => [ 'label' => __( 'Übersicht', 'wm-k9-bh-exam-planner' ), 'icon' => 'dashicons-info-outline' ],
			'examinees'  => [ 'label' => __( 'Zulassung', 'wm-k9-bh-exam-planner' ), 'icon' => 'dashicons-yes-alt' ],
			'evaluation' => [ 'label' => __( 'Bewertung', 'wm-k9-bh-exam-planner' ), 'icon' => 'dashicons-clipboard' ],
			'settings'   => [ 'label' => __( 'Einstellungen', 'wm-k9-bh-exam-planner' ), 'icon' => 'dashicons-admin-generic' ],
		];
	}

	public function render_dashboard(): void {
		if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs = $this->tabs();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab routing, checked against the tab list.
		$active_tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'overview' ) );
		if ( ! isset( $tabs[ $active_tab ] ) ) {
			$active_tab = 'overview';
		}
		?>
		<div class="wrap wm-admin">
			<!-- Header -->
			<header class="wm-admin-header">
				<div class="wm-header-brand">
					<div class="wm-header-icon">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/><path d="M11 7h2v6h-2zm0 8h2v2h-2z"/></svg>
					</div>
					<div>
						<h1><?php esc_html_e( 'WM K9 BH Exam Planner', 'wm-k9-bh-exam-planner' ); ?></h1>
						<p class="wm-subtitle"><?php esc_html_e( 'Zulassungs-Vorabprüfung &amp; Punkterechner für die Begleithundeprüfung (BH/VT)', 'wm-k9-bh-exam-planner' ); ?></p>
					</div>
				</div>
				<div class="wm-header-badges">
					<span class="wm-badge-family" title="<?php esc_attr_e( 'Part of the WM Suite plugin family', 'wm-k9-bh-exam-planner' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="4" r="2"/><circle cx="19" cy="16" r="2"/><circle cx="5" cy="16" r="2"/><path d="M12 7v2M13.9 13.2l3.4 2M10.1 13.2l-3.4 2"/></svg>
						<?php esc_html_e( 'WM Suite', 'wm-k9-bh-exam-planner' ); ?>
						<span class="wm-badge-family-state"><?php echo defined( 'WM_SUITE_HUB_VERSION' ) ? esc_html__( 'Hub', 'wm-k9-bh-exam-planner' ) : esc_html__( 'Standalone', 'wm-k9-bh-exam-planner' ); ?></span>
					</span>
					<span class="wm-badge wm-badge-ghost"><?php esc_html_e( 'KEINE GESPEICHERTEN DATEN', 'wm-k9-bh-exam-planner' ); ?></span>
				</div>
			</header>

			<!-- Tabs -->
			<nav class="wm-nav-tabs" aria-label="<?php esc_attr_e( 'Module Tabs', 'wm-k9-bh-exam-planner' ); ?>">
				<?php foreach ( $tabs as $slug => $tab ) : ?>
				<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'wm-k9-planner', 'tab' => $slug ], admin_url( 'admin.php' ) ) ); ?>" class="wm-tab-link <?php echo $active_tab === $slug ? 'active' : ''; ?>"<?php echo $active_tab === $slug ? ' aria-current="page"' : ''; ?>>
					<span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>" aria-hidden="true"></span> <?php echo esc_html( $tab['label'] ); ?>
				</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( 'overview' === $active_tab ) : ?>
			<div class="wm-tab-content">
				<section class="wm-quick-guide" id="k9-guide">
					<div class="wm-quick-guide-title">
						<span><span class="dashicons dashicons-lightbulb" aria-hidden="true"></span> <?php esc_html_e( 'Was dieses Plugin tut', 'wm-k9-bh-exam-planner' ); ?></span>
					</div>
					<p class="wm-quick-guide-desc">
						<?php
						// Until 2026-09-15 this said both calculators also run as a WP-CLI command; only the admission check does.
						echo esc_html(
							sprintf(
								/* translators: 1: minimum age in months, 2: number of digits of the transponder code */
								__( 'Zwei Rechner für die Begleithundeprüfung: eine Zulassungs-Vorabprüfung (Mindestalter %1$d Monate am Prüfungstag, %2$d-stelliger Transpondercode, Sachkundenachweis des Hundeführers) und ein Punkterechner für Teil A mit dem Ergebnis von Teil B. Beide laufen als Shortcode auf einer Seite und als REST-Route; die Zulassungsprüfung zusätzlich als WP-CLI-Befehl. Das Plugin speichert keine Hunde, Termine oder Bewertungen; diese Seite hat daher keine Zahlen.', 'wm-k9-bh-exam-planner' ),
								WM_K9_Admission_Validator::MIN_AGE_MONTHS,
								WM_K9_Admission_Validator::CHIP_DIGITS
							)
						);
						?>
					</p>
				</section>

				<div class="wm-grid-2">
					<div class="wm-card">
						<h2><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> <?php esc_html_e( 'Zulassung', 'wm-k9-bh-exam-planner' ); ?></h2>
						<ul>
							<li><?php esc_html_e( 'Shortcode:', 'wm-k9-bh-exam-planner' ); ?> <code>[wm_k9_zulassungscheck]</code></li>
							<li><?php esc_html_e( 'REST:', 'wm-k9-bh-exam-planner' ); ?> <code>POST /wp-json/wm-k9/v1/zulassung</code></li>
							<li><?php esc_html_e( 'WP-CLI:', 'wm-k9-bh-exam-planner' ); ?> <code>wp k9 check-eligibility</code></li>
						</ul>
					</div>
					<div class="wm-card">
						<h2><span class="dashicons dashicons-clipboard" aria-hidden="true"></span> <?php esc_html_e( 'Bewertung', 'wm-k9-bh-exam-planner' ); ?></h2>
						<ul>
							<li><?php esc_html_e( 'Shortcode:', 'wm-k9-bh-exam-planner' ); ?> <code>[wm_k9_richterbogen]</code></li>
							<li><?php esc_html_e( 'REST:', 'wm-k9-bh-exam-planner' ); ?> <code>POST /wp-json/wm-k9/v1/bewertung</code></li>
						</ul>
					</div>
				</div>
			</div>

			<?php elseif ( 'examinees' === $active_tab ) : ?>
			<div class="wm-tab-content">
				<div class="wm-card">
					<h2><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> <?php esc_html_e( 'Was die Zulassungs-Vorabprüfung prüft', 'wm-k9-bh-exam-planner' ); ?></h2>
					<ol>
						<li>
							<?php
							/* translators: %d: minimum age in months */
							echo esc_html( sprintf( __( 'Alter am Prüfungstag: mindestens %d volle Monate zwischen Geburtsdatum und Prüfungsdatum. Ein Monat ist am Tag mit derselben Zahl wie der Geburtstag vollendet, in kürzeren Monaten am Ersten des Folgemonats (Lesart von § 187 Abs. 2, § 188 Abs. 2 und 3 BGB; die Prüfungsordnung nennt keine Zählregel).', 'wm-k9-bh-exam-planner' ), WM_K9_Admission_Validator::MIN_AGE_MONTHS ) );
							?>
						</li>
						<li>
							<?php
							/* translators: %d: number of digits of the transponder code */
							echo esc_html( sprintf( __( 'Transpondercode: nach Entfernen aller Nicht-Ziffern genau %d Ziffern. Es ist eine Formatprüfung; ob der Chip existiert oder zum Hund gehört, wird nicht geprüft.', 'wm-k9-bh-exam-planner' ), WM_K9_Admission_Validator::CHIP_DIGITS ) );
							?>
						</li>
						<li><?php esc_html_e( 'Sachkundenachweis: so, wie der Aufrufer ihn angibt.', 'wm-k9-bh-exam-planner' ); ?></li>
					</ol>
					<p style="color: var(--wm-text-secondary);"><?php esc_html_e( 'Die Werte sind fest im Code hinterlegt. Ein Mindestalter von fünfzehn Monaten nennt der VDH für die Begleithundprüfung; die FCI-Prüfungsordnung überlässt es der Landesorganisation (nicht unter 12 Monaten). Über die Zulassung entscheidet der ausrichtende Verein.', 'wm-k9-bh-exam-planner' ); ?></p>
				</div>
			</div>

			<?php elseif ( 'evaluation' === $active_tab ) : ?>
			<div class="wm-tab-content">
				<div class="wm-card">
					<h2><span class="dashicons dashicons-clipboard" aria-hidden="true"></span> <?php esc_html_e( 'Wie der Punkterechner rechnet', 'wm-k9-bh-exam-planner' ); ?></h2>
					<ol>
						<li>
							<?php
							$wm_k9_parts = [];
							foreach ( WM_K9_Scoring_Engine::EXERCISES as $wm_k9_exercise ) {
								$wm_k9_parts[] = sprintf( '%s (max. %d)', $wm_k9_exercise['label'], (int) $wm_k9_exercise['max'] );
							}
							echo esc_html(
								sprintf(
									/* translators: 1: list of exercises with maximum points, 2: maximum total, 3: points needed to pass */
									__( 'Teil A: %1$s; jeder Wert wird auf seinen Bereich begrenzt und die Summe (max. %2$d) mit der Grenze verglichen, bevor sie für die Anzeige gerundet wird. Bestanden ab %3$d Punkten.', 'wm-k9-bh-exam-planner' ),
									implode( ', ', $wm_k9_parts ),
									(int) WM_K9_Scoring_Engine::MAX_POINTS_TEIL_A,
									(int) WM_K9_Scoring_Engine::PASSING_POINTS_TEIL_A
								)
							);
							?>
						</li>
						<li><?php esc_html_e( 'Teil B (Verkehrsteil): wird als bestanden oder nicht bestanden übergeben, nicht berechnet.', 'wm-k9-bh-exam-planner' ); ?></li>
						<li><?php esc_html_e( 'Ergebnis: BESTANDEN nur bei Teil A und Teil B; eine Disqualifikation (nur über REST) setzt alles auf DISQUALIFIZIERT.', 'wm-k9-bh-exam-planner' ); ?></li>
					</ol>
					<p style="color: var(--wm-text-secondary);"><?php esc_html_e( 'Übungen und Höchstpunkte stammen aus der FCI-Prüfungsordnung 2019. Seit dem 01.01.2025 gilt die FCI-Prüfungsordnung 2025: Leinenführigkeit 30 Punkte, Sitz 10, Ablegen in Verbindung mit Herankommen 10, Ablegen unter Ablenkung 10, keine Freifolge; bestanden weiterhin ab 70 %. Der Rechner ist noch nicht umgestellt.', 'wm-k9-bh-exam-planner' ); ?></p>
				</div>
			</div>

			<?php elseif ( 'settings' === $active_tab ) : ?>
			<div class="wm-tab-content">
				<div class="wm-card">
					<h2><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span> <?php esc_html_e( 'Deinstallation', 'wm-k9-bh-exam-planner' ); ?></h2>
					<form method="post" action="options.php">
						<?php
						if ( function_exists( 'settings_fields' ) ) {
							settings_fields( 'wm_k9_planner_options' );
						}
						$wm_k9_delete_flag = function_exists( 'get_option' )
							? (string) get_option( 'wm_k9_delete_data_on_uninstall', '0' )
							: '0';
						?>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><?php esc_html_e( 'Datenbereinigung', 'wm-k9-bh-exam-planner' ); ?></th>
								<td>
									<label for="wm_k9_delete_data_on_uninstall">
										<input
											type="checkbox"
											id="wm_k9_delete_data_on_uninstall"
											name="<?php echo esc_attr( self::OPT_DELETE_ON_UNINSTALL ); ?>"
											value="1"
											<?php echo '1' === $wm_k9_delete_flag ? 'checked="checked"' : ''; ?>
										/>
										<?php esc_html_e( 'Plugin-Optionen und Transients beim endgültigen Löschen des Plugins entfernen', 'wm-k9-bh-exam-planner' ); ?>
									</label>
									<p class="description" style="color: var(--wm-text-secondary);">
										<?php esc_html_e( 'Standardmäßig bleiben Einstellungen bei der Deinstallation erhalten. Aktivieren Sie diese Option ausdrücklich, wenn beim Löschen des Plugins alle eigenen Options- und Transient-Einträge entfernt werden sollen.', 'wm-k9-bh-exam-planner' ); ?>
									</p>
								</td>
							</tr>
						</table>
						<p class="submit">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Änderungen speichern', 'wm-k9-bh-exam-planner' ); ?></button>
						</p>
					</form>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
