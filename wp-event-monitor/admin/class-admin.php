<?php
/**
 * WEM_Admin
 *
 * Handles admin interface and menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEM_Admin {

	/**
	 * Initialize admin
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_wem_add_source', array( __CLASS__, 'handle_add_source' ) );
		add_action( 'admin_post_wem_update_source', array( __CLASS__, 'handle_update_source' ) );
		add_action( 'admin_post_wem_delete_source', array( __CLASS__, 'handle_delete_source' ) );
		add_action( 'admin_post_wem_toggle_source', array( __CLASS__, 'handle_toggle_source' ) );
		add_action( 'admin_post_wem_scrape_source', array( __CLASS__, 'handle_scrape_source' ) );
		add_action( 'admin_post_wem_scrape_all_sources', array( __CLASS__, 'handle_scrape_all_sources' ) );
		add_action( 'admin_post_wem_preview_source', array( __CLASS__, 'handle_preview_source' ) );
		add_action( 'admin_post_wem_add_keyword', array( __CLASS__, 'handle_add_keyword' ) );
		add_action( 'admin_post_wem_delete_keyword', array( __CLASS__, 'handle_delete_keyword' ) );
		add_action( 'admin_post_wem_clear_log', array( __CLASS__, 'handle_clear_log' ) );
		add_action( 'admin_post_wem_save_settings', array( __CLASS__, 'handle_save_settings' ) );
	}

	/**
	 * Add admin menu
	 */
	public static function add_admin_menu() {
		add_menu_page(
			__( 'Event Monitor', 'wp-event-monitor' ),
			__( 'Event Monitor', 'wp-event-monitor' ),
			'manage_options',
			'wem-dashboard',
			array( __CLASS__, 'render_page' ),
			'dashicons-rss',
			76
		);
	}

	/**
	 * Enqueue admin-only assets.
	 *
	 * @param string $hook Current admin page hook
	 */
	public static function enqueue_admin_assets( $hook ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'wem-dashboard' !== $page ) {
			return;
		}

		wp_enqueue_media();
	}

	/**
	 * Render main admin page
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'wp-event-monitor' ) );
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'sources';
		?>
		<div class="wrap wem-admin">
			<h1><?php echo esc_html( __( 'Event Monitor', 'wp-event-monitor' ) ); ?></h1>

			<nav class="nav-tab-wrapper wem-tabs">
				<a href="?page=wem-dashboard&tab=instructions" class="nav-tab <?php echo $tab === 'instructions' ? 'nav-tab-active' : ''; ?>" style="background: #fff8e5; font-weight: bold;">
					<?php echo esc_html( __( '❓ Instructions', 'wp-event-monitor' ) ); ?>
				</a>
				<a href="?page=wem-dashboard&tab=sources" class="nav-tab <?php echo $tab === 'sources' ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( __( 'Sources', 'wp-event-monitor' ) ); ?>
				</a>
				<a href="?page=wem-dashboard&tab=keywords" class="nav-tab <?php echo $tab === 'keywords' ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( __( 'Keywords', 'wp-event-monitor' ) ); ?>
				</a>
				<a href="?page=wem-dashboard&tab=settings" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( __( 'Settings', 'wp-event-monitor' ) ); ?>
				</a>
				<a href="?page=wem-dashboard&tab=log" class="nav-tab <?php echo $tab === 'log' ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( __( 'Activity Log', 'wp-event-monitor' ) ); ?>
				</a>
			</nav>

			<div class="wem-content">
				<?php
				switch ( $tab ) {
					case 'instructions':
						self::render_instructions_page();
						break;
					case 'keywords':
						self::render_keywords_page();
						break;
					case 'settings':
						self::render_settings_page();
						break;
					case 'log':
						self::render_log_page();
						break;
					case 'sources':
					default:
						self::render_sources_page();
						break;
				}
				?>
			</div>
		</div>

		<style>
			.wem-admin {
				max-width: 1200px;
			}
			.wem-tabs {
				margin-bottom: 20px;
			}
			.wem-content {
				background: #fff;
				padding: 20px;
				border: 1px solid #ddd;
				border-radius: 4px;
			}
			.wem-form {
				max-width: 720px;
				margin-bottom: 30px;
			}
			.wem-form-group {
				margin-bottom: 15px;
			}
			.wem-fallback-gallery {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				margin: 10px 0;
			}
			.wem-fallback-gallery-item {
				position: relative;
				width: 96px;
				padding: 6px;
				border: 1px solid #dcdcde;
				background: #f6f7f7;
				border-radius: 4px;
				text-align: center;
			}
			.wem-fallback-gallery-item img {
				display: block;
				width: 84px;
				height: 84px;
				object-fit: cover;
				margin: 0 auto 6px;
				border-radius: 3px;
			}
			.wem-fallback-gallery-item .button-link-delete {
				font-size: 12px;
				line-height: 1.2;
			}
			.wem-form-group label {
				display: block;
				margin-bottom: 5px;
				font-weight: 500;
			}
			.wem-form-group input[type="text"],
			.wem-form-group input[type="url"],
			.wem-form-group select,
			.wem-form-group textarea {
				width: 100%;
				padding: 8px 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
				font-size: 14px;
			}
			.wem-form-group small {
				display: block;
				margin-top: 5px;
				color: #666;
			}
			.wem-table {
				width: 100%;
				border-collapse: collapse;
				margin-top: 20px;
			}
			.wem-table th,
			.wem-table td {
				padding: 12px;
				text-align: left;
				border-bottom: 1px solid #ddd;
			}
			.wem-table th {
				background: #f9f9f9;
				font-weight: 600;
			}
			.wem-table tr:hover {
				background: #f5f5f5;
			}
			.wem-actions {
				display: flex;
				gap: 10px;
				flex-wrap: wrap;
			}
			.wem-actions a,
			.wem-actions form {
				margin: 0;
			}
			.wem-btn {
				padding: 6px 12px;
				background: #0073aa;
				color: #fff;
				border: 1px solid #0073aa;
				border-radius: 4px;
				cursor: pointer;
				text-decoration: none;
				font-size: 13px;
				display: inline-block;
			}
			.wem-btn:hover {
				background: #005a87;
				border-color: #005a87;
				color: #fff;
				text-decoration: none;
			}
			.wem-btn.danger {
				background: #dc3545;
				border-color: #dc3545;
			}
			.wem-btn.danger:hover {
				background: #c82333;
				border-color: #c82333;
			}
			.wem-btn.secondary {
				background: #6c757d;
				border-color: #6c757d;
			}
			.wem-btn.secondary:hover {
				background: #5a6268;
				border-color: #5a6268;
			}
			.wem-status {
				display: inline-block;
				padding: 4px 8px;
				border-radius: 3px;
				font-size: 12px;
				font-weight: 500;
			}
			.wem-status.active {
				background: #d4edda;
				color: #155724;
			}
			.wem-status.inactive {
				background: #f8d7da;
				color: #721c24;
			}
			.notice {
				margin-bottom: 20px;
			}
		</style>
		<?php
	}

	/**
	 * Render instructions page
	 */
	private static function render_instructions_page() {
		?>
		<div style="max-width: 900px;">
			<h2><?php echo esc_html( __( '🚀 Getting Started - 4 Simple Steps', 'wp-event-monitor' ) ); ?></h2>

			<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin-bottom: 30px;">
				<strong><?php echo esc_html( __( '⏱️ Time needed: 15 minutes', 'wp-event-monitor' ) ); ?></strong>
			</div>

			<!-- Step 1 -->
			<div style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #0073aa;">
				<h3 style="margin-top: 0; color: #0073aa;">📍 Step 1: Add Event Websites (5 min)</h3>
				<p><?php echo esc_html( __( 'Go to the "Sources" tab and add your event websites.', 'wp-event-monitor' ) ); ?></p>
				<div style="background: #fff; padding: 12px; border-radius: 3px; margin: 10px 0; font-family: monospace; font-size: 12px;">
					<strong><?php echo esc_html( __( 'Example:', 'wp-event-monitor' ) ); ?></strong><br>
					URL: https://www.eventbrite.de/<br>
					Label: EventBrite Germany<br>
					CSS-Selector: .event-item (optional)
				</div>
				<p><strong><?php echo esc_html( __( 'Repeat for all 20 websites', 'wp-event-monitor' ) ); ?></strong></p>
				<a href="?page=wem-dashboard&tab=sources" class="wem-btn" style="background: #0073aa;">
					<?php echo esc_html( __( 'Go to Sources Tab →', 'wp-event-monitor' ) ); ?>
				</a>
			</div>

			<!-- Step 2 -->
			<div style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #28a745;">
				<h3 style="margin-top: 0; color: #28a745;">🔑 Step 2: Define Keywords (5 min)</h3>
				<p><?php echo esc_html( __( 'Go to the "Keywords" tab and add your search keywords. Only events matching these keywords will be posted.', 'wp-event-monitor' ) ); ?></p>
				<div style="background: #fff; padding: 12px; border-radius: 3px; margin: 10px 0; font-family: monospace; font-size: 12px;">
					<strong><?php echo esc_html( __( 'Example:', 'wp-event-monitor' ) ); ?></strong><br>
					✓ "conference" (Plain Text)<br>
					✓ "workshop" (Plain Text)<br>
					✓ "seminar" (Plain Text)<br>
					✓ "/kongress|summit/" (Regex)
				</div>
				<p><strong><?php echo esc_html( __( '💡 Tip: Use regex for complex patterns', 'wp-event-monitor' ) ); ?></strong></p>
				<a href="?page=wem-dashboard&tab=keywords" class="wem-btn" style="background: #28a745;">
					<?php echo esc_html( __( 'Go to Keywords Tab →', 'wp-event-monitor' ) ); ?>
				</a>
			</div>

			<!-- Step 3 -->
			<div style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #6f42c1;">
				<h3 style="margin-top: 0; color: #6f42c1;">⏰ Step 3: Configure Schedule (5 min)</h3>
				<p><?php echo esc_html( __( 'Go to the "Settings" tab and set your scrape interval.', 'wp-event-monitor' ) ); ?></p>
				<div style="background: #fff; padding: 12px; border-radius: 3px; margin: 10px 0;">
					<p><strong><?php echo esc_html( __( 'Choose:', 'wp-event-monitor' ) ); ?></strong></p>
					<ul style="margin: 5px 0;">
						<li>Interval: Weekly / Bi-weekly / Monthly</li>
						<li>Day: Monday, Tuesday, etc. (or day of month)</li>
						<li>Time: 08:00, 12:00, etc.</li>
					</ul>
				</div>
				<p><?php echo esc_html( __( 'After saving, you will see when the next scrape is scheduled.', 'wp-event-monitor' ) ); ?></p>
				<a href="?page=wem-dashboard&tab=settings" class="wem-btn" style="background: #6f42c1;">
					<?php echo esc_html( __( 'Go to Settings Tab →', 'wp-event-monitor' ) ); ?>
				</a>
			</div>

			<!-- Step 4 -->
			<div style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
				<h3 style="margin-top: 0; color: #dc3545;">✅ Step 4: Test (2 min)</h3>
				<p><?php echo esc_html( __( 'Go back to the "Sources" tab and click "Scrape Now" on one website to test.', 'wp-event-monitor' ) ); ?></p>
				<p><strong><?php echo esc_html( __( 'Then check:', 'wp-event-monitor' ) ); ?></strong></p>
				<ol>
					<li><?php echo esc_html( __( 'Event Monitor → Activity Log: Do you see a new entry?', 'wp-event-monitor' ) ); ?></li>
					<li><?php echo esc_html( __( 'WordPress Posts → Drafts: Are there new draft posts?', 'wp-event-monitor' ) ); ?></li>
				</ol>
				<p style="color: #28a745;"><strong>✓ If YES → Everything works! Your plugin is ready!</strong></p>
				<p style="color: #dc3545;"><strong>✗ If NO → Check Activity Log for error messages</strong></p>
				<a href="?page=wem-dashboard&tab=sources" class="wem-btn" style="background: #dc3545;">
					<?php echo esc_html( __( 'Go to Sources & Test →', 'wp-event-monitor' ) ); ?>
				</a>
			</div>

			<!-- How it Works -->
			<hr style="margin: 40px 0; border: none; border-top: 2px solid #ddd;">

			<h2><?php echo esc_html( __( '🔄 How It Works', 'wp-event-monitor' ) ); ?></h2>

			<div style="background: #e8f4f8; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
				<h4><?php echo esc_html( __( 'Every scheduled scrape:', 'wp-event-monitor' ) ); ?></h4>
				<ol>
					<li><strong><?php echo esc_html__( 'Fetch:', 'wp-event-monitor' ); ?></strong> <?php echo esc_html__( 'All 20 websites are fetched', 'wp-event-monitor' ); ?></li>
					<li><strong><?php echo esc_html__( 'Parse:', 'wp-event-monitor' ); ?></strong> <?php echo esc_html__( 'Events are extracted from the HTML', 'wp-event-monitor' ); ?></li>
					<li><strong><?php echo esc_html__( 'Filter:', 'wp-event-monitor' ); ?></strong> <?php echo esc_html__( 'Events are matched against your keywords', 'wp-event-monitor' ); ?></li>
					<li><strong><?php echo esc_html__( 'Post:', 'wp-event-monitor' ); ?></strong> <?php echo esc_html__( 'Matching events are created as WordPress draft posts', 'wp-event-monitor' ); ?></li>
					<li><strong><?php echo esc_html__( 'Log:', 'wp-event-monitor' ); ?></strong> <?php echo esc_html__( 'Everything is logged in the Activity Log', 'wp-event-monitor' ); ?></li>
				</ol>
			</div>

			<!-- What to do next -->
			<h2><?php echo esc_html( __( '📋 What to do next:', 'wp-event-monitor' ) ); ?></h2>

			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
				<div style="background: #f8f9fa; padding: 15px; border-radius: 4px;">
					<h4>✏️ Manage Events</h4>
					<p>Review draft posts in WordPress Posts → Drafts, edit them, and publish when ready.</p>
				</div>
				<div style="background: #f8f9fa; padding: 15px; border-radius: 4px;">
					<h4>📊 Monitor Activity</h4>
					<p>Check the Activity Log regularly to see how many events are being found and posted.</p>
				</div>
				<div style="background: #f8f9fa; padding: 15px; border-radius: 4px;">
					<h4>🔧 Adjust Settings</h4>
					<p>If you're getting too many or too few events, adjust your keywords in the Keywords tab.</p>
				</div>
				<div style="background: #f8f9fa; padding: 15px; border-radius: 4px;">
					<h4>⚙️ Enable Real Cron</h4>
					<p>For reliable scraping, set up a real server cron job (SSH/cPanel). See README.md for details.</p>
				</div>
			</div>

			<!-- Tips -->
			<div style="background: #fffbea; border: 1px solid #ffc107; padding: 15px; border-radius: 4px;">
				<h3>💡 Pro Tips</h3>
				<ul>
					<li><strong><?php echo esc_html__( 'CSS Selectors:', 'wp-event-monitor' ); ?></strong> <?php echo esc_html__( 'Use browser F12 → Inspect to find the right selector for event elements', 'wp-event-monitor' ); ?></li>
					<li><strong><?php echo esc_html__( 'Keywords:', 'wp-event-monitor' ); ?></strong> <?php echo esc_html__( 'Start with broad keywords and make them more specific if you get too many false positives', 'wp-event-monitor' ); ?></li>
					<li><strong><?php echo esc_html__( 'Testing:', 'wp-event-monitor' ); ?></strong> <?php echo esc_html__( 'Use "Scrape Now" frequently to test your setup before enabling automatic scrapes', 'wp-event-monitor' ); ?></li>
					<li><strong><?php echo esc_html__( 'Review:', 'wp-event-monitor' ); ?></strong> <?php echo esc_html__( 'Always review draft posts before publishing them to ensure quality', 'wp-event-monitor' ); ?></li>
				</ul>
			</div>

			<!-- Real Cron Setup -->
			<hr style="margin: 40px 0; border: none; border-top: 2px solid #ddd;">

			<h2><?php echo esc_html( __( '⚙️ Setup Real Cron (Optional but Recommended)', 'wp-event-monitor' ) ); ?></h2>

			<p><?php echo esc_html( __( 'By default, scraping runs when WordPress Cron triggers (on website visits). For reliable scraping at exact times, set up a real server cron job:', 'wp-event-monitor' ) ); ?></p>

			<!-- SSH Option -->
			<div style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #17a2b8;">
				<h3 style="margin-top: 0; color: #17a2b8;">🔧 Option A: Linux/SSH Server</h3>
				<p><strong><?php echo esc_html( __( 'If you have SSH access:', 'wp-event-monitor' ) ); ?></strong></p>
				<ol>
					<li><?php echo esc_html( __( 'Connect via SSH:', 'wp-event-monitor' ) ); ?>
						<div style="background: #fff; padding: 10px; border-radius: 3px; margin: 10px 0; font-family: monospace; font-size: 12px;">
							ssh user@example.com
						</div>
					</li>
					<li><?php echo esc_html( __( 'Open crontab:', 'wp-event-monitor' ) ); ?>
						<div style="background: #fff; padding: 10px; border-radius: 3px; margin: 10px 0; font-family: monospace; font-size: 12px;">
							crontab -e
						</div>
					</li>
					<li><?php echo esc_html( __( 'Add this line (example: every Monday at 08:00):', 'wp-event-monitor' ) ); ?>
						<div style="background: #fff; padding: 10px; border-radius: 3px; margin: 10px 0; font-family: monospace; font-size: 12px;">
							0 8 * * 1 /usr/bin/php /var/www/html/wp-cron.php
						</div>
						<small><?php echo esc_html( __( '(Adjust path /var/www/html to your WordPress installation)', 'wp-event-monitor' ) ); ?></small>
					</li>
					<li><?php echo esc_html( __( 'Save & exit (vi: press :wq)', 'wp-event-monitor' ) ); ?></li>
					<li><?php echo esc_html( __( 'In WordPress, edit wp-config.php and add:', 'wp-event-monitor' ) ); ?>
						<div style="background: #fff; padding: 10px; border-radius: 3px; margin: 10px 0; font-family: monospace; font-size: 12px;">
							define( 'DISABLE_WP_CRON', true );
						</div>
					</li>
				</ol>
			</div>

			<!-- cPanel Option -->
			<div style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
				<h3 style="margin-top: 0; color: #ffc107;">📋 Option B: cPanel (Shared Hosting)</h3>
				<p><strong><?php echo esc_html( __( 'If you have cPanel access:', 'wp-event-monitor' ) ); ?></strong></p>
				<ol>
					<li><?php echo esc_html( __( 'Login to cPanel', 'wp-event-monitor' ) ); ?></li>
					<li><?php echo esc_html( __( 'Find "Cron Jobs" section', 'wp-event-monitor' ) ); ?></li>
					<li><?php echo esc_html( __( 'Click "Add New Cron Job"', 'wp-event-monitor' ) ); ?></li>
					<li><?php echo esc_html( __( 'Enter Command:', 'wp-event-monitor' ) ); ?>
						<div style="background: #fff; padding: 10px; border-radius: 3px; margin: 10px 0; font-family: monospace; font-size: 12px;">
							/usr/bin/php /home/username/public_html/wp-cron.php
						</div>
						<small><?php echo esc_html( __( '(Replace username and path with your cPanel username)', 'wp-event-monitor' ) ); ?></small>
					</li>
					<li><?php echo esc_html( __( 'Common Settings: Select "Once per week" or your preferred interval', 'wp-event-monitor' ) ); ?></li>
					<li><?php echo esc_html( __( 'Click "Add Cron Job"', 'wp-event-monitor' ) ); ?></li>
					<li><?php echo esc_html( __( 'Edit wp-config.php and add:', 'wp-event-monitor' ) ); ?>
						<div style="background: #fff; padding: 10px; border-radius: 3px; margin: 10px 0; font-family: monospace; font-size: 12px;">
							define( 'DISABLE_WP_CRON', true );
						</div>
					</li>
				</ol>
			</div>

			<!-- No Cron Option -->
			<div style="background: #e8f4f8; padding: 20px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #28a745;">
				<h3 style="margin-top: 0; color: #28a745;">✓ Option C: WordPress Cron (Default)</h3>
				<p><?php echo esc_html( __( 'If you don\'t set up a real cron job, WordPress Cron will still work. Scrapes will run when someone visits your website.', 'wp-event-monitor' ) ); ?></p>
				<p><strong><?php echo esc_html( __( 'Good for:', 'wp-event-monitor' ) ); ?></strong></p>
				<ul>
					<li><?php echo esc_html( __( 'Websites with daily visitors', 'wp-event-monitor' ) ); ?></li>
					<li><?php echo esc_html( __( 'Less critical timing requirements', 'wp-event-monitor' ) ); ?></li>
				</ul>
				<p><strong><?php echo esc_html( __( 'Downside:', 'wp-event-monitor' ) ); ?></strong></p>
				<ul>
					<li><?php echo esc_html( __( 'Scrapes may run late (when someone visits)', 'wp-event-monitor' ) ); ?></li>
					<li><?php echo esc_html( __( 'During low-traffic periods, scrapes may be delayed', 'wp-event-monitor' ) ); ?></li>
				</ul>
				<p><em><?php echo esc_html( __( '→ For 20 websites, we recommend Option A or B for reliable scheduling', 'wp-event-monitor' ) ); ?></em></p>
			</div>

			<!-- Tabs Overview -->
			<hr style="margin: 40px 0; border: none; border-top: 2px solid #ddd;">

			<h2><?php echo esc_html( __( '📑 Tab Overview', 'wp-event-monitor' ) ); ?></h2>

			<table class="wem-table">
				<thead>
					<tr>
						<th><?php echo esc_html( __( 'Tab', 'wp-event-monitor' ) ); ?></th>
						<th><?php echo esc_html( __( 'Purpose', 'wp-event-monitor' ) ); ?></th>
						<th><?php echo esc_html( __( 'Actions', 'wp-event-monitor' ) ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>Sources</strong></td>
						<td><?php echo esc_html( __( 'Manage event websites to scrape', 'wp-event-monitor' ) ); ?></td>
						<td>Add, Enable/Disable, Delete, Scrape Now</td>
					</tr>
					<tr>
						<td><strong>Keywords</strong></td>
						<td><?php echo esc_html( __( 'Manage search keywords and patterns', 'wp-event-monitor' ) ); ?></td>
						<td>Add, Delete</td>
					</tr>
					<tr>
						<td><strong>Settings</strong></td>
						<td><?php echo esc_html( __( 'Configure scrape schedule and timing', 'wp-event-monitor' ) ); ?></td>
						<td>Set Interval, Day, Time</td>
					</tr>
					<tr>
						<td><strong>Activity Log</strong></td>
						<td><?php echo esc_html( __( 'View scrape history and statistics', 'wp-event-monitor' ) ); ?></td>
						<td>View logs, Clear</td>
					</tr>
				</tbody>
			</table>

		</div>

		<style>
			.wem-instructions-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
				gap: 15px;
				margin: 20px 0;
			}
			.wem-instructions-grid > div {
				padding: 15px;
				background: #f8f9fa;
				border-radius: 4px;
				border-left: 4px solid #0073aa;
			}
		</style>
		<?php
	}

	/**
	 * Render sources page
	 */
	private static function render_sources_page() {
		$sources = WEM_Database::get_sources();
		$preview = self::get_preview_from_request();
		?>
		<?php if ( ! empty( $preview ) ) : ?>
			<?php self::render_preview_panel( $preview ); ?>
		<?php endif; ?>

		<div class="wem-form">
			<h2><?php echo esc_html( __( 'Add New Source', 'wp-event-monitor' ) ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wem_add_source">
				<?php wp_nonce_field( 'wem_add_source_nonce' ); ?>

				<div class="wem-form-group">
					<label for="wem_url"><?php echo esc_html( __( 'URL', 'wp-event-monitor' ) ); ?> *</label>
					<input type="url" id="wem_url" name="url" required placeholder="https://example.com/events">
					<small><?php echo esc_html( __( 'Full URL to scrape for events', 'wp-event-monitor' ) ); ?></small>
				</div>

				<div class="wem-form-group">
					<label for="wem_label"><?php echo esc_html( __( 'Label', 'wp-event-monitor' ) ); ?></label>
					<input type="text" id="wem_label" name="label" placeholder="e.g., Example Events">
					<small><?php echo esc_html( __( 'Human-readable name (optional)', 'wp-event-monitor' ) ); ?></small>
				</div>

				<input type="hidden" name="parser_mode" value="auto">

				<details class="wem-form-group">
					<summary><?php echo esc_html( __( 'Advanced selectors', 'wp-event-monitor' ) ); ?></summary>
					<p><small><?php echo esc_html( __( 'Optional: leave empty for Auto. Use these only when preview needs manual tuning.', 'wp-event-monitor' ) ); ?></small></p>

					<label for="wem_selector"><?php echo esc_html( __( 'Event Item Selector', 'wp-event-monitor' ) ); ?></label>
					<input type="text" id="wem_selector" name="css_selector" placeholder=".event-item, .event-card">

					<label for="wem_title_selector"><?php echo esc_html( __( 'Title Selector', 'wp-event-monitor' ) ); ?></label>
					<input type="text" id="wem_title_selector" name="title_selector" placeholder="h2, .title">

					<label for="wem_date_selector"><?php echo esc_html( __( 'Date Selector', 'wp-event-monitor' ) ); ?></label>
					<input type="text" id="wem_date_selector" name="date_selector" placeholder=".date">

					<label for="wem_time_selector"><?php echo esc_html( __( 'Time Selector', 'wp-event-monitor' ) ); ?></label>
					<input type="text" id="wem_time_selector" name="time_selector" placeholder=".time">

					<label for="wem_description_selector"><?php echo esc_html( __( 'Description Selector', 'wp-event-monitor' ) ); ?></label>
					<input type="text" id="wem_description_selector" name="description_selector" placeholder=".description">

					<label for="wem_link_selector"><?php echo esc_html( __( 'Link Selector', 'wp-event-monitor' ) ); ?></label>
					<input type="text" id="wem_link_selector" name="link_selector" placeholder="a[title=&quot;Details&quot;]">
				</details>

				<button type="submit" class="wem-btn">
					<?php echo esc_html( __( 'Add Source', 'wp-event-monitor' ) ); ?>
				</button>
			</form>
		</div>

		<div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-top: 24px;">
			<h2 style="margin: 0;"><?php echo esc_html( __( 'Sources', 'wp-event-monitor' ) ); ?></h2>
			<?php if ( ! empty( $sources ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="wem_scrape_all_sources">
					<?php wp_nonce_field( 'wem_scrape_all_sources_nonce' ); ?>
					<button type="submit" class="wem-btn">
						<?php echo esc_html( __( 'Scrape All Now', 'wp-event-monitor' ) ); ?>
					</button>
				</form>
			<?php endif; ?>
		</div>
		<?php if ( ! empty( $sources ) ) : ?>
			<table class="wem-table">
				<thead>
					<tr>
						<th><?php echo esc_html( __( 'Label', 'wp-event-monitor' ) ); ?></th>
						<th><?php echo esc_html( __( 'URL', 'wp-event-monitor' ) ); ?></th>
						<th><?php echo esc_html( __( 'Mode', 'wp-event-monitor' ) ); ?></th>
						<th><?php echo esc_html( __( 'Selector', 'wp-event-monitor' ) ); ?></th>
						<th><?php echo esc_html( __( 'Status', 'wp-event-monitor' ) ); ?></th>
						<th><?php echo esc_html( __( 'Last Scraped', 'wp-event-monitor' ) ); ?></th>
						<th><?php echo esc_html( __( 'Actions', 'wp-event-monitor' ) ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $sources as $source ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $source->label ); ?></strong></td>
							<td><small><?php echo esc_html( $source->url ); ?></small></td>
							<td><?php echo esc_html( self::get_parser_mode_label( ! empty( $source->parser_mode ) ? $source->parser_mode : 'auto' ) ); ?></td>
							<td><?php echo ! empty( $source->css_selector ) ? esc_html( $source->css_selector ) : '—'; ?></td>
							<td>
								<span class="wem-status <?php echo $source->enabled ? 'active' : 'inactive'; ?>">
									<?php echo $source->enabled ? esc_html( __( 'Active', 'wp-event-monitor' ) ) : esc_html( __( 'Inactive', 'wp-event-monitor' ) ); ?>
								</span>
							</td>
							<td><?php echo $source->last_scraped ? esc_html( $source->last_scraped ) : '—'; ?></td>
							<td>
								<div class="wem-actions">
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
										<input type="hidden" name="action" value="wem_toggle_source">
										<input type="hidden" name="source_id" value="<?php echo esc_attr( $source->id ); ?>">
										<input type="hidden" name="enabled" value="<?php echo $source->enabled ? '0' : '1'; ?>">
										<?php wp_nonce_field( 'wem_toggle_source_' . $source->id ); ?>
										<button type="submit" class="wem-btn secondary">
											<?php echo $source->enabled ? esc_html( __( 'Disable', 'wp-event-monitor' ) ) : esc_html( __( 'Enable', 'wp-event-monitor' ) ); ?>
										</button>
									</form>

									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
										<input type="hidden" name="action" value="wem_preview_source">
										<input type="hidden" name="source_id" value="<?php echo esc_attr( $source->id ); ?>">
										<?php wp_nonce_field( 'wem_preview_source_' . $source->id ); ?>
										<button type="submit" class="wem-btn secondary">
											<?php echo esc_html( __( 'Preview', 'wp-event-monitor' ) ); ?>
										</button>
									</form>

									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
										<input type="hidden" name="action" value="wem_scrape_source">
										<input type="hidden" name="source_id" value="<?php echo esc_attr( $source->id ); ?>">
										<?php wp_nonce_field( 'wem_scrape_source_' . $source->id ); ?>
										<button type="submit" class="wem-btn secondary">
											<?php echo esc_html( __( 'Scrape Now', 'wp-event-monitor' ) ); ?>
										</button>
									</form>

									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
										<input type="hidden" name="action" value="wem_delete_source">
										<input type="hidden" name="source_id" value="<?php echo esc_attr( $source->id ); ?>">
										<?php wp_nonce_field( 'wem_delete_source_' . $source->id ); ?>
										<button type="submit" class="wem-btn danger" onclick="return confirm('<?php echo esc_attr( __( 'Are you sure?', 'wp-event-monitor' ) ); ?>');">
											<?php echo esc_html( __( 'Delete', 'wp-event-monitor' ) ); ?>
										</button>
									</form>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="7">
								<details>
									<summary><?php echo esc_html__( 'Configure source', 'wp-event-monitor' ); ?></summary>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wem-form" style="margin-top: 12px; max-width: 900px;">
										<input type="hidden" name="action" value="wem_update_source">
										<input type="hidden" name="source_id" value="<?php echo esc_attr( $source->id ); ?>">
										<?php wp_nonce_field( 'wem_update_source_nonce' ); ?>

										<div class="wem-form-group">
											<label><?php echo esc_html__( 'URL', 'wp-event-monitor' ); ?></label>
											<input type="url" name="url" value="<?php echo esc_attr( $source->url ); ?>">
										</div>

										<div class="wem-form-group">
											<label><?php echo esc_html__( 'Label', 'wp-event-monitor' ); ?></label>
											<input type="text" name="label" value="<?php echo esc_attr( $source->label ); ?>">
										</div>

										<div class="wem-form-group">
											<label><?php echo esc_html__( 'Parsing Mode', 'wp-event-monitor' ); ?></label>
											<select name="parser_mode">
												<option value="auto" <?php selected( ! empty( $source->parser_mode ) ? $source->parser_mode : 'auto', 'auto' ); ?>><?php echo esc_html__( 'Auto', 'wp-event-monitor' ); ?></option>
												<option value="html" <?php selected( ! empty( $source->parser_mode ) ? $source->parser_mode : 'auto', 'html' ); ?>><?php echo esc_html__( 'HTML only', 'wp-event-monitor' ); ?></option>
												<option value="structured" <?php selected( ! empty( $source->parser_mode ) ? $source->parser_mode : 'auto', 'structured' ); ?>><?php echo esc_html__( 'Structured data only', 'wp-event-monitor' ); ?></option>
											</select>
										</div>

										<div class="wem-form-group">
											<label><?php echo esc_html__( 'Event Item Selector', 'wp-event-monitor' ); ?></label>
											<input type="text" name="css_selector" value="<?php echo esc_attr( $source->css_selector ); ?>" placeholder=".event-item">
										</div>

										<div class="wem-form-group">
											<label><?php echo esc_html__( 'Title Selector', 'wp-event-monitor' ); ?></label>
											<input type="text" name="title_selector" value="<?php echo esc_attr( $source->title_selector ?? '' ); ?>" placeholder="h2, .title">
										</div>

										<div class="wem-form-group">
											<label><?php echo esc_html__( 'Date Selector', 'wp-event-monitor' ); ?></label>
											<input type="text" name="date_selector" value="<?php echo esc_attr( $source->date_selector ?? '' ); ?>" placeholder=".date">
										</div>

										<div class="wem-form-group">
											<label><?php echo esc_html__( 'Time Selector', 'wp-event-monitor' ); ?></label>
											<input type="text" name="time_selector" value="<?php echo esc_attr( $source->time_selector ?? '' ); ?>" placeholder=".time">
										</div>

										<div class="wem-form-group">
											<label><?php echo esc_html__( 'Description Selector', 'wp-event-monitor' ); ?></label>
											<input type="text" name="description_selector" value="<?php echo esc_attr( $source->description_selector ?? '' ); ?>" placeholder=".description">
										</div>

										<div class="wem-form-group">
											<label><?php echo esc_html__( 'Link Selector', 'wp-event-monitor' ); ?></label>
											<input type="text" name="link_selector" value="<?php echo esc_attr( $source->link_selector ?? '' ); ?>" placeholder="a[title=&quot;Details&quot;]">
										</div>

										<button type="submit" class="wem-btn"><?php echo esc_html__( 'Save Source Settings', 'wp-event-monitor' ); ?></button>
									</form>
								</details>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php echo esc_html( __( 'No sources added yet.', 'wp-event-monitor' ) ); ?></p>
		<?php endif;
	}

	/**
	 * Read preview data from a transient referenced in the URL.
	 *
	 * @return array|null Preview data
	 */
	private static function get_preview_from_request() {
		if ( empty( $_GET['preview_key'] ) ) {
			return null;
		}

		$key = sanitize_key( wp_unslash( $_GET['preview_key'] ) );
		if ( empty( $key ) ) {
			return null;
		}

		$preview = get_transient( $key );
		delete_transient( $key );

		return is_array( $preview ) ? $preview : null;
	}

	/**
	 * Render source preview results.
	 *
	 * @param array $preview Preview data
	 */
	private static function render_preview_panel( $preview ) {
		$events = $preview['events'] ?? array();
		?>
		<div class="notice <?php echo ! empty( $preview['success'] ) ? 'notice-info' : 'notice-error'; ?> inline">
			<p>
				<strong><?php echo esc_html__( 'Preview:', 'wp-event-monitor' ); ?></strong>
				<?php echo esc_html( $preview['message'] ?? '' ); ?>
				<?php if ( ! empty( $preview['success'] ) ) : ?>
					<?php
					printf(
						esc_html__( ' Found: %1$d, matched: %2$d.', 'wp-event-monitor' ),
						(int) ( $preview['found'] ?? 0 ),
						(int) ( $preview['matched'] ?? 0 )
					);
					?>
				<?php endif; ?>
			</p>
		</div>

		<?php if ( ! empty( $events ) ) : ?>
			<table class="wem-table">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Title', 'wp-event-monitor' ); ?></th>
						<th><?php echo esc_html__( 'Date / Time', 'wp-event-monitor' ); ?></th>
						<th><?php echo esc_html__( 'Keywords', 'wp-event-monitor' ); ?></th>
						<th><?php echo esc_html__( 'Status', 'wp-event-monitor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_slice( $events, 0, 30 ) as $event ) : ?>
						<tr>
							<td>
								<strong><?php echo esc_html( $event['title'] ); ?></strong>
								<?php if ( ! empty( $event['href'] ) ) : ?>
									<br><small><a href="<?php echo esc_url( $event['href'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $event['href'] ); ?></a></small>
								<?php endif; ?>
								<?php if ( ! empty( $event['description'] ) ) : ?>
									<br><small><?php echo esc_html( $event['description'] ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<?php echo esc_html( trim( ( $event['date'] ?? '' ) . ' ' . ( $event['time'] ?? '' ) ) ); ?>
							</td>
							<td>
								<?php echo ! empty( $event['matched'] ) ? esc_html( implode( ', ', $event['matched'] ) ) : '—'; ?>
							</td>
							<td>
								<?php if ( ! empty( $event['duplicate'] ) ) : ?>
									<?php echo esc_html__( 'Duplicate', 'wp-event-monitor' ); ?>
								<?php elseif ( ! empty( $event['matched'] ) ) : ?>
									<?php echo esc_html__( 'Will import', 'wp-event-monitor' ); ?>
								<?php else : ?>
									<?php echo esc_html__( 'No keyword match', 'wp-event-monitor' ); ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render keywords page
	 */
	private static function render_keywords_page() {
		$keywords = WEM_Database::get_keywords();
		?>
		<div class="wem-form">
			<h2><?php echo esc_html( __( 'Add New Keyword', 'wp-event-monitor' ) ); ?></h2>
			<?php if ( isset( $_GET['added'] ) || isset( $_GET['skipped'] ) || isset( $_GET['failed'] ) ) : ?>
				<div class="notice notice-success inline">
					<p>
						<?php
						printf(
							esc_html__( 'Keywords imported. Added: %1$d, skipped: %2$d, failed: %3$d.', 'wp-event-monitor' ),
							isset( $_GET['added'] ) ? (int) $_GET['added'] : 0,
							isset( $_GET['skipped'] ) ? (int) $_GET['skipped'] : 0,
							isset( $_GET['failed'] ) ? (int) $_GET['failed'] : 0
						);
						?>
					</p>
				</div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wem_add_keyword">
				<?php wp_nonce_field( 'wem_add_keyword_nonce' ); ?>

				<div class="wem-form-group">
					<label for="wem_keyword"><?php echo esc_html( __( 'Keyword / Pattern', 'wp-event-monitor' ) ); ?> *</label>
					<input type="text" id="wem_keyword" name="keyword" placeholder="e.g., conference, /workshop|seminar/">
					<small><?php echo esc_html( __( 'Plain text or regex pattern (with delimiters and flags)', 'wp-event-monitor' ) ); ?></small>
				</div>

				<div class="wem-form-group">
					<label for="wem_keywords_bulk"><?php echo esc_html( __( 'Bulk Keywords', 'wp-event-monitor' ) ); ?></label>
					<textarea id="wem_keywords_bulk" name="keywords_bulk" rows="7" placeholder="conference&#10;workshop&#10;seminar"></textarea>
					<small><?php echo esc_html( __( 'Optional: paste many plain keywords separated by new lines, commas, or semicolons. Regex patterns are split by new lines only.', 'wp-event-monitor' ) ); ?></small>
				</div>

				<div class="wem-form-group">
					<label for="wem_type"><?php echo esc_html( __( 'Type', 'wp-event-monitor' ) ); ?></label>
					<select id="wem_type" name="type">
						<option value="plain"><?php echo esc_html( __( 'Plain Text (case-insensitive)', 'wp-event-monitor' ) ); ?></option>
						<option value="regex"><?php echo esc_html( __( 'Regular Expression', 'wp-event-monitor' ) ); ?></option>
					</select>
					<small><?php echo esc_html( __( 'Choose matching type', 'wp-event-monitor' ) ); ?></small>
				</div>

				<button type="submit" class="wem-btn">
					<?php echo esc_html( __( 'Add Keyword', 'wp-event-monitor' ) ); ?>
				</button>
			</form>
		</div>

		<h2><?php echo esc_html( __( 'Keywords', 'wp-event-monitor' ) ); ?></h2>
		<?php if ( ! empty( $keywords ) ) : ?>
			<table class="wem-table">
				<thead>
					<tr>
						<th><?php echo esc_html( __( 'Keyword', 'wp-event-monitor' ) ); ?></th>
						<th><?php echo esc_html( __( 'Type', 'wp-event-monitor' ) ); ?></th>
						<th><?php echo esc_html( __( 'Actions', 'wp-event-monitor' ) ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $keywords as $kw ) : ?>
						<tr>
							<td><code><?php echo esc_html( $kw->keyword ); ?></code></td>
							<td><?php echo esc_html( ucfirst( $kw->type ) ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
									<input type="hidden" name="action" value="wem_delete_keyword">
									<input type="hidden" name="keyword_id" value="<?php echo esc_attr( $kw->id ); ?>">
									<?php wp_nonce_field( 'wem_delete_keyword_' . $kw->id ); ?>
									<button type="submit" class="wem-btn danger" onclick="return confirm('<?php echo esc_attr( __( 'Are you sure?', 'wp-event-monitor' ) ); ?>');">
										<?php echo esc_html( __( 'Delete', 'wp-event-monitor' ) ); ?>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php echo esc_html( __( 'No keywords added yet.', 'wp-event-monitor' ) ); ?></p>
		<?php endif;
	}

	/**
	 * Get a human-readable parser mode label.
	 *
	 * @param string $parser_mode Parser mode
	 *
	 * @return string Display label
	 */
	private static function get_parser_mode_label( $parser_mode ) {
		$labels = array(
			'auto' => __( 'Auto', 'wp-event-monitor' ),
			'html' => __( 'HTML', 'wp-event-monitor' ),
			'structured' => __( 'Structured data', 'wp-event-monitor' ),
		);

		return $labels[ $parser_mode ] ?? $labels['auto'];
	}

	/**
	 * Read optional field selectors from POST.
	 *
	 * @return array Field selectors
	 */
	private static function get_field_selectors_from_post() {
		$selectors = array();
		foreach ( array( 'title_selector', 'date_selector', 'time_selector', 'description_selector', 'link_selector' ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$selectors[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			}
		}

		return $selectors;
	}

	/**
	 * Render activity log page
	 */
	private static function render_log_page() {
		$logs = WEM_Database::get_logs( 100 );
		?>
		<div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom: 20px;">
				<input type="hidden" name="action" value="wem_clear_log">
				<?php wp_nonce_field( 'wem_clear_log_nonce' ); ?>
				<button type="submit" class="wem-btn danger" onclick="return confirm('<?php echo esc_attr( __( 'Clear all logs?', 'wp-event-monitor' ) ); ?>');">
					<?php echo esc_html( __( 'Clear Log', 'wp-event-monitor' ) ); ?>
				</button>
			</form>

			<h2><?php echo esc_html( __( 'Recent Activity (Last 100 entries)', 'wp-event-monitor' ) ); ?></h2>
			<?php if ( ! empty( $logs ) ) : ?>
				<table class="wem-table">
					<thead>
						<tr>
							<th><?php echo esc_html( __( 'Source', 'wp-event-monitor' ) ); ?></th>
							<th><?php echo esc_html( __( 'Status', 'wp-event-monitor' ) ); ?></th>
							<th><?php echo esc_html( __( 'Found', 'wp-event-monitor' ) ); ?></th>
							<th><?php echo esc_html( __( 'Matched', 'wp-event-monitor' ) ); ?></th>
							<th><?php echo esc_html( __( 'Posts Created', 'wp-event-monitor' ) ); ?></th>
							<th><?php echo esc_html( __( 'Time', 'wp-event-monitor' ) ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td>
									<?php if ( ! empty( $log->source_url ) ) : ?>
										<strong><?php echo esc_html( parse_url( $log->source_url, PHP_URL_HOST ) ); ?></strong><br>
										<small><?php echo esc_html( $log->source_url ); ?></small>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
								<td>
									<span class="wem-status <?php echo $log->status === 'success' ? 'active' : 'inactive'; ?>">
										<?php echo esc_html( ucfirst( $log->status ) ); ?>
									</span>
									<?php if ( ! empty( $log->error_message ) ) : ?>
										<br><small style="color: <?php echo $log->status === 'error' ? '#dc3545' : '#666'; ?>;"><?php echo esc_html( $log->error_message ); ?></small>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $log->items_found ); ?></td>
								<td><?php echo esc_html( $log->items_matched ); ?></td>
								<td><?php echo esc_html( $log->posts_created ); ?></td>
								<td><?php echo esc_html( $log->created_at ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php echo esc_html( __( 'No logs yet.', 'wp-event-monitor' ) ); ?></p>
			<?php endif;
	}

	/**
	 * Handle add source form submission
	 */
	public static function handle_add_source() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wem_add_source_nonce' ) ) {
			wp_die( __( 'Security check failed', 'wp-event-monitor' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'wp-event-monitor' ) );
		}

		$url = isset( $_POST['url'] ) ? sanitize_text_field( wp_unslash( $_POST['url'] ) ) : '';
		$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
		$css_selector = isset( $_POST['css_selector'] ) ? sanitize_text_field( wp_unslash( $_POST['css_selector'] ) ) : '';
		$parser_mode = isset( $_POST['parser_mode'] ) ? sanitize_key( wp_unslash( $_POST['parser_mode'] ) ) : 'auto';
		$field_selectors = self::get_field_selectors_from_post();

		$result = WEM_Source_Manager::add_source( $url, $label, $css_selector, $parser_mode, $field_selectors );

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wem-dashboard&tab=sources&added=1' ) );
		exit;
	}

	/**
	 * Handle update source form submission
	 */
	public static function handle_update_source() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wem_update_source_nonce' ) ) {
			wp_die( __( 'Security check failed', 'wp-event-monitor' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'wp-event-monitor' ) );
		}

		$source_id = isset( $_POST['source_id'] ) ? (int) wp_unslash( $_POST['source_id'] ) : 0;
		if ( ! $source_id ) {
			wp_die( __( 'Invalid source', 'wp-event-monitor' ) );
		}

		$data = array();
		if ( isset( $_POST['url'] ) ) {
			$data['url'] = sanitize_text_field( wp_unslash( $_POST['url'] ) );
		}
		if ( isset( $_POST['label'] ) ) {
			$data['label'] = sanitize_text_field( wp_unslash( $_POST['label'] ) );
		}
		if ( isset( $_POST['parser_mode'] ) ) {
			$data['parser_mode'] = sanitize_key( wp_unslash( $_POST['parser_mode'] ) );
		}
		if ( isset( $_POST['css_selector'] ) ) {
			$data['css_selector'] = sanitize_text_field( wp_unslash( $_POST['css_selector'] ) );
		}
		$data = array_merge( $data, self::get_field_selectors_from_post() );

		WEM_Source_Manager::update_source( $source_id, $data );

		wp_safe_redirect( admin_url( 'admin.php?page=wem-dashboard&tab=sources' ) );
		exit;
	}

	/**
	 * Handle delete source form submission
	 */
	public static function handle_delete_source() {
		$source_id = isset( $_POST['source_id'] ) ? (int) wp_unslash( $_POST['source_id'] ) : 0;

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wem_delete_source_' . $source_id ) ) {
			wp_die( __( 'Security check failed', 'wp-event-monitor' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'wp-event-monitor' ) );
		}

		if ( ! $source_id ) {
			wp_die( __( 'Invalid source', 'wp-event-monitor' ) );
		}

		WEM_Source_Manager::delete_source( $source_id );

		wp_safe_redirect( admin_url( 'admin.php?page=wem-dashboard&tab=sources' ) );
		exit;
	}

	/**
	 * Handle toggle source status
	 */
	public static function handle_toggle_source() {
		$source_id = isset( $_POST['source_id'] ) ? (int) wp_unslash( $_POST['source_id'] ) : 0;

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wem_toggle_source_' . $source_id ) ) {
			wp_die( __( 'Security check failed', 'wp-event-monitor' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'wp-event-monitor' ) );
		}

		$enabled = isset( $_POST['enabled'] ) ? (bool) $_POST['enabled'] : false;

		if ( ! $source_id ) {
			wp_die( __( 'Invalid source', 'wp-event-monitor' ) );
		}

		WEM_Source_Manager::toggle_source( $source_id, $enabled );

		wp_safe_redirect( admin_url( 'admin.php?page=wem-dashboard&tab=sources' ) );
		exit;
	}

	/**
	 * Handle scrape source
	 */
	public static function handle_scrape_source() {
		$source_id = isset( $_POST['source_id'] ) ? (int) wp_unslash( $_POST['source_id'] ) : 0;

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wem_scrape_source_' . $source_id ) ) {
			wp_die( __( 'Security check failed', 'wp-event-monitor' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'wp-event-monitor' ) );
		}

		if ( ! $source_id ) {
			wp_die( __( 'Invalid source', 'wp-event-monitor' ) );
		}

		WEM_Cron::manual_scrape( $source_id );

		wp_safe_redirect( admin_url( 'admin.php?page=wem-dashboard&tab=log' ) );
		exit;
	}

	/**
	 * Handle scraping all active sources.
	 */
	public static function handle_scrape_all_sources() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wem_scrape_all_sources_nonce' ) ) {
			wp_die( __( 'Security check failed', 'wp-event-monitor' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'wp-event-monitor' ) );
		}

		WEM_Cron::run_scrape();

		wp_safe_redirect( admin_url( 'admin.php?page=wem-dashboard&tab=log' ) );
		exit;
	}

	/**
	 * Handle source preview without creating drafts.
	 */
	public static function handle_preview_source() {
		$source_id = isset( $_POST['source_id'] ) ? (int) wp_unslash( $_POST['source_id'] ) : 0;

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wem_preview_source_' . $source_id ) ) {
			wp_die( __( 'Security check failed', 'wp-event-monitor' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'wp-event-monitor' ) );
		}

		if ( ! $source_id ) {
			wp_die( __( 'Invalid source', 'wp-event-monitor' ) );
		}

		$preview = WEM_Cron::preview_source( $source_id );
		$key = 'wem_preview_' . get_current_user_id() . '_' . time();
		set_transient( $key, $preview, 10 * MINUTE_IN_SECONDS );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => 'wem-dashboard',
					'tab' => 'sources',
					'preview_key' => $key,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle add keyword form submission
	 */
	public static function handle_add_keyword() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wem_add_keyword_nonce' ) ) {
			wp_die( __( 'Security check failed', 'wp-event-monitor' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'wp-event-monitor' ) );
		}

		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
		$keywords_bulk = isset( $_POST['keywords_bulk'] ) ? wp_unslash( $_POST['keywords_bulk'] ) : '';
		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'plain';

		if ( ! empty( trim( $keywords_bulk ) ) ) {
			$summary = WEM_Source_Manager::add_keywords_bulk( $keywords_bulk, $type );

			wp_safe_redirect(
				add_query_arg(
					array(
						'tab' => 'keywords',
						'added' => (int) $summary['added'],
						'skipped' => (int) $summary['skipped'],
						'failed' => (int) $summary['failed'],
					),
					admin_url( 'admin.php?page=wem-dashboard' )
				)
			);
			exit;
		}

		if ( empty( $keyword ) ) {
			wp_die( esc_html__( 'Keyword cannot be empty', 'wp-event-monitor' ) );
		}

		$result = WEM_Source_Manager::add_keyword( $keyword, $type );

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wem-dashboard&tab=keywords' ) );
		exit;
	}

	/**
	 * Handle delete keyword form submission
	 */
	public static function handle_delete_keyword() {
		$keyword_id = isset( $_POST['keyword_id'] ) ? (int) wp_unslash( $_POST['keyword_id'] ) : 0;

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wem_delete_keyword_' . $keyword_id ) ) {
			wp_die( __( 'Security check failed', 'wp-event-monitor' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'wp-event-monitor' ) );
		}

		if ( ! $keyword_id ) {
			wp_die( __( 'Invalid keyword', 'wp-event-monitor' ) );
		}

		WEM_Source_Manager::delete_keyword( $keyword_id );

		wp_safe_redirect( admin_url( 'admin.php?page=wem-dashboard&tab=keywords' ) );
		exit;
	}

	/**
	 * Handle clear log
	 */
	public static function handle_clear_log() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wem_clear_log_nonce' ) ) {
			wp_die( __( 'Security check failed', 'wp-event-monitor' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'wp-event-monitor' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'em_log';
		$wpdb->query( "DELETE FROM {$table}" );

		wp_safe_redirect( admin_url( 'admin.php?page=wem-dashboard&tab=log' ) );
		exit;
	}

	/**
	 * Render settings page
	 */
	private static function render_settings_page() {
		wp_enqueue_media();

		$schedule_type = get_option( 'wem_schedule_type', 'weekly' );
		$schedule_day = get_option( 'wem_schedule_day', 1 );
		$schedule_time = get_option( 'wem_schedule_time', '08:00' );
		$fallback_image_ids = self::fallback_image_ids();
		$next_run = WEM_Cron::get_next_run();
		?>
		<div class="wem-form">
			<h2><?php echo esc_html( __( 'Scrape Settings', 'wp-event-monitor' ) ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wem_save_settings">
				<?php wp_nonce_field( 'wem_save_settings_nonce' ); ?>

				<div class="wem-form-group">
					<label><?php echo esc_html( __( 'Scrape Interval', 'wp-event-monitor' ) ); ?></label>
					<div style="margin-top: 10px;">
						<label style="font-weight: normal; margin-right: 20px;">
							<input type="radio" name="schedule_type" value="weekly" <?php checked( $schedule_type, 'weekly' ); ?>>
							<?php echo esc_html( __( 'Weekly', 'wp-event-monitor' ) ); ?>
						</label>
						<label style="font-weight: normal; margin-right: 20px;">
							<input type="radio" name="schedule_type" value="biweekly" <?php checked( $schedule_type, 'biweekly' ); ?>>
							<?php echo esc_html( __( 'Bi-weekly', 'wp-event-monitor' ) ); ?>
						</label>
						<label style="font-weight: normal;">
							<input type="radio" name="schedule_type" value="monthly" <?php checked( $schedule_type, 'monthly' ); ?>>
							<?php echo esc_html( __( 'Monthly', 'wp-event-monitor' ) ); ?>
						</label>
					</div>
				</div>

				<div class="wem-form-group" id="wem-day-selector">
					<label for="wem_schedule_day"><?php echo esc_html( __( 'Day', 'wp-event-monitor' ) ); ?></label>
					<select id="wem_schedule_day" name="schedule_day">
						<?php
						$day_labels = array(
							0 => __( 'Sunday', 'wp-event-monitor' ),
							1 => __( 'Monday', 'wp-event-monitor' ),
							2 => __( 'Tuesday', 'wp-event-monitor' ),
							3 => __( 'Wednesday', 'wp-event-monitor' ),
							4 => __( 'Thursday', 'wp-event-monitor' ),
							5 => __( 'Friday', 'wp-event-monitor' ),
							6 => __( 'Saturday', 'wp-event-monitor' ),
						);
						$month_labels = array();
						for ( $i = 1; $i <= 28; $i++ ) {
							$month_labels[ $i ] = $i;
						}
						?>
						<optgroup label="<?php echo esc_html( __( 'Weekday', 'wp-event-monitor' ) ); ?>" id="weekday-optgroup">
							<?php
							foreach ( $day_labels as $val => $label ) {
								echo '<option value="' . esc_attr( $val ) . '" ' . selected( $schedule_day, $val, false ) . '>' . esc_html( $label ) . '</option>';
							}
							?>
						</optgroup>
						<optgroup label="<?php echo esc_html( __( 'Day of Month', 'wp-event-monitor' ) ); ?>" id="monthday-optgroup" style="display: none;">
							<?php
							foreach ( $month_labels as $val => $label ) {
								echo '<option value="' . esc_attr( $val ) . '" ' . selected( $schedule_day, $val, false ) . '>' . esc_html( $label ) . '</option>';
							}
							?>
						</optgroup>
					</select>
					<small><?php echo esc_html( __( 'Which day to run the scrape', 'wp-event-monitor' ) ); ?></small>
				</div>

				<div class="wem-form-group">
					<label for="wem_schedule_time"><?php echo esc_html( __( 'Time', 'wp-event-monitor' ) ); ?></label>
					<select id="wem_schedule_time" name="schedule_time">
						<?php
						for ( $h = 0; $h < 24; $h++ ) {
							for ( $m = 0; $m < 60; $m += 15 ) {
								$time = sprintf( '%02d:%02d', $h, $m );
								echo '<option value="' . esc_attr( $time ) . '" ' . selected( $schedule_time, $time, false ) . '>' . esc_html( $time ) . '</option>';
							}
						}
						?>
					</select>
					<small><?php echo esc_html( __( 'What time to run the scrape (24-hour format)', 'wp-event-monitor' ) ); ?></small>
				</div>

				<div class="wem-form-group">
					<label for="wem_fallback_image_ids"><?php echo esc_html( __( 'Fallback Image Gallery', 'wp-event-monitor' ) ); ?></label>
					<input type="hidden" id="wem_fallback_image_ids" name="fallback_image_ids" value="<?php echo esc_attr( implode( ',', $fallback_image_ids ) ); ?>">
					<div class="wem-fallback-gallery" id="wem-fallback-gallery">
						<?php foreach ( $fallback_image_ids as $attachment_id ) : ?>
							<?php
							$thumb = wp_get_attachment_image( $attachment_id, 'thumbnail', false, array( 'data-wem-fallback-id' => $attachment_id ) );
							if ( empty( $thumb ) ) {
								continue;
							}
							?>
							<div class="wem-fallback-gallery-item" data-wem-fallback-id="<?php echo esc_attr( $attachment_id ); ?>">
								<?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<button type="button" class="button-link-delete" data-wem-remove-fallback><?php echo esc_html__( 'Remove', 'wp-event-monitor' ); ?></button>
							</div>
						<?php endforeach; ?>
					</div>
					<p>
						<button type="button" class="button button-secondary" id="wem-select-fallback-images"><?php echo esc_html__( 'Select Images', 'wp-event-monitor' ); ?></button>
						<button type="button" class="button button-secondary" id="wem-clear-fallback-images"><?php echo esc_html__( 'Clear Gallery', 'wp-event-monitor' ); ?></button>
					</p>
					<small><?php echo esc_html__( 'When an event has no usable scraped image, one image from this gallery is assigned randomly as the featured image.', 'wp-event-monitor' ); ?></small>
				</div>

				<button type="submit" class="button button-primary">
					<?php echo esc_html( __( 'Save Settings', 'wp-event-monitor' ) ); ?>
				</button>
			</form>
		</div>

		<?php if ( $next_run ) : ?>
			<div style="background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 4px; margin-top: 20px;">
				<strong><?php echo esc_html( __( 'Next Scrape:', 'wp-event-monitor' ) ); ?></strong> <?php echo esc_html( $next_run ); ?>
			</div>
		<?php endif; ?>

		<script>
			(function() {
				function initSettingsPage() {
				const typeRadios = document.querySelectorAll('input[name="schedule_type"]');
				const daySelector = document.getElementById('wem-day-selector');
				const weekdayOptgroup = document.getElementById('weekday-optgroup');
				const monthdayOptgroup = document.getElementById('monthday-optgroup');
				const fallbackInput = document.getElementById('wem_fallback_image_ids');
				const fallbackGallery = document.getElementById('wem-fallback-gallery');
				const selectFallbackImages = document.getElementById('wem-select-fallback-images');
				const clearFallbackImages = document.getElementById('wem-clear-fallback-images');

				function updateDayOptions() {
					const selectedType = document.querySelector('input[name="schedule_type"]:checked').value;
					if (selectedType === 'monthly') {
						weekdayOptgroup.style.display = 'none';
						monthdayOptgroup.style.display = 'block';
					} else {
						weekdayOptgroup.style.display = 'block';
						monthdayOptgroup.style.display = 'none';
					}
				}

				typeRadios.forEach(radio => {
					radio.addEventListener('change', updateDayOptions);
				});

				function fallbackIds() {
					if (!fallbackInput || !fallbackInput.value) return [];
					return fallbackInput.value.split(',').map(value => parseInt(value, 10)).filter(Boolean);
				}

				function setFallbackIds(ids) {
					const uniqueIds = Array.from(new Set(ids.map(value => parseInt(value, 10)).filter(Boolean)));
					fallbackInput.value = uniqueIds.join(',');
				}

				function addFallbackPreview(attachment) {
					if (!fallbackGallery || !attachment || !attachment.id) return;
					if (fallbackGallery.querySelector('[data-wem-fallback-id="' + attachment.id + '"]')) return;
					const item = document.createElement('div');
					item.className = 'wem-fallback-gallery-item';
					item.dataset.wemFallbackId = attachment.id;
					const image = document.createElement('img');
					image.src = (attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url);
					image.alt = attachment.alt || attachment.title || '';
					const remove = document.createElement('button');
					remove.type = 'button';
					remove.className = 'button-link-delete';
					remove.dataset.wemRemoveFallback = '';
					remove.textContent = '<?php echo esc_js( __( 'Remove', 'wp-event-monitor' ) ); ?>';
					item.appendChild(image);
					item.appendChild(remove);
					fallbackGallery.appendChild(item);
				}

				if (selectFallbackImages && fallbackInput && fallbackGallery && window.wp && wp.media) {
					selectFallbackImages.addEventListener('click', function(event) {
						event.preventDefault();
						const frame = wp.media({
							title: '<?php echo esc_js( __( 'Select fallback images', 'wp-event-monitor' ) ); ?>',
							button: { text: '<?php echo esc_js( __( 'Use selected images', 'wp-event-monitor' ) ); ?>' },
							library: { type: 'image' },
							multiple: true
						});

						frame.on('select', function() {
							const selected = frame.state().get('selection').toJSON();
							const ids = fallbackIds();
							selected.forEach(function(attachment) {
								ids.push(attachment.id);
								addFallbackPreview(attachment);
							});
							setFallbackIds(ids);
						});

						frame.open();
					});
				}

				if (fallbackGallery && fallbackInput) {
					fallbackGallery.addEventListener('click', function(event) {
						const button = event.target.closest('[data-wem-remove-fallback]');
						if (!button) return;
						const item = button.closest('[data-wem-fallback-id]');
						if (!item) return;
						const removeId = parseInt(item.dataset.wemFallbackId, 10);
						item.remove();
						setFallbackIds(fallbackIds().filter(id => id !== removeId));
					});
				}

				if (clearFallbackImages && fallbackInput && fallbackGallery) {
					clearFallbackImages.addEventListener('click', function(event) {
						event.preventDefault();
						fallbackInput.value = '';
						fallbackGallery.innerHTML = '';
					});
				}

				// Initialize on load
				updateDayOptions();
				}

				if (document.readyState === 'complete') {
					initSettingsPage();
				} else {
					window.addEventListener('load', initSettingsPage);
				}
			})();
		</script>
		<?php
	}

	/**
	 * Handle save settings form submission
	 */
	public static function handle_save_settings() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wem_save_settings_nonce' ) ) {
			wp_die( __( 'Security check failed', 'wp-event-monitor' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'wp-event-monitor' ) );
		}

		$schedule_type = isset( $_POST['schedule_type'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule_type'] ) ) : 'weekly';
		$schedule_day = isset( $_POST['schedule_day'] ) ? (int) $_POST['schedule_day'] : 1;
		$schedule_time = isset( $_POST['schedule_time'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule_time'] ) ) : '08:00';
		$fallback_image_ids = isset( $_POST['fallback_image_ids'] ) ? self::sanitize_fallback_image_ids( wp_unslash( $_POST['fallback_image_ids'] ) ) : array();

		// Validate schedule type
		if ( ! in_array( $schedule_type, array( 'weekly', 'biweekly', 'monthly' ), true ) ) {
			wp_die( __( 'Invalid schedule type', 'wp-event-monitor' ) );
		}

		// Validate day
		if ( $schedule_type === 'monthly' ) {
			if ( $schedule_day < 1 || $schedule_day > 28 ) {
				wp_die( __( 'Invalid day of month', 'wp-event-monitor' ) );
			}
		} else {
			if ( $schedule_day < 0 || $schedule_day > 6 ) {
				wp_die( __( 'Invalid day of week', 'wp-event-monitor' ) );
			}
		}

		// Reschedule the cron job
		WEM_Cron::reschedule_by_schedule( $schedule_type, $schedule_day, $schedule_time );
		update_option( 'wem_fallback_image_ids', $fallback_image_ids );

		wp_safe_redirect( admin_url( 'admin.php?page=wem-dashboard&tab=settings&saved=1' ) );
		exit;
	}

	/**
	 * Get saved fallback image IDs.
	 *
	 * @return array
	 */
	private static function fallback_image_ids() {
		return self::sanitize_fallback_image_ids( get_option( 'wem_fallback_image_ids', array() ) );
	}

	/**
	 * Sanitize fallback image IDs.
	 *
	 * @param mixed $raw Raw IDs
	 *
	 * @return array
	 */
	private static function sanitize_fallback_image_ids( $raw ) {
		if ( is_string( $raw ) ) {
			$raw = preg_split( '/[\s,]+/', $raw );
		}

		if ( ! is_array( $raw ) ) {
			return array();
		}

		$ids = array();
		foreach ( $raw as $id ) {
			$id = absint( $id );
			if ( $id && wp_attachment_is_image( $id ) ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}
}
