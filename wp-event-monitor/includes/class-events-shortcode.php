<?php
/**
 * WEM_Events_Shortcode
 *
 * Renders a standalone events page that does not depend on ACF or Elementor loops.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WEM_Events_Shortcode {

	/**
	 * Register shortcodes.
	 */
	public static function init() {
		add_shortcode( 'wem_events', array( __CLASS__, 'render' ) );
		add_action( 'init', array( __CLASS__, 'maybe_create_sample_events' ), 20 );
	}

	/**
	 * Create sample events once after activation so the schedule UI is visible immediately.
	 */
	public static function maybe_create_sample_events() {
		if ( get_option( 'wem_sample_events_created' ) ) {
			return;
		}

		if ( ! post_type_exists( 'event' ) ) {
			return;
		}

		$image_url = 'https://neli.digirelation.dev/wp-content/uploads/2026/04/Event-2.webp';
		$base = current_time( 'timestamp' );
		$samples = array(
			array(
				'title' => 'Test: Familienmarkt am See',
				'content' => 'Ein Testevent mit einem einzelnen Termin.',
				'city' => 'Bregenz',
				'terms' => array( 'Category Tag 1', 'Category Tag 3' ),
				'schedule' => array(
					array( 'date' => date_i18n( 'Ymd', strtotime( '+4 days', $base ) ), 'time' => '12:00 - 15:00' ),
				),
			),
			array(
				'title' => 'Test: Kreativwerkstatt mit weiteren Terminen',
				'content' => 'Ein Testevent mit mehreren einzelnen Terminen und Hover-Popup.',
				'city' => 'Feldkirch',
				'terms' => array( 'Category Tag 1', 'Category Tag 2' ),
				'schedule' => array(
					array( 'date' => date_i18n( 'Ymd', strtotime( '+5 days', $base ) ), 'time' => '15:00 - 18:00' ),
					array( 'date' => date_i18n( 'Ymd', strtotime( '+8 days', $base ) ), 'time' => '15:00 - 18:00' ),
					array( 'date' => date_i18n( 'Ymd', strtotime( '+12 days', $base ) ), 'time' => '15:00 - 18:00' ),
					array( 'date' => date_i18n( 'Ymd', strtotime( '+15 days', $base ) ), 'time' => '15:00 - 18:00' ),
					array( 'date' => date_i18n( 'Ymd', strtotime( '+19 days', $base ) ), 'time' => '15:00 - 18:00' ),
				),
			),
			array(
				'title' => 'Test: Ferienprogramm mehrere Tage',
				'content' => 'Ein Testevent, das mehrere Tage hintereinander dauert.',
				'city' => 'Dornbirn',
				'terms' => array( 'Category Tag 2', 'Category Tag 3' ),
				'schedule' => array(
					array( 'date' => date_i18n( 'Ymd', strtotime( '+10 days', $base ) ), 'time' => '09:00 - 12:00' ),
					array( 'date' => date_i18n( 'Ymd', strtotime( '+11 days', $base ) ), 'time' => '09:00 - 12:00' ),
					array( 'date' => date_i18n( 'Ymd', strtotime( '+12 days', $base ) ), 'time' => '09:00 - 12:00' ),
					array( 'date' => date_i18n( 'Ymd', strtotime( '+13 days', $base ) ), 'time' => '09:00 - 12:00' ),
				),
			),
		);

		foreach ( $samples as $sample ) {
			self::create_sample_event( $sample, $image_url );
		}

		update_option( 'wem_sample_events_created', '1' );
		delete_option( 'wem_create_sample_events' );
	}

	/**
	 * Insert one sample event if it does not already exist.
	 *
	 * @param array  $sample Sample data
	 * @param string $image_url External sample image URL
	 */
	private static function create_sample_event( $sample, $image_url ) {
		$existing = new WP_Query(
			array(
				'post_type' => 'event',
				'post_status' => 'any',
				'posts_per_page' => 1,
				'fields' => 'ids',
				'meta_key' => '_wem_sample_event',
				'meta_value' => sanitize_title( $sample['title'] ),
				'no_found_rows' => true,
			)
		);

		if ( ! empty( $existing->posts ) ) {
			return;
		}

		$schedule = $sample['schedule'];
		$first = reset( $schedule );
		$post_id = wp_insert_post(
			array(
				'post_type' => 'event',
				'post_status' => 'publish',
				'post_title' => sanitize_text_field( $sample['title'] ),
				'post_content' => wp_kses_post( $sample['content'] ),
				'post_excerpt' => wp_trim_words( wp_strip_all_tags( $sample['content'] ), 18, '...' ),
			)
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return;
		}

		update_post_meta( $post_id, '_wem_sample_event', sanitize_title( $sample['title'] ) );
		update_post_meta( $post_id, '_wem_event_date', $first['date'] );
		update_post_meta( $post_id, '_wem_event_time', $first['time'] );
		update_post_meta( $post_id, '_wem_event_schedule', $schedule );
		update_post_meta( $post_id, '_wem_event_city', sanitize_text_field( $sample['city'] ) );
		update_post_meta( $post_id, '_wem_image_url', esc_url_raw( $image_url ) );
		update_post_meta( $post_id, '_em_image_credit_url', esc_url_raw( $image_url ) );

		wp_set_object_terms( $post_id, $sample['city'], 'city-name', false );
		wp_set_object_terms( $post_id, $sample['terms'], 'category-tag', false );
	}

	/**
	 * Render the events page.
	 *
	 * @param array $atts Shortcode attributes
	 *
	 * @return string HTML
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit' => 48,
				'featured' => 8,
				'status' => 'auto',
				'show_past' => '0',
			),
			$atts,
			'wem_events'
		);

		$filters = self::current_filters();
		$events = self::get_events( $atts, $filters );
		$featured = $events;
		$regular = $events;

		ob_start();
		self::print_styles_once();
		?>
		<section class="wem-events-page" data-wem-events>
			<div class="wem-events-hero">
				<div class="wem-events-hero-inner">
					<p class="wem-events-kicker"><?php echo esc_html__( 'Events', 'wp-event-monitor' ); ?></p>
					<h1><?php echo esc_html__( 'Weitere Events', 'wp-event-monitor' ); ?></h1>
					<p><?php echo esc_html__( 'Speziell für Sie ausgewählte Erlebnisse, die bald stattfinden werden', 'wp-event-monitor' ); ?></p>
					<?php self::render_filters( $filters, 'hero' ); ?>
				</div>
			</div>

			<div class="wem-events-section">
				<div class="wem-events-section-head">
					<div>
						<h2><?php echo esc_html__( 'Kommende Veranstaltungen', 'wp-event-monitor' ); ?></h2>
						<p><?php echo esc_html__( 'Speziell für Sie ausgewählte Erlebnisse, die bald stattfinden werden', 'wp-event-monitor' ); ?></p>
					</div>
					<?php if ( count( $featured ) > 1 ) : ?>
						<div class="wem-featured-controls" aria-label="<?php echo esc_attr__( 'Carousel controls', 'wp-event-monitor' ); ?>">
							<button type="button" class="wem-featured-arrow" data-wem-carousel-prev aria-label="<?php echo esc_attr__( 'Vorherige Veranstaltung', 'wp-event-monitor' ); ?>"><span aria-hidden="true">&larr;</span></button>
							<button type="button" class="wem-featured-arrow next" data-wem-carousel-next aria-label="<?php echo esc_attr__( 'Naechste Veranstaltung', 'wp-event-monitor' ); ?>"><span aria-hidden="true">&rarr;</span></button>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $featured ) ) : ?>
					<div class="wem-featured-carousel" data-wem-carousel>
						<div class="wem-featured-events" data-wem-carousel-track>
							<?php foreach ( $featured as $index => $event ) : ?>
								<?php self::render_event_card( $event, 0 === $index ? 'featured' : 'featured small' ); ?>
							<?php endforeach; ?>
						</div>
						<?php if ( count( $featured ) > 1 ) : ?>
							<div class="wem-featured-dots" data-wem-carousel-dots>
								<?php foreach ( $featured as $index => $event ) : ?>
									<button type="button" class="<?php echo 0 === $index ? 'is-active' : ''; ?>" data-wem-carousel-dot="<?php echo esc_attr( $index ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Go to slide %d', 'wp-event-monitor' ), $index + 1 ) ); ?>"></button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="wem-events-section wem-events-section-grid">
				<div class="wem-events-section-head">
					<div>
						<h2><?php echo esc_html__( 'Weitere Events', 'wp-event-monitor' ); ?></h2>
						<p><?php echo esc_html__( 'Speziell für Sie ausgewählte Erlebnisse, die bald stattfinden werden', 'wp-event-monitor' ); ?></p>
					</div>
				</div>
				<?php self::render_filters( $filters, 'grid' ); ?>

				<?php if ( empty( $events ) ) : ?>
					<div class="wem-events-empty"><?php echo esc_html__( 'Keine Veranstaltungen gefunden.', 'wp-event-monitor' ); ?></div>
				<?php else : ?>
					<div class="wem-events-grid">
						<?php foreach ( $regular as $index => $event ) : ?>
							<?php self::render_event_card( $event, $index >= 9 ? 'is-extra' : '' ); ?>
						<?php endforeach; ?>
					</div>

					<?php if ( count( $regular ) > 9 ) : ?>
						<button class="wem-events-more" type="button" data-wem-show-more><?php echo esc_html__( 'Mehr Events anzeigen', 'wp-event-monitor' ); ?></button>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<?php self::render_contact_block(); ?>
		</section>
		<script>
			(function () {
				function initRoot(root) {
					if (!root) return;
					root.dataset.wemReady = '1';
					var carousel = root.querySelector('[data-wem-carousel]');
					if (carousel) {
						var track = carousel.querySelector('[data-wem-carousel-track]');
						var cards = track ? Array.prototype.slice.call(track.querySelectorAll('.wem-event-card')) : [];
						var prev = root.querySelector('[data-wem-carousel-prev]');
						var next = root.querySelector('[data-wem-carousel-next]');
						var dots = Array.prototype.slice.call(root.querySelectorAll('[data-wem-carousel-dot]'));
						var index = 0;
						track.querySelectorAll('a').forEach(function (link) {
							link.setAttribute('draggable', 'false');
						});

						track.querySelectorAll('img').forEach(function (image) {
							image.setAttribute('draggable', 'false');
						});

						function stepSize() {
							if (!track || !cards.length) return 0;
							var style = window.getComputedStyle(track);
							var gap = parseFloat(style.columnGap || style.gap || 0);
							return cards[0].getBoundingClientRect().width + gap;
						}

						function scrollToIndex(nextIndex, smooth) {
							if (!track || !cards.length) return;
							index = ((nextIndex % cards.length) + cards.length) % cards.length;
							track.scrollTo({
								left: index * stepSize(),
								behavior: smooth ? 'smooth' : 'auto'
							});
							updateDots();
						}

						function updateDots() {
							dots.forEach(function (dot, dotIndex) { dot.classList.toggle('is-active', dotIndex === index); });
						}

						function updateCarousel() {
							if (!track || !cards.length) return;
							scrollToIndex(index, false);
						}

						if (prev) prev.addEventListener('click', function () { scrollToIndex(index - 1, true); });
						if (next) next.addEventListener('click', function () { scrollToIndex(index + 1, true); });
						dots.forEach(function (dot) { dot.addEventListener('click', function () { scrollToIndex(parseInt(dot.getAttribute('data-wem-carousel-dot'), 10) || 0, true); }); });
						track.addEventListener('scroll', function () {
							window.clearTimeout(track.wemScrollTimer);
							track.wemScrollTimer = window.setTimeout(function () {
								var step = stepSize();
								if (!step) return;
								index = Math.max(0, Math.min(cards.length - 1, Math.round(track.scrollLeft / step)));
								updateDots();
							}, 80);
						});
						window.addEventListener('resize', updateCarousel);
						scrollToIndex(0, false);
						updateDots();
					}

					root.querySelectorAll('.wem-event-credit').forEach(function (credit) {
						credit.addEventListener('click', function (event) {
							event.preventDefault();
							event.stopPropagation();
							credit.classList.toggle('is-open');
						});
						credit.addEventListener('keydown', function (event) {
							if ('Enter' !== event.key && ' ' !== event.key) return;
							event.preventDefault();
							credit.classList.toggle('is-open');
						});
					});

					var button = root.querySelector('[data-wem-show-more]');
					if (button) {
						button.addEventListener('click', function () {
							root.querySelectorAll('.wem-event-card.is-extra').forEach(function (card) {
								card.classList.remove('is-extra');
							});
							button.remove();
						});
					}

					var activeModal = null;
					var previousFocus = null;

					function closeEventModal() {
						if (!activeModal) return;
						activeModal.remove();
						activeModal = null;
						document.documentElement.classList.remove('wem-event-modal-open');
						if (previousFocus && previousFocus.focus) previousFocus.focus();
					}

					function openEventModal(trigger) {
						var template = trigger ? trigger.nextElementSibling : null;
						if (!template || 'TEMPLATE' !== template.tagName) return;
						closeEventModal();
						previousFocus = document.activeElement;
						var overlay = document.createElement('div');
						overlay.className = 'wem-event-modal-overlay';
						overlay.setAttribute('data-wem-modal-overlay', '');
						overlay.appendChild(template.content.cloneNode(true));
						document.body.appendChild(overlay);
						document.documentElement.classList.add('wem-event-modal-open');
						activeModal = overlay;
						var close = overlay.querySelector('[data-wem-modal-close]');
						if (close) close.focus();
					}

					root.querySelectorAll('[data-wem-modal-trigger]').forEach(function (card) {
						card.addEventListener('click', function (event) {
							if (event.target.closest('.wem-event-credit, .wem-event-schedule, .wem-event-action')) return;
							event.preventDefault();
							openEventModal(card);
						});
						card.addEventListener('keydown', function (event) {
							if ('Enter' !== event.key && ' ' !== event.key) return;
							if (event.target.closest('.wem-event-credit, .wem-event-schedule, .wem-event-action')) return;
							event.preventDefault();
							openEventModal(card);
						});
					});

					document.addEventListener('click', function (event) {
						if (!activeModal) return;
						if (event.target.matches('[data-wem-modal-overlay], [data-wem-modal-close]')) {
							event.preventDefault();
							closeEventModal();
						}
					});

					document.addEventListener('keydown', function (event) {
						if ('Escape' === event.key) closeEventModal();
					});

					var forms = Array.prototype.slice.call(root.querySelectorAll('.wem-events-filters'));
					var ajaxTimer = null;

					function syncForms(sourceForm) {
						var data = new FormData(sourceForm);
						forms.forEach(function (form) {
							if (form === sourceForm) return;
							Array.prototype.slice.call(form.elements).forEach(function (field) {
								if (!field.name || !data.has(field.name)) return;
								field.value = data.get(field.name);
							});
						});
					}

					function ajaxFilter(sourceForm, pushState) {
						syncForms(sourceForm);
						var params = new URLSearchParams();
						forms.forEach(function (form) {
							Array.prototype.slice.call(form.elements).forEach(function (field) {
								if (!field.name || 'submit' === field.type || !field.value) return;
								params.set(field.name, field.value);
							});
						});
						var url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
						root.classList.add('is-loading');
						fetch(url, { credentials: 'same-origin' })
							.then(function (response) { return response.text(); })
							.then(function (html) {
								var doc = new DOMParser().parseFromString(html, 'text/html');
								var nextRoot = doc.querySelector('[data-wem-events]');
								if (!nextRoot) {
									window.location.href = url;
									return;
								}
								root.innerHTML = nextRoot.innerHTML;
								root.classList.remove('is-loading');
								if (pushState) window.history.pushState({}, '', url);
								initRoot(root);
							})
							.catch(function () {
								window.location.href = url;
							});
					}

					function formatDateLabel(value) {
						if (!value) return 'Datum auswählen';
						var parts = value.split('-');
						if (parts.length !== 3) return value;
						return parts[2] + '.' + parts[1] + '.' + parts[0];
					}

					function shiftDate(value, days) {
						var date = value ? new Date(value + 'T12:00:00') : new Date();
						if (Number.isNaN(date.getTime())) date = new Date();
						date.setDate(date.getDate() + days);
						var year = date.getFullYear();
						var month = String(date.getMonth() + 1).padStart(2, '0');
						var day = String(date.getDate()).padStart(2, '0');
						return year + '-' + month + '-' + day;
					}

					forms.forEach(function (form) {
						var datePeriod = form.querySelector('.wem-events-date-period');
						if (!datePeriod) return;
						var dateInput = datePeriod.querySelector('[data-wem-date-input]');
						var dateLabel = datePeriod.querySelector('[data-wem-date-label]');
						var prevDate = datePeriod.querySelector('[data-wem-date-prev]');
						var nextDate = datePeriod.querySelector('[data-wem-date-next]');

						function setDate(value, shouldFilter) {
							if (!dateInput) return;
							dateInput.value = value;
							if (dateLabel) dateLabel.textContent = formatDateLabel(value);
							if (shouldFilter) ajaxFilter(form, true);
						}

						function openDatePicker() {
							if (!dateInput) return;
							dateInput.focus();
							if (dateInput.showPicker) {
								try { dateInput.showPicker(); } catch (error) {}
							}
						}

						if (datePeriod) {
							datePeriod.addEventListener('click', function (event) {
								if (event.target.closest('[data-wem-date-prev], [data-wem-date-next]')) return;
								openDatePicker();
							});
						}

						if (dateLabel) {
							dateLabel.addEventListener('click', openDatePicker);
						}

						if (prevDate) {
							prevDate.addEventListener('click', function () {
								setDate(shiftDate(dateInput ? dateInput.value : '', -1), true);
							});
						}

						if (nextDate) {
							nextDate.addEventListener('click', function () {
								setDate(shiftDate(dateInput ? dateInput.value : '', 1), true);
							});
						}

						if (dateInput && dateLabel) {
							dateLabel.textContent = formatDateLabel(dateInput.value);
						}
					});

					forms.forEach(function (form) {
						form.addEventListener('submit', function (event) {
							event.preventDefault();
							ajaxFilter(form, true);
						});
						Array.prototype.slice.call(form.elements).forEach(function (field) {
							if (!field.name || 'submit' === field.type) return;
							field.addEventListener('change', function () { ajaxFilter(form, true); });
							if ('search' === field.type || 'text' === field.type) {
								field.addEventListener('input', function () {
									window.clearTimeout(ajaxTimer);
									ajaxTimer = window.setTimeout(function () { ajaxFilter(form, true); }, 350);
								});
							}
						});
					});
				}

				window.WEMEventsInit = function () {
					document.querySelectorAll('[data-wem-events]').forEach(initRoot);
				};

				window.addEventListener('popstate', function () {
					var root = document.querySelector('[data-wem-events]');
					if (!root) return;
					fetch(window.location.href, { credentials: 'same-origin' })
						.then(function (response) { return response.text(); })
						.then(function (html) {
							var doc = new DOMParser().parseFromString(html, 'text/html');
							var nextRoot = doc.querySelector('[data-wem-events]');
							if (!nextRoot) return;
							root.innerHTML = nextRoot.innerHTML;
							initRoot(root);
						});
				});

				window.WEMEventsInit();
			})();
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Read current GET filters.
	 *
	 * @return array Filter map
	 */
	private static function current_filters() {
		return array(
			'q' => isset( $_GET['wem_q'] ) ? sanitize_text_field( wp_unslash( $_GET['wem_q'] ) ) : '',
			'category' => self::request_filter_value( array( 'wem_category', 'wem_type', 'type', 'category' ) ),
			'city' => self::request_filter_value( array( 'wem_city', 'city' ) ),
			'date' => isset( $_GET['wem_date'] ) ? sanitize_text_field( wp_unslash( $_GET['wem_date'] ) ) : '',
		);
	}

	/**
	 * Read the first populated request filter value from a list of compatible names.
	 *
	 * @param array $names Request keys
	 *
	 * @return string Normalized filter value
	 */
	private static function request_filter_value( $names ) {
		foreach ( $names as $name ) {
			if ( empty( $_GET[ $name ] ) ) {
				continue;
			}

			$value = sanitize_text_field( wp_unslash( $_GET[ $name ] ) );
			if ( '' !== $value ) {
				return self::normalize_filter_value( $value );
			}
		}

		return '';
	}

	/**
	 * Normalize user-provided taxonomy filter values to match slugs or term names.
	 *
	 * @param string $value Raw filter value
	 *
	 * @return string
	 */
	private static function normalize_filter_value( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		return sanitize_title( $value );
	}

	/**
	 * Query and filter event posts.
	 *
	 * @param array $atts Shortcode attributes
	 * @param array $filters Current filters
	 *
	 * @return array Event view models
	 */
	private static function get_events( $atts, $filters ) {
		$query = new WP_Query(
			array(
				'post_type' => 'event',
				'post_status' => self::post_statuses( $atts['status'] ),
				'posts_per_page' => min( 120, max( 1, (int) $atts['limit'] ) ),
				's' => $filters['q'],
				'orderby' => 'date',
				'order' => 'DESC',
				'no_found_rows' => true,
			)
		);

		$events = array();
		$today = gmdate( 'Ymd' );

		foreach ( $query->posts as $post ) {
			$event = self::event_view_model( $post );

			if ( '1' !== (string) $atts['show_past'] && ! empty( $event['last_date_raw'] ) && $event['last_date_raw'] < $today ) {
				continue;
			}

			if ( ! empty( $filters['category'] ) && ! in_array( $filters['category'], $event['category_keys'], true ) ) {
				continue;
			}

			if ( ! empty( $filters['city'] ) && ! in_array( $filters['city'], $event['city_keys'], true ) ) {
				continue;
			}

			if ( ! empty( $filters['date'] ) && ! in_array( self::filter_date_to_raw( $filters['date'] ), $event['schedule_dates'], true ) ) {
				continue;
			}

			$events[] = $event;
		}

		usort(
			$events,
			function ( $a, $b ) {
				$a_date = ! empty( $a['date_raw'] ) ? $a['date_raw'] : '99999999';
				$b_date = ! empty( $b['date_raw'] ) ? $b['date_raw'] : '99999999';

				return strcmp( $a_date, $b_date );
			}
		);

		return self::dedupe_events( $events );
	}

	/**
	 * Remove imported duplicate events from the public listing.
	 *
	 * @param array $events Event view models
	 *
	 * @return array Deduplicated event view models
	 */
	private static function dedupe_events( $events ) {
		$seen = array();
		$unique = array();

		foreach ( $events as $event ) {
			$key = implode(
				'|',
				array(
					sanitize_title( $event['title'] ),
					! empty( $event['date_raw'] ) ? $event['date_raw'] : '',
					! empty( $event['time'] ) ? sanitize_text_field( $event['time'] ) : '',
					! empty( $event['city_slug'] ) ? sanitize_key( $event['city_slug'] ) : '',
				)
			);

			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$unique[] = $event;
		}

		return $unique;
	}

	/**
	 * Resolve shortcode post status setting.
	 *
	 * Auto mode keeps public pages safe, but lets editors preview imported drafts.
	 *
	 * @param string $status Raw shortcode status attribute
	 *
	 * @return string|array WP_Query post_status value
	 */
	private static function post_statuses( $status ) {
		$status = strtolower( trim( (string) $status ) );

		if ( 'auto' === $status || '' === $status ) {
			return current_user_can( 'edit_posts' ) ? array( 'publish', 'draft', 'pending', 'future' ) : 'publish';
		}

		$allowed = array( 'publish', 'draft', 'pending', 'future' );
		$statuses = array();

		foreach ( explode( ',', $status ) as $candidate ) {
			$candidate = sanitize_key( trim( $candidate ) );
			if ( in_array( $candidate, $allowed, true ) ) {
				$statuses[] = $candidate;
			}
		}

		if ( empty( $statuses ) ) {
			return 'publish';
		}

		return count( $statuses ) === 1 ? $statuses[0] : $statuses;
	}

	/**
	 * Build one event card view model.
	 *
	 * @param WP_Post $post Event post
	 *
	 * @return array View model
	 */
	private static function event_view_model( $post ) {
		$date = self::meta_first( $post->ID, array( '_wem_event_date', 'event_date' ) );
		$time = self::meta_first( $post->ID, array( '_wem_event_time', 'event_time' ) );
		$schedule = self::event_schedule( $post->ID, $date, $time );
		$primary_term = ! empty( $schedule ) ? reset( $schedule ) : array( 'date' => $date, 'time' => $time );
		$event_url = self::meta_first( $post->ID, array( '_wem_event_url', '_em_event_url' ) );
		$ticket_url = get_post_meta( $post->ID, '_wem_ticket_url', true );
		$credit_url = self::meta_first( $post->ID, array( '_em_image_credit_url', '_wem_source_url', '_em_source_url' ) );
		$terms = get_the_terms( $post->ID, 'category-tag' );
		$cities = get_the_terms( $post->ID, 'city-name' );
		$city = ! empty( $cities ) && ! is_wp_error( $cities ) ? reset( $cities ) : null;
		$body = self::clean_event_content( ! empty( $post->post_content ) ? $post->post_content : self::meta_first( $post->ID, array( '_wem_about_event', 'about_event' ) ) );
		$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $body ), 22, '...' );
		$location = self::meta_first( $post->ID, array( '_wem_event_location', 'event_location', '_wem_location', 'location' ) );
		$contact = self::meta_first( $post->ID, array( '_wem_event_contact', 'event_contact', '_wem_contact', 'contact' ) );
		$image = get_the_post_thumbnail_url( $post, 'large' ) ?: self::meta_first( $post->ID, array( '_wem_image_url' ) );

		return array(
			'id' => $post->ID,
			'title' => get_the_title( $post ),
			'permalink' => get_permalink( $post ),
			'content' => wpautop( wp_kses_post( $body ) ),
			'excerpt' => $excerpt,
			'date_raw' => $primary_term['date'],
			'date_label' => self::format_modal_date( $primary_term['date'] ),
			'last_date_raw' => ! empty( $schedule ) ? end( $schedule )['date'] : $date,
			'day' => self::date_part( $primary_term['date'], 'j' ),
			'month' => self::date_part( $primary_term['date'], 'M' ),
			'time' => $primary_term['time'],
			'schedule' => $schedule,
			'schedule_dates' => wp_list_pluck( $schedule, 'date' ),
			'event_url' => $event_url,
			'ticket_url' => $ticket_url,
			'credit_url' => ! empty( $image ) ? $credit_url : '',
			'image' => $image,
			'has_image' => ! empty( $image ),
			'terms' => ! empty( $terms ) && ! is_wp_error( $terms ) ? array_slice( $terms, 0, 3 ) : array(),
			'category_slugs' => ! empty( $terms ) && ! is_wp_error( $terms ) ? wp_list_pluck( $terms, 'slug' ) : array(),
			'category_keys' => self::term_filter_keys( $terms ),
			'city' => $city ? $city->name : get_post_meta( $post->ID, '_wem_event_city', true ),
			'city_slug' => $city ? $city->slug : sanitize_title( get_post_meta( $post->ID, '_wem_event_city', true ) ),
			'city_keys' => $city ? self::term_filter_keys( array( $city ) ) : array_filter( array( sanitize_title( get_post_meta( $post->ID, '_wem_event_city', true ) ) ) ),
			'location' => $location,
			'contact' => $contact,
		);
	}

	/**
	 * Build comparable filter keys from taxonomy terms.
	 *
	 * @param array|WP_Error|false $terms Terms
	 *
	 * @return array
	 */
	private static function term_filter_keys( $terms ) {
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		$keys = array();

		foreach ( $terms as $term ) {
			if ( ! empty( $term->slug ) ) {
				$keys[] = self::normalize_filter_value( $term->slug );
			}

			if ( ! empty( $term->name ) ) {
				$keys[] = self::normalize_filter_value( $term->name );
			}
		}

		return array_values( array_unique( array_filter( $keys ) ) );
	}

	/**
	 * Build a sorted list of event occurrences.
	 *
	 * @param int    $post_id Post ID
	 * @param string $fallback_date Primary event date
	 * @param string $fallback_time Primary event time
	 *
	 * @return array
	 */
	private static function event_schedule( $post_id, $fallback_date, $fallback_time ) {
		$raw = get_post_meta( $post_id, '_wem_event_schedule', true );
		$schedule = array();

		if ( is_array( $raw ) ) {
			foreach ( $raw as $term ) {
				if ( ! is_array( $term ) ) {
					continue;
				}

				$date = self::filter_date_to_raw( $term['date'] ?? '' );
				if ( empty( $date ) ) {
					continue;
				}

				$schedule[] = array(
					'date' => $date,
					'time' => sanitize_text_field( $term['time'] ?? $fallback_time ),
				);
			}
		}

		if ( empty( $schedule ) && ! empty( $fallback_date ) ) {
			$schedule[] = array(
				'date' => self::filter_date_to_raw( $fallback_date ),
				'time' => sanitize_text_field( $fallback_time ),
			);
		}

		usort(
			$schedule,
			function ( $a, $b ) {
				return strcmp( $a['date'], $b['date'] );
			}
		);

		return $schedule;
	}

	/**
	 * Render filters.
	 *
	 * @param array $filters Current filters
	 */
	private static function render_filters( $filters, $variant = 'grid' ) {
		$categories = get_terms( array( 'taxonomy' => 'category-tag', 'hide_empty' => true ) );
		$cities = get_terms( array( 'taxonomy' => 'city-name', 'hide_empty' => true ) );
		$reset_url = remove_query_arg( array( 'wem_q', 'wem_category', 'wem_type', 'type', 'category', 'wem_city', 'city', 'wem_date' ) );
		$has_active_filters = ! empty( $filters['q'] ) || ! empty( $filters['category'] ) || ! empty( $filters['city'] ) || ! empty( $filters['date'] );
		?>
		<form class="wem-events-filters wem-events-filters-<?php echo esc_attr( $variant ); ?>" method="get">
			<label>
				<span class="screen-reader-text"><?php echo esc_html__( 'Suche', 'wp-event-monitor' ); ?></span>
				<input type="search" name="wem_q" value="<?php echo esc_attr( $filters['q'] ); ?>" placeholder="<?php echo esc_attr__( 'Finde Veranstaltungen in deiner Stadt', 'wp-event-monitor' ); ?>">
			</label>

			<button type="submit"><?php echo esc_html__( 'Suchen', 'wp-event-monitor' ); ?></button>

			<?php if ( 'grid' === $variant ) : ?>
			<select name="wem_city" aria-label="<?php echo esc_attr__( 'Ort', 'wp-event-monitor' ); ?>">
				<option value=""><?php echo esc_html__( 'Alle Städte', 'wp-event-monitor' ); ?></option>
				<?php if ( ! is_wp_error( $cities ) ) : ?>
					<?php foreach ( $cities as $city ) : ?>
						<option value="<?php echo esc_attr( $city->slug ); ?>" <?php selected( $filters['city'], self::normalize_filter_value( $city->slug ) ); ?>><?php echo esc_html( $city->name ); ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>

			<select name="wem_category" aria-label="<?php echo esc_attr__( 'Kategorie', 'wp-event-monitor' ); ?>">
				<option value=""><?php echo esc_html__( 'Alle Kategorien', 'wp-event-monitor' ); ?></option>
				<?php if ( ! is_wp_error( $categories ) ) : ?>
					<?php foreach ( $categories as $category ) : ?>
						<option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $filters['category'], self::normalize_filter_value( $category->slug ) ); ?>><?php echo esc_html( $category->name ); ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>

			<?php
			$date_label = __( 'Datum auswählen', 'wp-event-monitor' );
			if ( ! empty( $filters['date'] ) ) {
				$date_timestamp = strtotime( $filters['date'] );
				if ( $date_timestamp ) {
					$date_label = date_i18n( 'd.m.Y', $date_timestamp );
				}
			}
			?>
			<div class="wem-events-date-filter wem-events-date-period">
				<button type="button" class="wem-events-date-step prev" data-wem-date-prev aria-label="<?php echo esc_attr__( 'Vorheriger Tag', 'wp-event-monitor' ); ?>"></button>
				<label class="wem-events-date-picker">
					<span class="screen-reader-text"><?php echo esc_html__( 'Datum', 'wp-event-monitor' ); ?></span>
					<span class="wem-events-date-button" data-wem-date-label><?php echo esc_html( $date_label ); ?></span>
					<input type="date" name="wem_date" value="<?php echo esc_attr( $filters['date'] ); ?>" data-wem-date-input>
				</label>
				<button type="button" class="wem-events-date-step next" data-wem-date-next aria-label="<?php echo esc_attr__( 'Nächster Tag', 'wp-event-monitor' ); ?>"></button>
			</div>
			<?php if ( $has_active_filters ) : ?>
				<a class="wem-events-reset" href="<?php echo esc_url( $reset_url ); ?>"><?php echo esc_html__( 'Zuruecksetzen', 'wp-event-monitor' ); ?></a>
			<?php endif; ?>
			<?php endif; ?>
		</form>
		<?php
	}

	/**
	 * Render one event card.
	 *
	 * @param array  $event Event view model
	 * @param string $variant Card variant classes
	 */
	private static function render_event_card( $event, $variant = '' ) {
		$classes = trim( 'wem-event-card ' . $variant . ( empty( $event['has_image'] ) ? ' has-placeholder-image' : '' ) );
		$action_url = ! empty( $event['event_url'] ) ? $event['event_url'] : ( ! empty( $event['ticket_url'] ) ? $event['ticket_url'] : $event['permalink'] );
		$action_label = ! empty( $event['ticket_url'] ) ? __( 'Ticket kaufen', 'wp-event-monitor' ) : __( 'Mehr erfahren', 'wp-event-monitor' );
		$image_style = ! empty( $event['image'] ) ? ' style="background-image:url(' . esc_url( $event['image'] ) . ')"' : '';
		$credit_host = ! empty( $event['credit_url'] ) ? wp_parse_url( $event['credit_url'], PHP_URL_HOST ) : '';
		$credit_label = $credit_host ? $credit_host : __( 'Test photo source', 'wp-event-monitor' );
		?>
		<article class="<?php echo esc_attr( $classes ); ?>" data-wem-modal-trigger tabindex="0">
			<a class="wem-event-card-image" href="<?php echo esc_url( $action_url ); ?>"<?php echo $image_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<span class="wem-event-date">
					<strong><?php echo esc_html( $event['day'] ? $event['day'] : '--' ); ?></strong>
					<small><?php echo esc_html( $event['month'] ? $event['month'] : '' ); ?></small>
				</span>
				<?php if ( ! empty( $event['has_image'] ) && ! empty( $event['credit_url'] ) ) : ?>
					<span class="wem-event-credit" title="<?php echo esc_attr( $credit_label ); ?>" tabindex="0" role="button" aria-label="<?php echo esc_attr__( 'Image source', 'wp-event-monitor' ); ?>">
						<span class="wem-event-credit-symbol">&copy;</span>
						<span class="wem-event-credit-text"><?php echo esc_html__( 'Image source:', 'wp-event-monitor' ); ?> <?php echo esc_html( $credit_label ); ?></span>
					</span>
				<?php endif; ?>
			</a>

			<div class="wem-event-card-body">
				<?php if ( ! empty( $event['terms'] ) ) : ?>
					<div class="wem-event-tags">
						<?php foreach ( $event['terms'] as $term ) : ?>
							<span><?php echo esc_html( $term->name ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<h3><a href="<?php echo esc_url( $event['permalink'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a></h3>

				<?php if ( ! empty( $event['excerpt'] ) ) : ?>
					<p><?php echo esc_html( $event['excerpt'] ); ?></p>
				<?php endif; ?>

				<div class="wem-event-meta">
					<?php if ( ! empty( $event['city'] ) ) : ?>
						<span class="wem-event-location"><?php echo esc_html( $event['city'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $event['time'] ) ) : ?>
						<span class="wem-event-time"><?php echo esc_html( $event['time'] ); ?></span>
					<?php endif; ?>
					<?php if ( count( $event['schedule'] ) > 1 ) : ?>
						<span class="wem-event-schedule" tabindex="0">
							<span class="wem-event-schedule-trigger">
								<?php echo esc_html( sprintf( __( '%d weitere Termine', 'wp-event-monitor' ), count( $event['schedule'] ) - 1 ) ); ?>
							</span>
							<span class="wem-event-schedule-popover" role="tooltip">
								<?php foreach ( $event['schedule'] as $term ) : ?>
									<span class="wem-event-schedule-row">
										<strong><?php echo esc_html( self::date_part( $term['date'], 'D' ) ); ?></strong>
										<span><?php echo esc_html( self::format_schedule_date( $term['date'] ) ); ?></span>
										<span><?php echo esc_html( $term['time'] ); ?></span>
									</span>
								<?php endforeach; ?>
							</span>
						</span>
					<?php endif; ?>
				</div>

				<a class="wem-event-action" href="<?php echo esc_url( $action_url ); ?>" <?php echo ! empty( $event['ticket_url'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
					<?php echo esc_html( $action_label ); ?>
				</a>
			</div>
		</article>
		<template data-wem-modal-template>
			<?php self::render_event_modal( $event, $action_url ); ?>
		</template>
		<?php
	}

	/**
	 * Render one event modal template.
	 *
	 * @param array  $event Event view model
	 * @param string $action_url Primary action URL
	 */
	private static function render_event_modal( $event, $action_url ) {
		$image_style = ! empty( $event['image'] ) ? ' style="background-image:url(' . esc_url( $event['image'] ) . ')"' : '';
		$route_url = ! empty( $event['location'] ) ? self::route_url( $event['location'] ) : '';
		$calendar_url = self::calendar_url( $event );
		?>
		<div class="wem-event-modal" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $event['title'] ); ?>">
			<div class="wem-event-modal-hero <?php echo empty( $event['has_image'] ) ? 'has-placeholder-image' : ''; ?>"<?php echo $image_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<span class="wem-event-date">
					<strong><?php echo esc_html( $event['day'] ? $event['day'] : '--' ); ?></strong>
					<small><?php echo esc_html( $event['month'] ? $event['month'] : '' ); ?></small>
				</span>
				<button type="button" class="wem-event-modal-close" data-wem-modal-close aria-label="<?php echo esc_attr__( 'Schliessen', 'wp-event-monitor' ); ?>"></button>
				<div class="wem-event-modal-hero-content">
					<h2><?php echo esc_html( $event['title'] ); ?></h2>
					<div class="wem-event-modal-social" aria-label="<?php echo esc_attr__( 'Social links', 'wp-event-monitor' ); ?>">
						<a class="instagram" href="https://www.instagram.com/neli_netzwerk_eltern_inklusion/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"></a>
						<a class="facebook" href="https://www.facebook.com/NetzwerkElternInklusion" target="_blank" rel="noopener noreferrer" aria-label="Facebook"></a>
						<span class="tiktok" aria-hidden="true"></span>
						<span class="twitter" aria-hidden="true"></span>
					</div>
				</div>
			</div>

			<div class="wem-event-modal-content">
				<?php if ( ! empty( $event['terms'] ) ) : ?>
					<div class="wem-event-tags">
						<?php foreach ( $event['terms'] as $term ) : ?>
							<span><?php echo esc_html( $term->name ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<section class="wem-event-modal-section">
					<h3><?php echo esc_html__( 'Details zur Veranstaltung:', 'wp-event-monitor' ); ?></h3>
					<div class="wem-event-modal-text">
						<?php echo ! empty( $event['content'] ) ? $event['content'] : '<p>' . esc_html( $event['excerpt'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</section>

				<section class="wem-event-modal-section">
					<h3><?php echo esc_html__( 'Datum & Ort:', 'wp-event-monitor' ); ?></h3>
					<?php if ( count( $event['schedule'] ) > 1 ) : ?>
						<div class="wem-event-modal-facts has-schedule">
							<?php if ( ! empty( $event['location'] ) ) : ?>
								<div class="wem-event-modal-fact location"><span></span><strong><?php echo esc_html( $event['location'] ); ?></strong></div>
							<?php endif; ?>
							<div class="wem-event-modal-schedule">
								<div class="wem-event-modal-schedule-head">
									<span></span>
									<strong><?php echo esc_html__( 'Termine', 'wp-event-monitor' ); ?></strong>
								</div>
								<div class="wem-event-modal-schedule-list">
									<?php foreach ( $event['schedule'] as $term ) : ?>
										<div class="wem-event-modal-schedule-row">
											<strong><?php echo esc_html( self::date_part( $term['date'], 'D' ) ); ?></strong>
											<span><?php echo esc_html( self::format_schedule_date( $term['date'] ) ); ?></span>
											<small><?php echo esc_html( ! empty( $term['time'] ) ? $term['time'] : __( 'Uhrzeit', 'wp-event-monitor' ) ); ?></small>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					<?php else : ?>
						<div class="wem-event-modal-facts">
							<?php if ( ! empty( $event['location'] ) ) : ?>
								<div class="wem-event-modal-fact location"><span></span><strong><?php echo esc_html( $event['location'] ); ?></strong></div>
							<?php endif; ?>
							<div class="wem-event-modal-fact calendar"><span></span><strong><?php echo esc_html( $event['date_label'] ? $event['date_label'] : __( 'Datum', 'wp-event-monitor' ) ); ?></strong></div>
							<div class="wem-event-modal-fact time"><span></span><strong><?php echo esc_html( $event['time'] ? $event['time'] : __( 'Uhrzeit', 'wp-event-monitor' ) ); ?></strong></div>
						</div>
					<?php endif; ?>
				</section>

				<section class="wem-event-modal-section">
					<h3><?php echo esc_html__( 'Anfahrt & Kalender:', 'wp-event-monitor' ); ?></h3>
					<div class="wem-event-modal-links">
						<?php if ( ! empty( $route_url ) ) : ?>
							<a class="route" href="<?php echo esc_url( $route_url ); ?>" target="_blank" rel="noopener noreferrer"><span></span><?php echo esc_html__( 'Route anzeigen', 'wp-event-monitor' ); ?></a>
						<?php endif; ?>
						<a class="calendar" href="<?php echo esc_url( $calendar_url ); ?>" target="_blank" rel="noopener noreferrer"><span></span><?php echo esc_html__( 'Zum Google Kalender hinzufügen', 'wp-event-monitor' ); ?></a>
					</div>
				</section>

				<div class="wem-event-modal-action-wrap">
					<a class="wem-event-modal-action" href="<?php echo esc_url( $action_url ); ?>" <?php echo ! empty( $event['ticket_url'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
						<?php echo esc_html__( 'Teilnehmen', 'wp-event-monitor' ); ?>
					</a>
				</div>

				<?php if ( ! empty( $event['contact'] ) ) : ?>
					<div class="wem-event-modal-divider"></div>
					<section class="wem-event-modal-section">
						<h3><?php echo esc_html__( 'Kontakt:', 'wp-event-monitor' ); ?></h3>
						<p><?php echo esc_html__( 'Bei Fragen zur Veranstaltung wenden Sie sich bitte direkt an den Veranstalter.', 'wp-event-monitor' ); ?></p>
						<p class="wem-event-modal-contact"><?php echo esc_html( $event['contact'] ); ?></p>
					</section>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the NELI contact block used below the events grid.
	 */
	private static function render_contact_block() {
		?>
		<section class="wem-events-contact">
			<div class="wem-events-contact-image">
				<img src="https://neli.digirelation.dev/wp-content/uploads/2026/04/img-block-kontakt.webp" alt="">
			</div>
			<div class="wem-events-contact-content">
				<h2><span>NELI</span> Vorarlberg</h2>
				<h3><?php echo esc_html__( 'Kontaktiere uns!', 'wp-event-monitor' ); ?></h3>
				<p>Ankenreuthe 353<br>A-6858 Bildstein<br>ZVR-Zahl 1386436685</p>
				<p><a href="tel:+4367763053160">+43 677 630 531 60</a><br><a href="mailto:info@neli-vorarlberg.at">info@neli-vorarlberg.at</a></p>
				<h3><?php echo esc_html__( 'Folge uns auf Social Media!', 'wp-event-monitor' ); ?></h3>
				<div class="wem-events-social">
					<a class="wem-social-facebook" href="https://www.facebook.com/NetzwerkElternInklusion" target="_blank" rel="noopener noreferrer" aria-label="Facebook"></a>
					<a class="wem-social-instagram" href="https://www.instagram.com/neli_netzwerk_eltern_inklusion/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"></a>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Get first non-empty meta value.
	 *
	 * @param int   $post_id Post ID
	 * @param array $keys Meta keys
	 *
	 * @return string
	 */
	private static function meta_first( $post_id, $keys ) {
		foreach ( $keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( '' !== (string) $value ) {
				return (string) $value;
			}
		}

		return '';
	}

	/**
	 * Remove scraper-added meta/details from the user-facing event description.
	 *
	 * @param string $content Raw event content
	 *
	 * @return string
	 */
	private static function clean_event_content( $content ) {
		$content = wp_kses_post( (string) $content );
		$content = preg_replace( '/<ul\b[^>]*>.*?(?:Buy Ticket|View Event|Source:|Time:|Phone:|Email:).*?<\/ul>/isu', '', $content );
		$content = preg_replace( '/<p>\s*(?:Buy Ticket|View Event|Source:|Time:|Phone:|Email:).*?<\/p>/isu', '', $content );
		$content = preg_replace( '/\b(?:Buy Ticket|View Event|Source:)\b.*$/imu', '', $content );

		return trim( $content );
	}

	/**
	 * Convert a date filter value to the stored Ymd format.
	 *
	 * @param string $date Date filter value
	 *
	 * @return string
	 */
	private static function filter_date_to_raw( $date ) {
		$date = trim( (string) $date );
		if ( preg_match( '/^\d{8}$/', $date ) ) {
			return $date;
		}

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return str_replace( '-', '', $date );
		}

		return '';
	}

	/**
	 * Format a Ymd date part.
	 *
	 * @param string $date Date in Ymd format
	 * @param string $format Date format
	 *
	 * @return string
	 */
	private static function date_part( $date, $format ) {
		if ( ! preg_match( '/^\d{8}$/', (string) $date ) ) {
			return '';
		}

		$timestamp = strtotime( substr( $date, 0, 4 ) . '-' . substr( $date, 4, 2 ) . '-' . substr( $date, 6, 2 ) );
		if ( ! $timestamp ) {
			return '';
		}

		$value = date_i18n( $format, $timestamp );
		if ( 'M' === $format && $value && '.' !== substr( $value, -1 ) ) {
			$value .= '.';
		}

		return $value;
	}

	/**
	 * Format a schedule date for the popover.
	 *
	 * @param string $date Date in Ymd format
	 *
	 * @return string
	 */
	private static function format_schedule_date( $date ) {
		return self::date_part( $date, 'd.m.Y' );
	}

	/**
	 * Format a Ymd date for the event modal.
	 *
	 * @param string $date Date in Ymd format
	 *
	 * @return string
	 */
	private static function format_modal_date( $date ) {
		return self::date_part( $date, 'F j, Y' );
	}

	/**
	 * Build a Google Maps route URL.
	 *
	 * @param string $location Location query
	 *
	 * @return string
	 */
	private static function route_url( $location ) {
		$query = ! empty( $location ) ? $location : 'NELI Vorarlberg';

		return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $query );
	}

	/**
	 * Build a Google Calendar URL for the event.
	 *
	 * @param array $event Event view model
	 *
	 * @return string
	 */
	private static function calendar_url( $event ) {
		$date = ! empty( $event['date_raw'] ) && preg_match( '/^\d{8}$/', $event['date_raw'] ) ? $event['date_raw'] : gmdate( 'Ymd' );
		$start = $date;
		$end = date_i18n( 'Ymd', strtotime( substr( $date, 0, 4 ) . '-' . substr( $date, 4, 2 ) . '-' . substr( $date, 6, 2 ) . ' +1 day' ) );

		if ( count( $event['schedule'] ) > 1 ) {
			$last = end( $event['schedule'] );
			if ( ! empty( $last['date'] ) && preg_match( '/^\d{8}$/', $last['date'] ) ) {
				$end = date_i18n( 'Ymd', strtotime( substr( $last['date'], 0, 4 ) . '-' . substr( $last['date'], 4, 2 ) . '-' . substr( $last['date'], 6, 2 ) . ' +1 day' ) );
			}
		} elseif ( ! empty( $event['time'] ) && preg_match( '/(\d{1,2}):(\d{2}).*?(\d{1,2}):(\d{2})/', $event['time'], $matches ) ) {
			$start = sprintf( '%sT%02d%02d00', $date, (int) $matches[1], (int) $matches[2] );
			$end = sprintf( '%sT%02d%02d00', $date, (int) $matches[3], (int) $matches[4] );
		}

		return add_query_arg(
			array(
				'action' => 'TEMPLATE',
				'text' => $event['title'],
				'dates' => $start . '/' . $end,
				'details' => wp_strip_all_tags( $event['excerpt'] ),
				'location' => $event['location'],
			),
			'https://calendar.google.com/calendar/render'
		);
	}

	/**
	 * Print shortcode styles once.
	 */
	private static function print_styles_once() {
		static $printed = false;
		if ( $printed ) {
			return;
		}
		$printed = true;
		?>
		<style>
			.wem-events-page {
				--wem-blue: #64acc3;
				--wem-blue-dark: #508a9c;
				--wem-ink: #131b23;
				--wem-muted: rgba(19, 27, 35, 0.68);
				--wem-line: rgba(19, 27, 35, 0.08);
				background: #fafafa;
				color: var(--wem-ink);
				font-family: Barlow, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
				margin: 0;
				overflow: hidden;
			}

			.wem-events-page * {
				box-sizing: border-box;
			}

			.wem-events-hero {
				padding: 72px 24px 52px;
			}

			.wem-events-hero-inner {
				margin: 0 auto;
				max-width: 860px;
				text-align: center;
			}

			.wem-events-kicker {
				color: var(--wem-blue-dark);
				font-size: 14px;
				font-weight: 700;
				letter-spacing: 0;
				margin: 0 0 14px;
				text-transform: uppercase;
			}

			.wem-events-hero h1 {
				color: var(--wem-ink);
				font-size: clamp(38px, 6vw, 72px);
				font-weight: 500;
				letter-spacing: 0;
				line-height: 1.08;
				margin: 0;
			}

			.wem-events-hero h1 span {
				color: var(--wem-blue);
			}

			.wem-events-hero p {
				color: var(--wem-muted);
				font-size: 16px;
				line-height: 1.55;
				margin: 18px auto 0;
				max-width: 560px;
			}

			.wem-events-filters {
				align-items: center;
				background: #fff;
				border: 1px solid #f0f0f0;
				border-radius: 999px;
				box-shadow: 0 24px 32px -24px rgba(19, 27, 35, 0.16);
				display: grid;
				gap: 8px;
				grid-template-columns: minmax(220px, 1fr) 150px 150px auto;
				margin: 34px auto 0;
				max-width: 860px;
				padding: 6px;
			}

			.wem-events-filters input,
			.wem-events-filters select {
				background: transparent;
				border: 0;
				border-radius: 999px;
				color: var(--wem-ink);
				font: inherit;
				min-height: 48px;
				outline: 0;
				padding: 0 18px;
				width: 100%;
			}

			.wem-events-filters select {
				border-left: 1px solid var(--wem-line);
			}

			.wem-events-filters button,
			.wem-events-more,
			.wem-event-schedule {
				cursor: default;
				display: inline-flex;
				position: relative;
			}

			.wem-event-schedule:before {
				background: #f0192f;
				content: "";
				display: inline-block;
				height: 16px;
				margin-right: 8px;
				vertical-align: -3px;
				width: 16px;
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 2v4'/%3E%3Cpath d='M16 2v4'/%3E%3Crect width='18' height='18' x='3' y='4' rx='2'/%3E%3Cpath d='M3 10h18'/%3E%3C/svg%3E") center/contain no-repeat;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 2v4'/%3E%3Cpath d='M16 2v4'/%3E%3Crect width='18' height='18' x='3' y='4' rx='2'/%3E%3Cpath d='M3 10h18'/%3E%3C/svg%3E") center/contain no-repeat;
			}

			.wem-event-schedule-trigger {
				color: #f0192f;
			}

			.wem-event-schedule-popover {
				background: #000;
				border-radius: 4px;
				bottom: calc(100% + 14px);
				box-shadow: 0 18px 42px rgba(0, 0, 0, 0.28);
				color: #fff;
				display: grid;
				font-size: 16px;
				font-weight: 600;
				gap: 12px;
				left: 50%;
				line-height: 1.2;
				max-height: 320px;
				min-width: 360px;
				opacity: 0;
				overflow: auto;
				padding: 24px;
				pointer-events: none;
				position: absolute;
				transform: translate(-50%, 8px);
				transition: opacity 160ms ease, transform 160ms ease;
				visibility: hidden;
				z-index: 20;
			}

			.wem-event-schedule-popover:before {
				border-left: 9px solid transparent;
				border-right: 9px solid transparent;
				border-top: 10px solid #000;
				content: "";
				left: 50%;
				position: absolute;
				top: 100%;
				transform: translateX(-50%);
			}

			.wem-event-schedule:hover .wem-event-schedule-popover,
			.wem-event-schedule:focus .wem-event-schedule-popover,
			.wem-event-schedule:focus-within .wem-event-schedule-popover {
				opacity: 1;
				pointer-events: auto;
				transform: translate(-50%, 0);
				visibility: visible;
			}

			.wem-event-schedule-row {
				align-items: baseline;
				display: grid;
				gap: 14px;
				grid-template-columns: 34px 112px 1fr;
				white-space: nowrap;
			}

			.wem-event-schedule span:before {
				content: none !important;
				display: none !important;
			}

			.wem-event-action {
				align-items: center;
				background: var(--wem-blue);
				border: 1px solid var(--wem-blue);
				border-radius: 999px;
				color: #fff;
				cursor: pointer;
				display: inline-flex;
				font-weight: 700;
				justify-content: center;
				min-height: 48px;
				padding: 0 24px;
				text-decoration: none;
			}

			.wem-events-section {
				margin: 0 auto;
				max-width: 1184px;
				padding: 48px 24px;
			}

			.wem-events-section-head {
				align-items: flex-end;
				display: flex;
				justify-content: space-between;
				margin-bottom: 24px;
			}

			.wem-events-section h2 {
				font-size: clamp(30px, 4vw, 48px);
				font-weight: 500;
				letter-spacing: 0;
				line-height: 1.14;
				margin: 0 0 8px;
			}

			.wem-events-section-head p {
				color: var(--wem-muted);
				margin: 0;
			}

			.wem-featured-events {
				display: grid;
				gap: 24px;
				grid-template-columns: 1.35fr 0.75fr;
			}

			.wem-featured-events .wem-event-card:first-child {
				grid-row: span 2;
			}

			.wem-events-grid {
				display: grid;
				gap: 24px;
				grid-template-columns: repeat(3, minmax(0, 1fr));
			}

			.wem-event-card {
				background: #fff;
				border: 1px solid rgba(19, 27, 35, 0.04);
				border-radius: 24px;
				box-shadow: 0 24px 44px -32px rgba(19, 27, 35, 0.28);
				display: flex;
				flex-direction: column;
				overflow: hidden;
			}

			.wem-event-card.is-extra {
				display: none;
			}

			.wem-event-card-image {
				background: linear-gradient(135deg, #64acc3, #084887);
				background-position: center;
				background-size: cover;
				display: block;
				min-height: 230px;
				position: relative;
				text-decoration: none;
			}

			.wem-event-card.featured .wem-event-card-image {
				min-height: 485px;
			}

			.wem-event-card-image:after {
				background: linear-gradient(180deg, rgba(19, 27, 35, 0) 35%, rgba(19, 27, 35, 0.72) 100%);
				content: "";
				inset: 0;
				position: absolute;
			}

			.wem-event-date {
				align-items: center;
				background: var(--wem-blue);
				border-radius: 14px;
				color: #fff;
				display: flex;
				flex-direction: column;
				height: 64px;
				justify-content: center;
				left: 16px;
				position: absolute;
				top: 16px;
				width: 64px;
				z-index: 2;
			}

			.wem-event-date strong {
				font-size: 28px;
				line-height: 1;
			}

			.wem-event-date small {
				font-size: 13px;
				font-weight: 700;
				margin-top: 3px;
			}

			.wem-event-credit {
				align-items: center;
				background: rgba(0, 0, 0, 0.68);
				border-radius: 999px;
				bottom: 12px;
				color: #fff;
				cursor: pointer;
				display: inline-flex;
				font-size: 12px;
				gap: 6px;
				left: 12px;
				max-width: calc(100% - 24px);
				min-height: 24px;
				padding: 0 8px;
				position: absolute;
				text-decoration: none;
				z-index: 4;
			}

			.wem-event-credit-text {
				display: inline-block;
				max-width: 0;
				opacity: 0;
				overflow: hidden;
				transition: max-width 180ms ease, opacity 180ms ease;
				white-space: nowrap;
			}

			.wem-event-credit:focus,
			.wem-event-credit:hover {
				color: #fff;
				outline: 0;
			}

			.wem-event-credit:focus .wem-event-credit-text,
			.wem-event-credit:hover .wem-event-credit-text,
			.wem-event-credit.is-open .wem-event-credit-text {
				max-width: 220px;
				opacity: 1;
			}

			.wem-event-card-body {
				display: flex;
				flex: 1;
				flex-direction: column;
				gap: 14px;
				padding: 20px;
			}

			.wem-event-tags {
				display: flex;
				flex-wrap: wrap;
				gap: 6px;
			}

			.wem-event-tags span {
				background: rgba(19, 27, 35, 0.04);
				border-radius: 999px;
				color: rgba(19, 27, 35, 0.56);
				font-size: 12px;
				font-weight: 700;
				padding: 7px 10px;
			}

			.wem-event-card h3 {
				font-size: 24px;
				font-weight: 500;
				letter-spacing: 0;
				line-height: 1.2;
				margin: 0;
			}

			.wem-event-card h3 a {
				color: var(--wem-ink);
				text-decoration: none;
			}

			.wem-event-card p {
				color: var(--wem-muted);
				font-size: 14px;
				line-height: 1.55;
				margin: 0;
			}

			.wem-event-meta {
				color: rgba(19, 27, 35, 0.7);
				display: flex;
				flex-wrap: wrap;
				font-size: 14px;
				font-weight: 600;
				gap: 10px 18px;
				margin-top: auto;
			}

			.wem-event-meta span:before {
				color: var(--wem-blue);
				content: "•";
				margin-right: 7px;
			}

			.wem-event-action {
				align-self: flex-start;
				background: #fff;
				color: var(--wem-blue);
				margin-top: 2px;
				min-height: 40px;
			}

			.wem-events-more {
				background: #fff;
				color: var(--wem-blue);
				display: flex;
				margin: 32px auto 0;
			}

			.wem-events-empty {
				background: #fff;
				border: 1px solid var(--wem-line);
				border-radius: 24px;
				color: var(--wem-muted);
				padding: 32px;
				text-align: center;
			}

			@media (max-width: 900px) {
				.wem-events-filters {
					border-radius: 28px;
					grid-template-columns: 1fr;
					padding: 12px;
				}

				.wem-events-filters select {
					border-left: 0;
					border-top: 1px solid var(--wem-line);
				}

				.wem-featured-events,
				.wem-events-grid {
					grid-template-columns: 1fr;
				}

				.wem-event-card.featured .wem-event-card-image {
					min-height: 320px;
				}
			}

			@media (max-width: 520px) {
				.wem-events-hero {
					padding: 44px 16px 28px;
				}

				.wem-events-section {
					padding: 34px 16px;
				}

				.wem-event-card {
					border-radius: 20px;
				}

				.wem-event-card-image,
				.wem-event-card.featured .wem-event-card-image {
					min-height: 245px;
				}
			}

			/* Elementor page match: sizes, hovers, icons */
			.wem-events-page {
				--wem-soft: #131b230a;
			}

			.wem-events-hero {
				padding: 84px 64px;
			}

			.wem-events-hero-inner {
				max-width: 760px;
			}

			.wem-events-hero h1 {
				font-size: 64px;
				line-height: 1.2;
			}

			.wem-events-hero p {
				font-weight: 500;
				line-height: 1.5;
				margin-top: 24px;
				max-width: 480px;
			}

			.wem-events-filters {
				gap: 10px;
				grid-template-columns: minmax(220px, 1fr) 150px 180px auto;
				margin-top: 48px;
				max-width: 700px;
				padding: 2px 4px;
			}

			.wem-events-filters label {
				position: relative;
			}

			.wem-events-filters label:before {
				background: var(--wem-blue);
				content: "";
				height: 18px;
				left: 16px;
				position: absolute;
				top: 50%;
				transform: translateY(-50%);
				width: 18px;
				z-index: 1;
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.3-4.3'/%3E%3C/svg%3E") center/contain no-repeat;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.3-4.3'/%3E%3C/svg%3E") center/contain no-repeat;
			}

			.wem-events-filters input,
			.wem-events-filters select {
				font-size: 14px;
				font-weight: 500;
				line-height: 1.5;
				min-height: 46px;
			}

			.wem-events-filters input {
				padding-left: 44px;
			}

			.wem-events-filters select {
				appearance: none;
				background-color: #fff;
				border: 1px solid var(--wem-blue);
				color: #55585e;
				cursor: pointer;
				padding-left: 42px;
				padding-right: 34px;
				text-align: center;
				transition: border-color 0.25s ease, background-color 0.25s ease;
			}

			.wem-events-filters select[name="wem_city"] {
				background-image:
					url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364ACC3' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z'/%3E%3Ccircle cx='12' cy='10' r='3'/%3E%3C/svg%3E"),
					url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364ACC3' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
				background-position: left 14px center, right 12px center;
				background-repeat: no-repeat, no-repeat;
				background-size: 20px, 18px;
			}

			.wem-events-filters select[name="wem_category"] {
				background-image:
					url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364ACC3' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M4 7h16'/%3E%3Cpath d='M7 12h10'/%3E%3Cpath d='M10 17h4'/%3E%3C/svg%3E"),
					url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364ACC3' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
				background-position: left 14px center, right 12px center;
				background-repeat: no-repeat, no-repeat;
				background-size: 20px, 18px;
			}

			.wem-events-filters select:hover,
			.wem-events-filters select:focus {
				background-color: #fafafa;
				border-color: var(--wem-blue-dark);
			}

			.wem-events-filters button,
			.wem-events-more,
			.wem-event-action {
				min-height: 46px;
				padding-left: 38px;
				padding-right: 38px;
				transition: background-color 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease, color 0.25s ease, transform 0.25s ease;
			}

			.wem-events-filters button:hover,
			.wem-events-filters button:focus {
				background: var(--wem-blue-dark);
				border-color: var(--wem-blue-dark);
				box-shadow: 0 4px 13.5px rgba(100, 172, 195, 0.24);
			}

			.wem-events-section {
				max-width: 1184px;
				padding: 40px 0;
			}

			.wem-events-section h2 {
				font-size: 48px;
				line-height: 1.2;
			}

			.wem-events-section-head p {
				font-size: 16px;
				font-weight: 500;
				line-height: 1.5;
			}

			.wem-featured-controls {
				align-items: center;
				display: flex;
				gap: 8px;
			}

			.wem-featured-arrow {
				align-items: center;
				background: #fff;
				border: 2px solid var(--wem-blue);
				border-radius: 999px;
				cursor: pointer;
				display: inline-flex;
				height: 56px;
				justify-content: center;
				padding: 0;
				position: relative;
				transition: background-color 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease;
				width: 56px;
			}

			.wem-featured-arrow:before {
				background: var(--wem-blue);
				content: "";
				height: 24px;
				width: 24px;
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M15 18 9 12l6-6'/%3E%3C/svg%3E") center/contain no-repeat;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M15 18 9 12l6-6'/%3E%3C/svg%3E") center/contain no-repeat;
			}

			.wem-featured-arrow.next:before {
				transform: rotate(180deg);
			}

			.wem-featured-arrow:hover,
			.wem-featured-arrow:focus {
				background: rgba(100, 172, 195, 0.08);
				box-shadow: 0 4px 13.5px rgba(100, 172, 195, 0.24);
				transform: translateY(-1px);
			}

			.wem-featured-events {
				gap: 10px;
				display: flex;
				margin-left: calc((100vw - 1184px) / -2);
				margin-right: calc((100vw - 1184px) / -2);
				overflow-x: auto;
				padding-bottom: 30px;
				scrollbar-width: none;
				scroll-snap-type: x proximity;
				transition: transform 0.5s ease;
				will-change: transform;
			}

			.wem-featured-events::-webkit-scrollbar {
				display: none;
			}

			.wem-featured-events .wem-event-card {
				flex: 0 0 calc((100% - 48px) / 3);
				scroll-snap-align: center;
			}

			.wem-featured-carousel {
				margin-left: calc((100vw - 1184px) / -2);
				margin-right: calc((100vw - 1184px) / -2);
				overflow: hidden;
			}

			.wem-featured-carousel .wem-featured-events {
				margin-left: 0;
				margin-right: 0;
				overflow: visible;
				padding-left: 100px;
				padding-right: 100px;
			}

			.wem-featured-dots {
				align-items: center;
				display: flex;
				gap: 8px;
				justify-content: center;
				margin-top: 30px;
			}

			.wem-featured-dots button {
				background: rgba(100, 172, 195, 0.34);
				border: 0;
				border-radius: 999px;
				cursor: pointer;
				height: 10px;
				padding: 0;
				transition: background-color 0.25s ease, transform 0.25s ease;
				width: 10px;
			}

			.wem-featured-dots button.is-active,
			.wem-featured-dots button:hover,
			.wem-featured-dots button:focus {
				background: var(--wem-blue);
				transform: scale(1.15);
			}

			.wem-events-grid {
				gap: 16px;
			}

			.wem-event-card {
				padding: 8px;
				transition: border-color 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease;
			}

			.wem-event-card:hover {
				border-color: rgba(100, 172, 195, 0.28);
				box-shadow: 0 28px 54px -30px rgba(19, 27, 35, 0.34);
				transform: translateY(-4px);
			}

			.wem-event-card-image {
				border-radius: 24px;
				min-height: 250px;
				overflow: hidden;
			}

			.wem-event-card.featured .wem-event-card-image {
				min-height: 485px;
			}

			.wem-event-card-image:after {
				background: linear-gradient(180deg, rgba(255, 255, 255, 0) 50%, rgba(19, 27, 35, 0.9) 100%);
				transition: opacity 0.25s ease;
			}

			.wem-event-card:hover .wem-event-card-image:after {
				opacity: 0.88;
			}

			.wem-event-date {
				border-radius: 16px;
				height: 72px;
				width: 72px;
			}

			.wem-event-date strong {
				font-size: 32px;
				font-weight: 600;
			}

			.wem-event-date small {
				font-size: 16px;
				font-weight: 600;
			}

			.wem-event-card-body {
				gap: 16px;
				padding: 40px;
			}

			.wem-event-tags span {
				font-size: 14px;
				font-weight: 600;
				letter-spacing: 0;
				line-height: 1;
				padding: 8px 12px;
			}

			.wem-event-card h3 {
				font-size: 20px;
			}

			.wem-event-card h3 a {
				transition: color 0.25s ease;
			}

			.wem-event-card h3 a:hover,
			.wem-event-card h3 a:focus {
				color: var(--wem-blue-dark);
			}

			.wem-event-card p {
				font-size: 16px;
				font-weight: 500;
				line-height: 1.5;
			}

			.wem-event-meta {
				gap: 12px 18px;
			}

			.wem-event-meta span:before {
				background: var(--wem-blue);
				content: "";
				display: inline-block;
				height: 16px;
				margin-right: 8px;
				vertical-align: -3px;
				width: 16px;
			}

			.wem-event-location:before {
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z'/%3E%3Ccircle cx='12' cy='10' r='3'/%3E%3C/svg%3E") center/contain no-repeat;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z'/%3E%3Ccircle cx='12' cy='10' r='3'/%3E%3C/svg%3E") center/contain no-repeat;
			}

			.wem-event-time:before {
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3Cpath d='M12 7v5l3 2'/%3E%3C/svg%3E") center/contain no-repeat;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3Cpath d='M12 7v5l3 2'/%3E%3C/svg%3E") center/contain no-repeat;
			}

			.wem-event-action:hover,
			.wem-event-action:focus,
			.wem-events-more:hover,
			.wem-events-more:focus {
				background: var(--wem-blue);
				color: #fff;
				transform: translateY(-1px);
			}

			@media (max-width: 1024px) {
				.wem-events-hero {
					padding: 80px 30px;
				}

				.wem-events-section {
					padding: 30px;
				}

				.wem-featured-events {
					margin-left: 0;
					margin-right: 0;
				}

				.wem-featured-carousel {
					margin-left: 0;
					margin-right: 0;
				}

				.wem-featured-carousel .wem-featured-events {
					padding-left: 0;
					padding-right: 0;
				}

				.wem-featured-events,
				.wem-events-grid {
					grid-template-columns: repeat(2, minmax(0, 1fr));
				}

				.wem-featured-events {
					display: flex;
				}

				.wem-featured-events .wem-event-card {
					flex-basis: calc((100% - 10px) / 2);
				}

				.wem-event-card.featured .wem-event-card-image {
					min-height: 250px;
				}
			}

			@media (max-width: 767px) {
				.wem-events-hero {
					padding: 40px 16px;
				}

				.wem-events-hero h1 {
					font-size: 32px;
				}

				.wem-events-filters {
					border-radius: 28px;
					grid-template-columns: 1fr;
					margin-top: 32px;
					padding: 12px;
				}

				.wem-events-filters input,
				.wem-events-filters select {
					font-size: 16px;
					min-height: 48px;
				}

				.wem-events-filters select {
					padding-left: 56px;
					padding-right: 56px;
				}

				.wem-events-filters select[name="wem_city"],
				.wem-events-filters select[name="wem_category"] {
					background-position: left 20px center, right 20px center;
					background-size: 26px, 22px;
				}

				.wem-events-section {
					padding: 40px 16px;
				}

				.wem-events-section h2 {
					font-size: 32px;
				}

				.wem-featured-controls {
					display: none;
				}

				.wem-featured-events,
				.wem-events-grid {
					grid-template-columns: 1fr;
				}

				.wem-featured-events {
					display: flex;
					gap: 16px;
					overflow-x: auto;
				}

				.wem-featured-carousel .wem-featured-events {
					padding-left: 24px;
					padding-right: 24px;
				}

				.wem-featured-events .wem-event-card {
					flex-basis: calc(100% - 48px);
				}

				.wem-event-card {
					border-radius: 24px;
				}

				.wem-event-card-image,
				.wem-event-card.featured .wem-event-card-image {
					min-height: 250px;
				}

				.wem-event-date {
					height: 64px;
					width: 64px;
				}

				.wem-event-date strong {
					font-size: 28px;
				}

				.wem-event-date small {
					font-size: 14px;
				}

				.wem-event-card-body {
					padding: 8px;
				}

				.wem-event-card p {
					display: none;
				}
			}

			/* Final Elementor parity layer */
			.wem-events-kicker {
				display: none;
			}

			.wem-events-filters-hero {
				grid-template-columns: minmax(220px, 1fr) auto;
			}

			.wem-events-filters-grid {
				background: transparent;
				border: 0;
				border-radius: 0;
				box-shadow: none;
				grid-template-columns: minmax(260px, 1fr) auto 170px 190px 190px;
				margin: 24px 0 32px;
				max-width: 100%;
				padding: 0;
			}

			.wem-events-filters-grid input,
			.wem-events-filters-grid select {
				background-color: #fff;
				border: 1px solid var(--wem-blue);
				box-shadow: none;
			}

			.wem-events-filters-grid label:first-child input {
				border-color: #edf2f3;
			}

			.wem-events-date-filter {
				position: relative;
			}

			.wem-events-date-filter:before {
				background: var(--wem-blue);
				content: "";
				height: 18px;
				left: 14px;
				position: absolute;
				top: 50%;
				transform: translateY(-50%);
				width: 18px;
				z-index: 1;
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 2v4'/%3E%3Cpath d='M16 2v4'/%3E%3Crect width='18' height='18' x='3' y='4' rx='2'/%3E%3Cpath d='M3 10h18'/%3E%3C/svg%3E") center/contain no-repeat;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 2v4'/%3E%3Cpath d='M16 2v4'/%3E%3Crect width='18' height='18' x='3' y='4' rx='2'/%3E%3Cpath d='M3 10h18'/%3E%3C/svg%3E") center/contain no-repeat;
			}

			.wem-events-date-filter input {
				color-scheme: light;
				padding-left: 42px;
			}

			.wem-featured-carousel {
				overflow: hidden;
			}

			.wem-featured-carousel .wem-featured-events {
				cursor: grab;
				overflow-x: auto;
				overscroll-behavior-x: contain;
				padding-left: 100px;
				padding-right: 100px;
				scroll-behavior: smooth;
				scroll-padding-left: 100px;
				scroll-snap-type: x mandatory;
				touch-action: pan-y;
				user-select: none;
			}

			.wem-featured-carousel .wem-featured-events.is-dragging {
				cursor: grabbing;
				scroll-behavior: auto;
				scroll-snap-type: none;
			}

			.wem-featured-events .wem-event-card {
				border: 0;
				box-shadow: none;
				flex: 0 0 calc((min(1184px, calc(100vw - 48px)) - 48px) / 3);
				min-height: 485px;
				overflow: hidden;
				position: relative;
				scroll-snap-align: center;
			}

			.wem-featured-events .wem-event-card:hover {
				box-shadow: none;
				transform: translateY(-2px);
			}

			.wem-featured-events .wem-event-card-image {
				height: auto;
				inset: 8px;
				min-height: 0;
				position: absolute;
			}

			.wem-featured-events .wem-event-card-body {
				color: #fff;
				justify-content: flex-end;
				min-height: 469px;
				padding: 40px;
				pointer-events: none;
				position: relative;
				z-index: 3;
			}

			.wem-featured-events .wem-event-card-body a,
			.wem-featured-events .wem-event-card-body button {
				pointer-events: auto;
			}

			.wem-featured-events .wem-event-card h3 a,
			.wem-featured-events .wem-event-meta {
				color: #fff;
			}

			.wem-featured-events .wem-event-tags span {
				background: rgba(255, 255, 255, 0.22);
				color: #fff;
			}

			.wem-featured-events .wem-event-card p,
			.wem-featured-events .wem-event-action,
			.wem-events-grid .wem-event-card p,
			.wem-events-grid .wem-event-action {
				display: none;
			}

			.wem-events-grid .wem-event-card-body {
				padding: 24px 24px 32px;
			}

			.wem-events-grid .wem-event-card-image {
				min-height: 250px;
			}

			.wem-events-page .wem-events-hero h1 {
				font-size: 64px;
				font-weight: 500;
				line-height: 1.2;
			}

			.wem-events-page .wem-events-hero p,
			.wem-events-page .wem-events-section-head p {
				color: rgba(19, 27, 35, 0.72);
			}

			.wem-featured-carousel .wem-featured-events {
				gap: 10px;
				scroll-snap-stop: always;
			}

			.wem-featured-carousel .wem-featured-events.is-dragging * {
				pointer-events: none;
			}

			.wem-featured-events .wem-event-card {
				flex-basis: calc((100vw - 210px) / 2);
				max-width: 590px;
			}

			.wem-events-grid .wem-event-card {
				box-shadow: none;
				min-height: 100%;
			}

			.wem-events-grid .wem-event-card:hover {
				box-shadow: none;
			}

			.wem-events-grid .wem-event-card h3 {
				line-height: 1.2;
				min-height: 48px;
			}

			.wem-events-grid .wem-event-meta {
				margin-top: 0;
			}

			@media (max-width: 1024px) {
				.wem-events-filters-grid {
					grid-template-columns: minmax(220px, 1fr) auto;
				}

				.wem-events-filters-grid select,
				.wem-events-filters-grid .wem-events-date-filter {
					min-width: 0;
				}

				.wem-featured-events .wem-event-card {
					flex-basis: calc((100% - 10px) / 2);
				}
			}

			@media (max-width: 767px) {
				.wem-events-filters-hero,
				.wem-events-filters-grid {
					grid-template-columns: 1fr;
				}

				.wem-featured-carousel {
					margin-left: -16px;
					margin-right: -16px;
				}

				.wem-featured-carousel .wem-featured-events {
					padding-left: 24px;
					padding-right: 24px;
					scroll-padding-left: 24px;
				}

				.wem-featured-events .wem-event-card {
					flex-basis: calc(100vw - 48px);
					min-height: 430px;
				}

				.wem-featured-events .wem-event-card-body {
					min-height: 414px;
					padding: 24px;
				}

				.wem-events-grid .wem-event-card-body {
					padding: 8px;
				}
			}

			.wem-events-page {
				background: #fafafa !important;
				font-family: Barlow, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
				overflow-x: clip !important;
			}

			.wem-events-page .wem-events-hero {
				padding: 84px 64px !important;
			}

			.wem-events-page .wem-events-section {
				max-width: 1184px !important;
				padding: 40px 0 !important;
			}

			.wem-events-page .wem-featured-carousel {
				margin-left: calc(50% - 50vw) !important;
				margin-right: calc(50% - 50vw) !important;
				overflow: hidden !important;
				width: 100vw !important;
			}

			.wem-events-page .wem-featured-carousel .wem-featured-events {
				overflow-x: auto !important;
				transform: none !important;
			}

			.wem-events-page .wem-featured-events .wem-event-card {
				border-radius: 32px !important;
				height: 485px !important;
				min-height: 485px !important;
			}

			.wem-events-page .wem-featured-events .wem-event-card-image {
				border-radius: 24px !important;
				inset: 8px !important;
			}

			.wem-events-page .wem-events-grid {
				gap: 16px !important;
				grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card {
				min-height: 460px !important;
			}

			.wem-events-contact {
				align-items: center;
				display: grid;
				gap: 120px;
				grid-template-columns: minmax(0, 620px) minmax(280px, 1fr);
				margin: 96px auto 112px;
				max-width: 1184px;
				padding: 0;
			}

			.wem-events-contact-image {
				border-radius: 24px;
				overflow: hidden;
			}

			.wem-events-contact-image img {
				display: block;
				height: auto;
				width: 100%;
			}

			.wem-events-contact-content {
				text-align: center;
			}

			.wem-events-contact-content h2 {
				color: var(--wem-ink);
				font-size: 40px;
				font-weight: 500;
				line-height: 1.2;
				margin: 0 0 56px;
			}

			.wem-events-contact-content h2 span {
				color: var(--wem-blue);
			}

			.wem-events-contact-content h3 {
				color: var(--wem-ink);
				font-size: 24px;
				font-weight: 600;
				line-height: 1.2;
				margin: 0 0 24px;
			}

			.wem-events-contact-content p {
				color: var(--wem-ink);
				font-size: 18px;
				font-weight: 500;
				line-height: 1.5;
				margin: 0 0 32px;
			}

			.wem-events-contact-content a {
				color: inherit;
				text-decoration: none;
			}

			.wem-events-social {
				align-items: center;
				display: flex;
				gap: 24px;
				justify-content: center;
			}

			.wem-events-social a {
				background: var(--wem-blue);
				display: inline-block;
				height: 32px;
				transition: background-color 0.25s ease, transform 0.25s ease;
				width: 32px;
			}

			.wem-events-social a:hover,
			.wem-events-social a:focus {
				background: var(--wem-blue-dark);
				transform: translateY(-2px);
			}

			.wem-social-facebook {
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath d='M504 256C504 119 393 8 256 8S8 119 8 256c0 123.8 90.7 226.4 209.3 245V327.7h-63V256h63v-54.6c0-62.2 37-96.5 93.7-96.5 27.1 0 55.5 4.8 55.5 4.8v61h-31.3c-30.8 0-40.4 19.1-40.4 38.7V256h68.8l-11 71.7h-57.8V501C413.3 482.4 504 379.8 504 256z'/%3E%3C/svg%3E") center/contain no-repeat;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath d='M504 256C504 119 393 8 256 8S8 119 8 256c0 123.8 90.7 226.4 209.3 245V327.7h-63V256h63v-54.6c0-62.2 37-96.5 93.7-96.5 27.1 0 55.5 4.8 55.5 4.8v61h-31.3c-30.8 0-40.4 19.1-40.4 38.7V256h68.8l-11 71.7h-57.8V501C413.3 482.4 504 379.8 504 256z'/%3E%3C/svg%3E") center/contain no-repeat;
			}

			.wem-social-instagram {
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath d='M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8z'/%3E%3C/svg%3E") center/contain no-repeat;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath d='M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8z'/%3E%3C/svg%3E") center/contain no-repeat;
			}

			@media (max-width: 1024px) {
				.wem-events-page .wem-events-section {
					padding-left: 30px !important;
					padding-right: 30px !important;
				}

				.wem-events-contact {
					gap: 56px;
					grid-template-columns: 1fr 1fr;
					padding: 0 30px;
				}
			}

			@media (max-width: 767px) {
				.wem-events-page .wem-events-hero {
					padding: 40px 16px !important;
				}

				.wem-events-page .wem-events-section {
					padding: 40px 16px !important;
				}

				.wem-events-page .wem-events-grid {
					grid-template-columns: 1fr !important;
				}

				.wem-events-page .wem-featured-events .wem-event-card {
					height: 430px !important;
					min-height: 430px !important;
				}

				.wem-events-contact {
					gap: 40px;
					grid-template-columns: 1fr;
					margin: 72px auto 72px;
					padding: 0 16px;
				}

				.wem-events-contact-content {
					order: 1;
				}

				.wem-events-contact-image {
					order: 2;
				}

				.wem-events-contact-content h2 {
					font-size: 28px;
					margin-bottom: 32px;
				}

				.wem-events-contact-content h3 {
					font-size: 20px;
				}

				.wem-events-contact-content p {
					font-size: 16px;
				}
			}

			.wem-events-page.is-loading {
				cursor: progress;
				opacity: 0.72;
				transition: opacity 0.2s ease;
			}

			.wem-events-page .wem-featured-arrow {
				background: #fff !important;
				border: 2px solid var(--wem-blue) !important;
				color: var(--wem-blue) !important;
				font-size: 32px !important;
				font-weight: 300 !important;
				line-height: 1 !important;
				overflow: hidden !important;
			}

			.wem-events-page .wem-featured-arrow:before {
				display: none !important;
			}

			.wem-events-page .wem-featured-arrow span {
				display: block !important;
				line-height: 1 !important;
				transform: translateY(-2px);
			}

			.wem-events-page .wem-featured-arrow:hover,
			.wem-events-page .wem-featured-arrow:focus {
				background: var(--wem-blue) !important;
				color: #fff !important;
			}

			.wem-events-page .wem-event-card {
				border-radius: 32px !important;
				box-shadow: 0 16px 32px -24px rgba(19, 27, 35, 0.05) !important;
				padding: 8px 8px 16px !important;
			}

			.wem-events-page .wem-event-card:hover {
				border-color: rgba(100, 172, 195, 0.46) !important;
				box-shadow: 0 24px 52px -30px rgba(19, 27, 35, 0.2) !important;
			}

			.wem-events-page .wem-event-date {
				border-radius: 16px !important;
				height: 72px !important;
				left: 16px !important;
				top: 16px !important;
				width: 72px !important;
			}

			.wem-events-page .wem-event-date strong {
				font-size: 32px !important;
				font-weight: 600 !important;
			}

			.wem-events-page .wem-event-date small {
				font-size: 16px !important;
				font-weight: 600 !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card-image {
				min-height: 275px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card-body {
				gap: 16px !important;
				padding: 16px 16px 8px !important;
			}

			.wem-events-page .wem-event-tags {
				gap: 8px !important;
			}

			.wem-events-page .wem-event-tags span {
				background: rgba(19, 27, 35, 0.04) !important;
				color: rgba(19, 27, 35, 0.62) !important;
				font-size: 16px !important;
				font-weight: 600 !important;
				line-height: 1 !important;
				padding: 8px 16px !important;
			}

			.wem-events-page .wem-event-card h3 {
				font-size: 24px !important;
				font-weight: 500 !important;
				line-height: 1.5 !important;
			}

			.wem-events-page .wem-event-meta {
				font-size: 16px !important;
				font-weight: 500 !important;
				gap: 16px 24px !important;
			}

			.wem-events-page .wem-featured-events .wem-event-tags span {
				background: rgba(255, 255, 255, 0.24) !important;
				color: #fff !important;
			}

			.wem-events-page .wem-featured-events .wem-event-card h3 {
				font-size: 40px !important;
				line-height: 1.2 !important;
			}

			.wem-events-page .wem-featured-events .wem-event-card,
			.wem-events-page .wem-featured-events .wem-event-card * {
				-webkit-user-drag: none;
				user-drag: none;
			}

			.wem-events-page .wem-featured-events.is-dragging .wem-event-card,
			.wem-events-page .wem-featured-events.is-dragging .wem-event-card * {
				pointer-events: none;
			}

			@media (max-width: 1024px) {
				.wem-events-page .wem-events-grid {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}

				.wem-events-page .wem-featured-events .wem-event-card h3 {
					font-size: 32px !important;
				}
			}

			@media (max-width: 767px) {
				.wem-events-page .wem-featured-controls {
					display: flex !important;
					justify-content: flex-start !important;
					margin-top: 20px !important;
				}

				.wem-events-page .wem-featured-arrow {
					height: 48px !important;
					width: 48px !important;
				}

				.wem-events-page .wem-featured-events .wem-event-card h3,
				.wem-events-page .wem-event-card h3 {
					font-size: 24px !important;
					line-height: 1.35 !important;
				}

				.wem-events-page .wem-events-grid .wem-event-card-image {
					min-height: 245px !important;
				}
			}

			/* Figma correction layer: keep sections in flow and match event card specs. */
			.wem-events-page {
				position: relative !important;
			}

			.wem-events-page .wem-events-hero,
			.wem-events-page .wem-events-section {
				clear: both !important;
				position: relative !important;
				transform: none !important;
			}

			.wem-events-page .wem-events-hero {
				min-height: 0 !important;
				padding: 84px 64px 84px !important;
				z-index: 1 !important;
			}

			.wem-events-page .wem-events-section {
				padding-bottom: 40px !important;
				padding-top: 40px !important;
				z-index: 2 !important;
			}

			.wem-events-page .wem-featured-carousel {
				margin-top: 0 !important;
				position: relative !important;
				top: auto !important;
				transform: none !important;
			}

			.wem-events-page .wem-featured-carousel .wem-featured-events {
				align-items: stretch !important;
				padding-bottom: 16px !important;
				padding-top: 0 !important;
			}

			.wem-events-page .wem-featured-events .wem-event-card {
				background: #fff !important;
				border: 0 !important;
				border-radius: 32px !important;
				box-shadow: none !important;
				height: 645px !important;
				min-height: 645px !important;
				overflow: hidden !important;
				padding: 8px !important;
			}

			.wem-events-page .wem-featured-events .wem-event-card-image {
				border-radius: 24px !important;
				inset: 8px !important;
				min-height: 0 !important;
				position: absolute !important;
			}

			.wem-events-page .wem-featured-events .wem-event-card-image:after {
				background: linear-gradient(180deg, rgba(19, 27, 35, 0) 44%, rgba(19, 27, 35, 0.88) 100%) !important;
			}

			.wem-events-page .wem-featured-events .wem-event-date {
				border-radius: 8px !important;
				height: 96px !important;
				left: 30px !important;
				top: 30px !important;
				width: 96px !important;
			}

			.wem-events-page .wem-featured-events .wem-event-date strong {
				font-size: 40px !important;
				line-height: 1 !important;
			}

			.wem-events-page .wem-featured-events .wem-event-date small {
				font-size: 20px !important;
				line-height: 1 !important;
			}

			.wem-events-page .wem-featured-events .wem-event-card-body {
				display: flex !important;
				flex-direction: column !important;
				gap: 24px !important;
				justify-content: flex-end !important;
				min-height: 629px !important;
				padding: 48px 56px 64px !important;
				position: relative !important;
				z-index: 3 !important;
			}

			.wem-events-page .wem-featured-events .wem-event-tags {
				left: 150px !important;
				position: absolute !important;
				top: 68px !important;
			}

			.wem-events-page .wem-featured-events .wem-event-tags span {
				backdrop-filter: blur(8.3px) !important;
				background: rgba(255, 255, 255, 0.16) !important;
				border-radius: 100px !important;
				color: #fff !important;
				font-size: 20px !important;
				font-weight: 600 !important;
				line-height: 1 !important;
				padding: 8px 24px !important;
			}

			.wem-events-page .wem-featured-events .wem-event-card h3 {
				max-width: 720px !important;
				margin-top: auto !important;
			}

			.wem-events-page .wem-featured-events .wem-event-card h3 a {
				color: #fff !important;
				font-size: 48px !important;
				font-weight: 500 !important;
				line-height: 1.12 !important;
			}

			.wem-events-page .wem-featured-events .wem-event-meta {
				color: #fff !important;
				font-size: 20px !important;
				font-weight: 600 !important;
				gap: 28px !important;
			}

			.wem-events-page .wem-events-grid {
				gap: 16px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card {
				border: 1px solid rgba(100, 172, 195, 0.25) !important;
				border-radius: 32px !important;
				box-shadow: 0 16px 32px -24px rgba(19, 27, 35, 0.04) !important;
				min-height: 507px !important;
				padding: 8px 8px 16px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card:hover {
				box-shadow: 0 16px 32px -24px rgba(19, 27, 35, 0.08) !important;
				transform: none !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card-image {
				border-radius: 24px !important;
				min-height: 275px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-date {
				border-radius: 8px !important;
				height: 64px !important;
				left: 16px !important;
				top: 16px !important;
				width: 64px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-date strong {
				font-size: 24px !important;
				line-height: 1.12 !important;
			}

			.wem-events-page .wem-events-grid .wem-event-date small {
				font-size: 14px !important;
				line-height: 1.12 !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card-body {
				gap: 16px !important;
				padding: 0 16px 8px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-tags span {
				backdrop-filter: blur(8.3px) !important;
				background: rgba(19, 27, 35, 0.04) !important;
				border-radius: 100px !important;
				color: rgba(19, 27, 35, 0.5) !important;
				font-size: 12px !important;
				font-weight: 600 !important;
				line-height: 14px !important;
				padding: 8px 12px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card h3 {
				font-size: 24px !important;
				font-weight: 500 !important;
				line-height: 1.2 !important;
				min-height: 0 !important;
			}

			.wem-events-page .wem-events-grid .wem-event-meta {
				color: rgba(19, 27, 35, 0.72) !important;
				flex-direction: column !important;
				font-size: 14px !important;
				font-weight: 600 !important;
				gap: 16px !important;
			}

			@media (max-width: 1024px) {
				.wem-events-page .wem-featured-events .wem-event-card {
					height: 540px !important;
					min-height: 540px !important;
				}

				.wem-events-page .wem-featured-events .wem-event-card-body {
					min-height: 524px !important;
					padding: 40px !important;
				}

				.wem-events-page .wem-featured-events .wem-event-card h3 a {
					font-size: 36px !important;
				}
			}

			@media (max-width: 767px) {
				.wem-events-page .wem-events-hero {
					padding: 40px 16px !important;
				}

				.wem-events-page .wem-featured-events .wem-event-card {
					height: 430px !important;
					min-height: 430px !important;
				}

				.wem-events-page .wem-featured-events .wem-event-date {
					height: 64px !important;
					width: 64px !important;
				}

				.wem-events-page .wem-featured-events .wem-event-date strong {
					font-size: 24px !important;
				}

				.wem-events-page .wem-featured-events .wem-event-date small {
					font-size: 14px !important;
				}

				.wem-events-page .wem-featured-events .wem-event-tags {
					left: 96px !important;
					top: 34px !important;
				}

				.wem-events-page .wem-featured-events .wem-event-tags span {
					font-size: 12px !important;
					padding: 8px 12px !important;
				}

				.wem-events-page .wem-featured-events .wem-event-card-body {
					display: flex !important;
					flex-direction: column !important;
					min-height: 414px !important;
					padding: 24px !important;
				}

				.wem-events-page .wem-featured-events .wem-event-card h3 a {
					font-size: 28px !important;
				}

				.wem-events-page .wem-event-schedule-popover {
					font-size: 14px !important;
					left: 0 !important;
					min-width: min(300px, calc(100vw - 48px)) !important;
					padding: 18px !important;
					transform: translate(0, 8px) !important;
				}

				.wem-events-page .wem-event-schedule:hover .wem-event-schedule-popover,
				.wem-events-page .wem-event-schedule:focus .wem-event-schedule-popover,
				.wem-events-page .wem-event-schedule:focus-within .wem-event-schedule-popover {
					transform: translate(0, 0) !important;
				}
			}

			.wem-events-page .wem-event-card,
			.wem-events-page .wem-featured-events .wem-event-card,
			.wem-events-page .wem-events-grid .wem-event-card {
				overflow: visible !important;
			}

			.wem-events-page .wem-event-schedule:before {
				background: #f0192f !important;
				content: "" !important;
				display: inline-block !important;
				height: 16px !important;
				margin-right: 8px !important;
				width: 16px !important;
			}

			.wem-events-page .wem-event-schedule span:before {
				content: none !important;
				display: none !important;
			}

			/* Featured carousel redesign from the supplied Figma frame. */
			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) {
				align-items: center !important;
				background: #fafafa !important;
				display: flex !important;
				flex-direction: column !important;
				gap: 32px !important;
				margin: 0 !important;
				max-width: none !important;
				padding: 40px 0 !important;
				width: 100% !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-events-section-head {
				align-items: flex-end !important;
				display: flex !important;
				justify-content: space-between !important;
				margin: 0 auto !important;
				max-width: 1184px !important;
				padding: 0 !important;
				width: min(1184px, calc(100% - 48px)) !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-events-section-head > div:first-child {
				max-width: 571px !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) h2 {
				color: #131b23 !important;
				font-size: 48px !important;
				font-weight: 500 !important;
				letter-spacing: 0 !important;
				line-height: 1.2 !important;
				margin: 0 0 8px !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-events-section-head p {
				color: rgba(19, 27, 35, 0.72) !important;
				font-size: 16px !important;
				font-weight: 500 !important;
				line-height: 1.5 !important;
				margin: 0 !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-controls {
				align-items: center !important;
				display: flex !important;
				gap: 8px !important;
				margin: 0 !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-arrow {
				background: #fff !important;
				border: 2px solid #64acc3 !important;
				border-radius: 100px !important;
				box-shadow: none !important;
				height: 56px !important;
				width: 56px !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-arrow:hover,
			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-arrow:focus {
				background: rgba(100, 172, 195, 0.08) !important;
				box-shadow: 0 4px 13.5px rgba(100, 172, 195, 0.24) !important;
				transform: none !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-carousel {
				margin: 0 !important;
				overflow: hidden !important;
				width: 100vw !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-carousel .wem-featured-events {
				align-items: center !important;
				display: flex !important;
				gap: 24px !important;
				overflow-x: auto !important;
				padding: 0 calc((100vw - min(1184px, calc(100vw - 48px))) / 2) !important;
				scroll-padding-left: calc((100vw - min(1184px, calc(100vw - 48px))) / 2) !important;
				scroll-snap-type: x mandatory !important;
				width: 100% !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card {
				background: transparent !important;
				border: 0 !important;
				border-radius: 32px !important;
				box-shadow: none !important;
				flex: 0 0 776px !important;
				height: 485px !important;
				max-width: none !important;
				min-height: 485px !important;
				overflow: hidden !important;
				padding: 0 !important;
				position: relative !important;
				scroll-snap-align: center !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card:hover {
				box-shadow: none !important;
				transform: none !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card-image {
				border-radius: 32px !important;
				inset: 0 !important;
				min-height: 0 !important;
				position: absolute !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card-image:after {
				background: linear-gradient(180deg, rgba(19, 27, 35, 0) 38%, rgba(19, 27, 35, 0.7) 100%) !important;
				inset: auto 0 0 !important;
				height: 246px !important;
				opacity: 1 !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-date {
				border-radius: 16px !important;
				height: 72px !important;
				left: 24px !important;
				top: 24px !important;
				width: 72px !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-date strong {
				font-size: 32px !important;
				font-weight: 600 !important;
				line-height: 1.12 !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-date small {
				font-size: 16px !important;
				font-weight: 600 !important;
				line-height: 1.12 !important;
				margin: 0 !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-credit {
				bottom: auto !important;
				left: auto !important;
				right: 24px !important;
				top: 24px !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card-body {
				color: #fff !important;
				display: flex !important;
				flex-direction: column !important;
				gap: 16px !important;
				justify-content: flex-end !important;
				min-height: 485px !important;
				padding: 24px !important;
				pointer-events: none !important;
				position: relative !important;
				z-index: 3 !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card-body a,
			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card-body button,
			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card-body .wem-event-schedule {
				pointer-events: auto !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-tags {
				display: flex !important;
				flex-wrap: wrap !important;
				gap: 4px !important;
				left: auto !important;
				position: static !important;
				top: auto !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-tags span {
				backdrop-filter: blur(8.3px) !important;
				background: rgba(255, 255, 255, 0.32) !important;
				border-radius: 100px !important;
				color: #fff !important;
				font-size: 14px !important;
				font-weight: 600 !important;
				line-height: 17px !important;
				max-width: 100% !important;
				overflow: hidden !important;
				padding: 8px 12px !important;
				text-overflow: ellipsis !important;
				white-space: nowrap !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card h3 {
				margin: 0 !important;
				max-width: 100% !important;
				min-height: 0 !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card h3 a {
				color: #fff !important;
				-webkit-box-orient: vertical !important;
				-webkit-line-clamp: 3 !important;
				display: -webkit-box !important;
				font-size: 28px !important;
				font-weight: 500 !important;
				line-height: 1.2 !important;
				overflow: hidden !important;
				text-decoration: none !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card p,
			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-action {
				display: none !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-meta {
				color: #fff !important;
				display: flex !important;
				flex-direction: row !important;
				flex-wrap: wrap !important;
				font-size: 16px !important;
				font-weight: 500 !important;
				gap: 24px !important;
				line-height: 1.5 !important;
				margin: 0 !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-meta span:before {
				background: #fff !important;
				height: 24px !important;
				margin-right: 8px !important;
				vertical-align: -6px !important;
				width: 24px !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-dots {
				gap: 16px !important;
				margin-top: 32px !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-dots button {
				background: rgba(100, 172, 195, 0.35) !important;
				border-radius: 50% !important;
				height: 12px !important;
				transform: none !important;
				width: 12px !important;
			}

			.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-dots button.is-active {
				background: #64acc3 !important;
				height: 16px !important;
				width: 16px !important;
			}

			@media (max-width: 900px) {
				.wem-events-page > .wem-events-section:not(.wem-events-section-grid) {
					align-items: flex-start !important;
					padding: 40px 16px !important;
				}

				.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-events-section-head {
					width: 100% !important;
				}

				.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-controls {
					display: none !important;
				}

				.wem-events-page > .wem-events-section:not(.wem-events-section-grid) h2 {
					font-size: 32px !important;
				}

				.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-carousel {
					margin-left: 0 !important;
					width: 100% !important;
				}

				.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-carousel .wem-featured-events {
					gap: 24px !important;
					padding: 0 !important;
					scroll-padding-left: 0 !important;
				}

				.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card {
					flex-basis: min(311px, calc(100vw - 32px)) !important;
					height: 454px !important;
					min-height: 454px !important;
				}

				.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card-body {
					gap: 24px !important;
					min-height: 454px !important;
					padding: 24px !important;
				}

				.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card h3 {
					max-width: 263px !important;
				}

				.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-card h3 a {
					font-size: 24px !important;
					line-height: 1.2 !important;
				}

				.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-events .wem-event-meta {
					gap: 12px 24px !important;
				}

				.wem-events-page > .wem-events-section:not(.wem-events-section-grid) .wem-featured-dots {
					align-self: stretch !important;
					width: 100% !important;
				}
			}

			/* Weitere Events grid redesign from the supplied Figma frame. */
			.wem-events-page .wem-events-section-grid {
				align-items: center !important;
				background: #fafafa !important;
				display: flex !important;
				flex-direction: column !important;
				gap: 32px !important;
				margin: 0 !important;
				max-width: none !important;
				padding: 80px 128px !important;
				width: 100% !important;
			}

			.wem-events-page .wem-events-section-grid .wem-events-section-head {
				align-items: flex-start !important;
				display: flex !important;
				flex-direction: column !important;
				gap: 16px !important;
				margin: 0 !important;
				max-width: 1184px !important;
				width: 100% !important;
			}

			.wem-events-page .wem-events-section-grid .wem-events-section-head > div {
				display: flex !important;
				flex-direction: column !important;
				gap: 16px !important;
			}

			.wem-events-page .wem-events-section-grid h2 {
				color: #131b23 !important;
				font-size: 48px !important;
				font-weight: 500 !important;
				letter-spacing: 0 !important;
				line-height: 1.2 !important;
				margin: 0 !important;
			}

			.wem-events-page .wem-events-section-grid .wem-events-section-head p {
				color: rgba(19, 27, 35, 0.72) !important;
				font-size: 16px !important;
				font-weight: 500 !important;
				line-height: 1.5 !important;
				margin: 0 !important;
				max-width: 470px !important;
			}

			.wem-events-page .wem-events-filters-grid {
				align-items: center !important;
				background: transparent !important;
				border: 0 !important;
				border-radius: 0 !important;
				box-shadow: none !important;
				display: grid !important;
				gap: 10px !important;
				grid-template-columns: minmax(320px, 469px) 112px repeat(3, minmax(140px, 155px)) !important;
				justify-content: space-between !important;
				margin: 0 !important;
				max-width: 1184px !important;
				padding: 0 !important;
				width: 100% !important;
			}

			.wem-events-page .wem-events-filters-grid label,
			.wem-events-page .wem-events-filters-grid select,
			.wem-events-page .wem-events-filters-grid button {
				height: 48px !important;
				min-height: 48px !important;
			}

			.wem-events-page .wem-events-filters-grid label {
				margin: 0 !important;
				position: relative !important;
			}

			.wem-events-page .wem-events-filters-grid label:first-child {
				background: #fff !important;
				border: 0.5px solid #c8c8c8 !important;
				border-radius: 100px !important;
				box-shadow: 0 24px 32px -24px rgba(19, 27, 35, 0.1) !important;
				padding: 0 !important;
			}

			.wem-events-page .wem-events-filters-grid label:first-child:before {
				background: rgba(19, 27, 35, 0.32) !important;
				content: "" !important;
				height: 24px !important;
				left: 20px !important;
				position: absolute !important;
				top: 50% !important;
				transform: translateY(-50%) !important;
				width: 24px !important;
				z-index: 2 !important;
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9.5 3a6.5 6.5 0 0 1 5.17 10.45l4.44 4.44-1.42 1.42-4.44-4.44A6.5 6.5 0 1 1 9.5 3Zm0 2a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M9.5 3a6.5 6.5 0 0 1 5.17 10.45l4.44 4.44-1.42 1.42-4.44-4.44A6.5 6.5 0 1 1 9.5 3Zm0 2a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
			}

			.wem-events-page .wem-events-filters-grid input,
			.wem-events-page .wem-events-filters-grid select {
				box-shadow: 0 24px 32px -24px rgba(19, 27, 35, 0.1) !important;
				color: rgba(19, 27, 35, 0.72) !important;
				font-size: 14px !important;
				font-weight: 500 !important;
				line-height: 1.5 !important;
				margin: 0 !important;
				min-height: 48px !important;
			}

			.wem-events-page .wem-events-filters-grid input[type="search"] {
				background: transparent !important;
				border: 0 !important;
				box-shadow: none !important;
				color: #131b23 !important;
				font-size: 16px !important;
				font-weight: 400 !important;
				padding: 0 20px 0 52px !important;
			}

			.wem-events-page .wem-events-filters-grid input[type="search"]::placeholder {
				color: rgba(19, 27, 35, 0.32) !important;
				opacity: 1 !important;
			}

			.wem-events-page .wem-events-filters-grid select,
			.wem-events-page .wem-events-filters-grid .wem-events-date-filter input {
				appearance: none !important;
				background-color: #fff !important;
				border: 1px solid #64acc3 !important;
				border-radius: 100px !important;
				cursor: pointer !important;
				padding: 0 36px 0 42px !important;
				text-align: center !important;
				width: 100% !important;
			}

			.wem-events-page .wem-events-filters-grid select {
				background-image:
					var(--wem-filter-icon),
					url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364ACC3' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m7 10 5 5 5-5'/%3E%3C/svg%3E") !important;
				background-position: left 12px center, right 10px center !important;
				background-repeat: no-repeat, no-repeat !important;
				background-size: 24px, 24px !important;
			}

			.wem-events-page .wem-events-filters-grid select[name="wem_city"] {
				--wem-filter-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364ACC3' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 10c0 5.5-8 12-8 12S4 15.5 4 10a8 8 0 1 1 16 0Z'/%3E%3Ccircle cx='12' cy='10' r='3'/%3E%3C/svg%3E");
			}

			.wem-events-page .wem-events-filters-grid select[name="wem_category"] {
				--wem-filter-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%2364ACC3' d='M4 5.5A1.5 1.5 0 0 1 5.5 4h4.2l2 2H18.5A1.5 1.5 0 0 1 20 7.5v11A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5v-13Z'/%3E%3C/svg%3E");
			}

			.wem-events-page .wem-events-filters-grid .wem-events-date-filter:before {
				background: #64acc3 !important;
				content: "" !important;
				height: 24px !important;
				left: 12px !important;
				position: absolute !important;
				top: 50% !important;
				transform: translateY(-50%) !important;
				width: 24px !important;
				z-index: 2 !important;
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M7 3v3M17 3v3M4 9h20M6 5h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M7 3v3M17 3v3M4 9h20M6 5h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
			}

			.wem-events-page .wem-events-filters-grid .wem-events-date-filter:after {
				background: #64acc3 !important;
				content: "" !important;
				height: 24px !important;
				pointer-events: none !important;
				position: absolute !important;
				right: 10px !important;
				top: 50% !important;
				transform: translateY(-50%) !important;
				width: 24px !important;
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m7 10 5 5 5-5'/%3E%3C/svg%3E") center/contain no-repeat !important;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m7 10 5 5 5-5'/%3E%3C/svg%3E") center/contain no-repeat !important;
			}

			.wem-events-page .wem-events-filters-grid button[type="submit"] {
				background: #64acc3 !important;
				border: 1px solid #64acc3 !important;
				border-radius: 100px !important;
				box-shadow: 0 24px 32px -24px rgba(19, 27, 35, 0.1) !important;
				color: #fff !important;
				cursor: pointer !important;
				display: inline-flex !important;
				font-size: 14px !important;
				font-weight: 700 !important;
				height: 48px !important;
				justify-content: center !important;
				line-height: 48px !important;
				min-height: 48px !important;
				padding: 0 24px !important;
				place-items: center !important;
				width: 112px !important;
			}

			.wem-events-page .wem-events-grid {
				display: grid !important;
				gap: 16px !important;
				grid-template-columns: repeat(3, minmax(0, 384px)) !important;
				justify-content: center !important;
				max-width: 1184px !important;
				width: 100% !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card {
				background: #fff !important;
				border: 0 !important;
				border-radius: 32px !important;
				box-shadow: 0 16px 32px -24px rgba(19, 27, 35, 0.04) !important;
				display: flex !important;
				flex-direction: column !important;
				gap: 16px !important;
				height: 507px !important;
				min-height: 507px !important;
				overflow: visible !important;
				padding: 8px 8px 16px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card-image {
				border-radius: 24px !important;
				display: block !important;
				flex: 0 0 275px !important;
				min-height: 275px !important;
				overflow: hidden !important;
				position: relative !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card-image:after {
				content: none !important;
			}

			.wem-events-page .wem-events-grid .wem-event-date {
				border-radius: 8px !important;
				height: 64px !important;
				left: 16px !important;
				top: 16px !important;
				width: 64px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-date strong {
				font-size: 24px !important;
				font-weight: 600 !important;
				line-height: 1.12 !important;
			}

			.wem-events-page .wem-events-grid .wem-event-date small {
				font-size: 14px !important;
				font-weight: 600 !important;
				line-height: 1.12 !important;
				margin: 0 !important;
			}

			.wem-events-page .wem-events-grid .wem-event-credit {
				bottom: 16px !important;
				left: 16px !important;
				max-width: calc(100% - 32px) !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card-body {
				display: flex !important;
				flex: 1 1 auto !important;
				flex-direction: column !important;
				gap: 16px !important;
				min-height: 0 !important;
				overflow: visible !important;
				padding: 0 16px 8px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-tags {
				display: flex !important;
				flex-wrap: wrap !important;
				gap: 4px !important;
				max-height: 30px !important;
				overflow: hidden !important;
			}

			.wem-events-page .wem-events-grid .wem-event-tags span {
				background: rgba(19, 27, 35, 0.04) !important;
				border-radius: 100px !important;
				color: rgba(19, 27, 35, 0.5) !important;
				font-size: 12px !important;
				font-weight: 600 !important;
				line-height: 14px !important;
				max-width: 148px !important;
				overflow: hidden !important;
				padding: 8px 12px !important;
				text-overflow: ellipsis !important;
				white-space: nowrap !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card h3 {
				color: #131b23 !important;
				font-size: 24px !important;
				font-weight: 500 !important;
				line-height: 1.2 !important;
				margin: 0 !important;
				min-height: 0 !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card h3 a {
				-webkit-box-orient: vertical !important;
				-webkit-line-clamp: 2 !important;
				color: #131b23 !important;
				display: -webkit-box !important;
				overflow: hidden !important;
				text-decoration: none !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card p,
			.wem-events-page .wem-events-grid .wem-event-action {
				display: none !important;
			}

			.wem-events-page .wem-events-grid .wem-event-meta {
				color: rgba(19, 27, 35, 0.72) !important;
				display: flex !important;
				flex-direction: column !important;
				flex-wrap: nowrap !important;
				font-size: 14px !important;
				font-weight: 600 !important;
				gap: 16px !important;
				line-height: 1.5 !important;
				margin: 0 !important;
			}

			.wem-events-page .wem-events-grid .wem-event-meta > span {
				align-items: center !important;
				display: inline-flex !important;
				min-height: 24px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-meta span:before {
				background: #64acc3 !important;
				height: 24px !important;
				margin-right: 8px !important;
				vertical-align: 0 !important;
				width: 24px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-schedule {
				color: rgba(19, 27, 35, 0.72) !important;
				font-size: 14px !important;
				font-weight: 600 !important;
			}

			.wem-events-page .wem-events-more {
				align-items: center !important;
				align-self: center !important;
				background: #fff !important;
				border: 1px solid #64acc3 !important;
				border-radius: 100px !important;
				box-shadow: 0 24px 32px -24px rgba(19, 27, 35, 0.1) !important;
				color: #64acc3 !important;
				display: inline-flex !important;
				font-size: 14px !important;
				font-weight: 500 !important;
				height: 48px !important;
				justify-content: center !important;
				line-height: 1.5 !important;
				margin: 0 !important;
				min-height: 48px !important;
				padding: 0 12px !important;
				width: auto !important;
			}

			@media (max-width: 1240px) {
				.wem-events-page .wem-events-section-grid {
					padding-left: 30px !important;
					padding-right: 30px !important;
				}

				.wem-events-page .wem-events-grid {
					grid-template-columns: repeat(2, minmax(0, 384px)) !important;
				}

				.wem-events-page .wem-events-filters-grid {
					grid-template-columns: minmax(280px, 1fr) 112px repeat(3, minmax(140px, 1fr)) !important;
				}
			}

			@media (max-width: 767px) {
				.wem-events-page .wem-events-section-grid {
					align-items: flex-start !important;
					gap: 32px !important;
					padding: 80px 16px !important;
				}

				.wem-events-page .wem-events-section-grid .wem-events-section-head {
					width: 100% !important;
				}

				.wem-events-page .wem-events-section-grid h2 {
					font-size: 32px !important;
					line-height: 1.2 !important;
				}

				.wem-events-page .wem-events-section-grid .wem-events-section-head p {
					max-width: 343px !important;
				}

				.wem-events-page .wem-events-filters-grid {
					display: flex !important;
					flex-direction: column !important;
					gap: 10px !important;
					width: 100% !important;
				}

				.wem-events-page .wem-events-filters-grid label:first-child {
					height: 56px !important;
					order: 0 !important;
					width: 100% !important;
				}

				.wem-events-page .wem-events-filters-grid input[type="search"] {
					min-height: 56px !important;
				}

				.wem-events-page .wem-events-filters-grid select,
				.wem-events-page .wem-events-filters-grid .wem-events-date-filter,
				.wem-events-page .wem-events-filters-grid .wem-events-date-filter input,
				.wem-events-page .wem-events-filters-grid button[type="submit"] {
					width: 100% !important;
				}

				.wem-events-page .wem-events-filters-grid select[name="wem_city"] {
					order: 1 !important;
				}

				.wem-events-page .wem-events-filters-grid select[name="wem_category"] {
					order: 2 !important;
				}

				.wem-events-page .wem-events-filters-grid .wem-events-date-filter {
					order: 3 !important;
				}

				.wem-events-page .wem-events-filters-grid button[type="submit"] {
					order: 4 !important;
				}

				.wem-events-page .wem-events-filters-grid .wem-events-reset {
					grid-column: auto !important;
					justify-self: stretch !important;
					order: 5 !important;
					width: 100% !important;
				}

				.wem-events-page .wem-events-grid {
					display: flex !important;
					flex-direction: column !important;
					gap: 16px !important;
					width: 100% !important;
				}

				.wem-events-page .wem-events-grid .wem-event-card {
					height: 507px !important;
					width: 100% !important;
				}

				.wem-events-page .wem-events-grid .wem-event-card-image {
					flex-basis: 275px !important;
					min-height: 275px !important;
				}
			}

			/* Grid collision fixes for real event content. */
			.wem-events-page .wem-events-filters-grid button[type="submit"],
			.wem-events-page .wem-events-more {
				align-items: center !important;
				display: inline-flex !important;
				justify-content: center !important;
				line-height: normal !important;
				text-align: center !important;
			}

			.wem-events-page .wem-events-filters-grid button[type="submit"] {
				appearance: none !important;
				box-sizing: border-box !important;
				font-family: Barlow, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
				vertical-align: middle !important;
			}

			.wem-events-page .wem-events-filters-grid .wem-events-date-filter {
				min-width: 190px !important;
			}

			.wem-events-page .wem-events-filters-grid .wem-events-date-filter input {
				overflow: hidden !important;
				text-overflow: ellipsis !important;
				white-space: nowrap !important;
			}

			.wem-events-page .wem-events-grid {
				align-items: stretch !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card {
				height: auto !important;
				min-height: 507px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card-body {
				display: grid !important;
				flex: 1 1 auto !important;
				gap: 14px !important;
				grid-template-rows: auto auto 1fr !important;
				overflow: visible !important;
				padding: 0 16px 8px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-tags {
				margin-bottom: 2px !important;
				max-height: none !important;
				min-height: 30px !important;
				overflow: visible !important;
			}

			.wem-events-page .wem-events-grid .wem-event-tags span {
				min-height: 30px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card h3 {
				display: block !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			.wem-events-page .wem-events-grid .wem-event-card h3 a {
				-webkit-box-orient: vertical !important;
				-webkit-line-clamp: 2 !important;
				display: -webkit-box !important;
				font-size: 24px !important;
				line-height: 1.2 !important;
				max-height: 58px !important;
				overflow: hidden !important;
			}

			.wem-events-page .wem-events-grid .wem-event-meta {
				align-content: start !important;
				align-self: start !important;
				gap: 12px !important;
				padding-top: 2px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-meta > span {
				line-height: 1.5 !important;
				min-height: 24px !important;
			}

			.wem-events-page .wem-events-grid .wem-event-schedule {
				position: relative !important;
			}

			.wem-events-page .wem-events-grid .wem-event-schedule:before {
				background: #64acc3 !important;
			}

			.wem-events-page .wem-events-grid .wem-event-schedule-trigger {
				color: rgba(19, 27, 35, 0.72) !important;
			}

			.wem-events-page .wem-events-grid .wem-event-schedule-popover {
				z-index: 50 !important;
			}

			@media (max-width: 767px) {
				.wem-events-page .wem-events-filters-grid .wem-events-date-filter {
					min-width: 0 !important;
				}

				.wem-events-page .wem-events-grid .wem-event-card {
					height: auto !important;
					min-height: 507px !important;
				}
			}

			/* Match the old page date-period filter and keep carousel drag smooth. */
			.wem-events-page .wem-featured-carousel .wem-featured-events.is-dragging {
				cursor: grabbing !important;
				scroll-behavior: auto !important;
				scroll-snap-type: none !important;
			}

			.wem-events-page .wem-featured-carousel .wem-featured-events.is-dragging .wem-event-card,
			.wem-events-page .wem-featured-carousel .wem-featured-events.is-dragging .wem-event-card * {
				pointer-events: none !important;
				user-select: none !important;
			}

			.wem-events-page .wem-events-filters-grid {
				grid-template-columns: minmax(320px, 469px) 112px minmax(140px, 155px) minmax(150px, 190px) minmax(220px, 240px) !important;
			}

			.wem-events-page .wem-events-reset {
				align-items: center !important;
				background: transparent !important;
				border: 0 !important;
				border-radius: 0 !important;
				color: #64acc3 !important;
				display: inline-flex !important;
				font-size: 14px !important;
				font-weight: 500 !important;
				height: auto !important;
				justify-content: center !important;
				line-height: 1.5 !important;
				grid-column: 1 / -1 !important;
				justify-self: flex-end !important;
				padding: 0 !important;
				text-decoration: underline !important;
				text-underline-offset: 3px !important;
				white-space: nowrap !important;
			}

			.wem-events-page .wem-events-reset:hover,
			.wem-events-page .wem-events-reset:focus {
				color: #4d9ab2 !important;
			}

			.wem-events-page .wem-events-date-period {
				align-items: center !important;
				background: #fff !important;
				border: 1px solid #64acc3 !important;
				border-radius: 100px !important;
				box-shadow: 0 24px 32px -24px rgba(19, 27, 35, 0.1) !important;
				display: grid !important;
				gap: 0 !important;
				grid-template-columns: 42px minmax(126px, 1fr) 42px !important;
				height: 48px !important;
				margin: 0 !important;
				min-height: 48px !important;
				min-width: 220px !important;
				overflow: hidden !important;
				padding: 0 !important;
				position: relative !important;
				width: 100% !important;
			}

			.wem-events-page .wem-events-date-period:before,
			.wem-events-page .wem-events-date-period:after {
				content: none !important;
				display: none !important;
			}

			.wem-events-page .wem-events-date-step {
				align-items: center !important;
				appearance: none !important;
				background: transparent !important;
				border: 0 !important;
				border-radius: 0 !important;
				box-shadow: none !important;
				cursor: pointer !important;
				display: inline-flex !important;
				height: 48px !important;
				justify-content: center !important;
				min-height: 48px !important;
				padding: 0 !important;
				position: relative !important;
				width: 42px !important;
			}

			.wem-events-page .wem-events-date-step:before {
				background: #64acc3 !important;
				content: "" !important;
				height: 18px !important;
				width: 18px !important;
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m15 18-6-6 6-6'/%3E%3C/svg%3E") center/contain no-repeat !important;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m15 18-6-6 6-6'/%3E%3C/svg%3E") center/contain no-repeat !important;
			}

			.wem-events-page .wem-events-date-step.next:before {
				transform: rotate(180deg) !important;
			}

			.wem-events-page .wem-events-date-picker {
				align-items: center !important;
				background: transparent !important;
				border: 0 !important;
				box-shadow: none !important;
				display: flex !important;
				height: 48px !important;
				justify-content: center !important;
				min-height: 48px !important;
				min-width: 0 !important;
				padding: 0 !important;
				position: relative !important;
			}

			.wem-events-page .wem-events-date-picker:before,
			.wem-events-page .wem-events-date-picker:after {
				content: none !important;
				display: none !important;
			}

			.wem-events-page .wem-events-date-button {
				align-items: center !important;
				color: rgba(19, 27, 35, 0.72) !important;
				cursor: pointer !important;
				display: inline-flex !important;
				font-size: 14px !important;
				font-weight: 500 !important;
				gap: 8px !important;
				justify-content: center !important;
				line-height: 1.5 !important;
				max-width: 100% !important;
				overflow: hidden !important;
				padding: 0 4px !important;
				text-overflow: ellipsis !important;
				white-space: nowrap !important;
			}

			.wem-events-page .wem-events-date-button:before {
				background: #64acc3 !important;
				content: "" !important;
				flex: 0 0 24px !important;
				height: 24px !important;
				width: 24px !important;
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M7 3v3M17 3v3M4 9h16M6 5h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
			}

			.wem-events-page .wem-events-date-picker input[type="date"] {
				appearance: none !important;
				background: transparent !important;
				border: 0 !important;
				box-shadow: none !important;
				cursor: pointer !important;
				height: 48px !important;
				inset: 0 !important;
				min-height: 48px !important;
				opacity: 0 !important;
				padding: 0 !important;
				position: absolute !important;
				width: 100% !important;
				z-index: 3 !important;
			}

			@media (max-width: 767px) {
				.wem-events-page .wem-events-date-period {
					min-width: 0 !important;
					width: 100% !important;
				}
			}

			/* Native scroll carousel: keep every event visible and avoid transform drift. */
			.wem-events-page .wem-featured-carousel {
				overflow: hidden !important;
			}

			.wem-events-page .wem-featured-carousel .wem-featured-events {
				box-sizing: border-box !important;
				cursor: grab !important;
				display: flex !important;
				flex-wrap: nowrap !important;
				gap: 24px !important;
				margin-left: 0 !important;
				margin-right: 0 !important;
				overflow-x: auto !important;
				overflow-y: hidden !important;
				padding-left: 0 !important;
				padding-right: 0 !important;
				scroll-behavior: smooth !important;
				scroll-padding-left: 0 !important;
				scroll-snap-type: x mandatory !important;
				scrollbar-width: none !important;
				touch-action: pan-x pan-y !important;
				transform: none !important;
				transition: none !important;
				user-select: none !important;
				will-change: scroll-position !important;
			}

			.wem-events-page .wem-featured-carousel .wem-featured-events::-webkit-scrollbar {
				display: none !important;
			}

			.wem-events-page .wem-featured-carousel .wem-featured-events.is-animating,
			.wem-events-page .wem-featured-carousel .wem-featured-events.is-dragging {
				transition: none !important;
			}

			.wem-events-page .wem-featured-carousel .wem-featured-events *,
			.wem-events-page .wem-featured-carousel .wem-featured-events .wem-event-card {
				user-select: none !important;
			}

			.wem-events-page .wem-featured-carousel .wem-featured-events .wem-event-card {
				display: flex !important;
				opacity: 1 !important;
				scroll-snap-align: start !important;
				visibility: visible !important;
			}

			@media (min-width: 768px) {
				.wem-events-page .wem-featured-carousel .wem-featured-events .wem-event-card {
					flex: 0 0 776px !important;
				}
			}

			.wem-events-page .wem-featured-carousel .wem-featured-events img,
			.wem-events-page .wem-featured-carousel .wem-featured-events a {
				-webkit-user-drag: none !important;
				user-drag: none !important;
			}

			.wem-events-page .wem-event-card[data-wem-modal-trigger] {
				cursor: pointer !important;
			}

			.wem-events-page .wem-event-card.has-placeholder-image .wem-event-card-image,
			.wem-event-modal-hero.has-placeholder-image {
				background:
					radial-gradient(circle at 20% 18%, rgba(255, 255, 255, 0.35), transparent 26%),
					radial-gradient(circle at 76% 26%, rgba(100, 172, 195, 0.42), transparent 28%),
					linear-gradient(135deg, #cfd5d7 0%, #7f8b91 48%, #2f4854 100%) !important;
			}

			.wem-event-modal-open {
				overflow: hidden !important;
			}

			.wem-event-modal-overlay {
				align-items: flex-start !important;
				background: rgba(19, 27, 35, 0.5) !important;
				box-sizing: border-box !important;
				display: flex !important;
				inset: 0 !important;
				justify-content: center !important;
				overflow: auto !important;
				padding: 48px 16px !important;
				position: fixed !important;
				z-index: 999999 !important;
			}

			.wem-event-modal,
			.wem-event-modal * {
				box-sizing: border-box !important;
			}

			.wem-event-modal {
				background: #fff !important;
				border-radius: 32px !important;
				color: #131b23 !important;
				font-family: Barlow, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
				max-width: 740px !important;
				overflow: hidden !important;
				width: min(740px, 100%) !important;
			}

			.wem-event-modal-hero {
				background-color: #d9d9d9 !important;
				background-position: center !important;
				background-size: cover !important;
				border-radius: 24px !important;
				display: flex !important;
				flex-direction: column !important;
				justify-content: space-between !important;
				margin: 8px 8px 0 !important;
				min-height: 275px !important;
				overflow: hidden !important;
				padding: 16px !important;
				position: relative !important;
			}

			.wem-event-modal-hero:before {
				background: linear-gradient(180deg, rgba(19, 27, 35, 0) 0%, #131b23 100%) !important;
				bottom: 0 !important;
				content: "" !important;
				height: 90% !important;
				left: 0 !important;
				opacity: 0.7 !important;
				position: absolute !important;
				right: 0 !important;
				z-index: 0 !important;
			}

			.wem-event-modal-hero .wem-event-date,
			.wem-event-modal-close,
			.wem-event-modal-hero-content {
				position: relative !important;
				z-index: 1 !important;
			}

			.wem-event-modal-hero .wem-event-date {
				border-radius: 8px !important;
				height: 64px !important;
				left: auto !important;
				top: auto !important;
				width: 64px !important;
			}

			.wem-event-modal-close {
				appearance: none !important;
				background: transparent !important;
				border: 0 !important;
				cursor: pointer !important;
				height: 24px !important;
				padding: 0 !important;
				position: absolute !important;
				right: 16px !important;
				top: 16px !important;
				width: 24px !important;
			}

			.wem-event-modal-close:before,
			.wem-event-modal-close:after {
				background: #fff !important;
				border-radius: 999px !important;
				content: "" !important;
				height: 2px !important;
				left: 2px !important;
				position: absolute !important;
				top: 11px !important;
				width: 20px !important;
			}

			.wem-event-modal-close:before {
				transform: rotate(45deg) !important;
			}

			.wem-event-modal-close:after {
				transform: rotate(-45deg) !important;
			}

			.wem-event-modal-hero-content {
				align-items: flex-end !important;
				display: grid !important;
				gap: 16px !important;
				grid-template-columns: minmax(0, 1fr) auto !important;
			}

			.wem-event-modal-hero h2 {
				color: #fff !important;
				font-size: 40px !important;
				font-weight: 500 !important;
				letter-spacing: 0 !important;
				line-height: 1.1 !important;
				margin: 0 !important;
				max-width: 460px !important;
			}

			.wem-event-modal-social {
				align-items: center !important;
				display: flex !important;
				gap: 16px !important;
				justify-content: flex-end !important;
			}

			.wem-event-modal-social a,
			.wem-event-modal-social span {
				align-items: center !important;
				color: #fff !important;
				display: inline-flex !important;
				font-size: 16px !important;
				font-weight: 700 !important;
				height: 16px !important;
				justify-content: center !important;
				line-height: 1 !important;
				text-decoration: none !important;
				width: 16px !important;
			}

			.wem-event-modal-social .instagram:before { content: "◎" !important; }
			.wem-event-modal-social .facebook:before { content: "f" !important; }
			.wem-event-modal-social .tiktok:before { content: "♪" !important; }
			.wem-event-modal-social .twitter:before { content: "X" !important; }

			.wem-event-modal-content {
				display: flex !important;
				flex-direction: column !important;
				gap: 40px !important;
				padding: 40px !important;
			}

			.wem-event-modal-content .wem-event-tags {
				display: flex !important;
				flex-wrap: wrap !important;
				gap: 4px !important;
			}

			.wem-event-modal-content .wem-event-tags span {
				background: rgba(19, 27, 35, 0.04) !important;
				border-radius: 100px !important;
				color: rgba(19, 27, 35, 0.5) !important;
				font-size: 12px !important;
				font-weight: 600 !important;
				line-height: 14px !important;
				padding: 8px 12px !important;
			}

			.wem-event-modal-section {
				display: flex !important;
				flex-direction: column !important;
				gap: 16px !important;
			}

			.wem-event-modal-section h3 {
				color: #131b23 !important;
				font-size: 20px !important;
				font-weight: 500 !important;
				letter-spacing: 0 !important;
				line-height: 1.2 !important;
				margin: 0 !important;
			}

			.wem-event-modal-section p,
			.wem-event-modal-text {
				color: rgba(19, 27, 35, 0.72) !important;
				font-size: 16px !important;
				font-weight: 400 !important;
				line-height: 1.5 !important;
				margin: 0 !important;
			}

			.wem-event-modal-text p {
				margin: 0 0 16px !important;
			}

			.wem-event-modal-text p:last-child {
				margin-bottom: 0 !important;
			}

			.wem-event-modal-facts {
				display: grid !important;
				gap: 8px !important;
				grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) !important;
			}

			.wem-event-modal-facts.has-schedule {
				grid-template-columns: minmax(180px, 0.9fr) minmax(0, 2.1fr) !important;
			}

			.wem-event-modal-facts.has-schedule .wem-event-modal-schedule:first-child {
				grid-column: 1 / -1 !important;
			}

			.wem-event-modal-fact {
				align-items: center !important;
				background: #f8f8f8 !important;
				border: 0.5px solid #f5f5f5 !important;
				border-radius: 24px !important;
				display: flex !important;
				flex-direction: column !important;
				gap: 16px !important;
				justify-content: center !important;
				min-height: 130px !important;
				padding: 16px 8px !important;
				text-align: center !important;
			}

			.wem-event-modal-fact span,
			.wem-event-modal-links span {
				background: #64acc3 !important;
				display: inline-block !important;
				flex: 0 0 auto !important;
				height: 24px !important;
				width: 24px !important;
			}

			.wem-event-modal-fact span {
				height: 32px !important;
				width: 32px !important;
			}

			.wem-event-modal-fact.location span {
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
			}

			.wem-event-modal-fact.calendar span,
			.wem-event-modal-links .calendar span {
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 2h2v3h6V2h2v3h2a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2V2Zm12 8H5v10h14V10Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 2h2v3h6V2h2v3h2a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2V2Zm12 8H5v10h14V10Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
			}

			.wem-event-modal-fact.time span {
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5v5.1l3.4 2.04-1 1.72L11 13.2V7h2Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 5v5.1l3.4 2.04-1 1.72L11 13.2V7h2Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
			}

			.wem-event-modal-schedule {
				background: #f8f8f8 !important;
				border: 0.5px solid #f5f5f5 !important;
				border-radius: 24px !important;
				display: flex !important;
				flex-direction: column !important;
				gap: 14px !important;
				min-width: 0 !important;
				padding: 18px !important;
			}

			.wem-event-modal-schedule-head {
				align-items: center !important;
				display: flex !important;
				gap: 10px !important;
			}

			.wem-event-modal-schedule-head span {
				background: #64acc3 !important;
				display: inline-block !important;
				flex: 0 0 24px !important;
				height: 24px !important;
				width: 24px !important;
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 2h2v3h6V2h2v3h2a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2V2Zm12 8H5v10h14V10Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 2h2v3h6V2h2v3h2a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2V2Zm12 8H5v10h14V10Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
			}

			.wem-event-modal-schedule-head strong {
				color: rgba(19, 27, 35, 0.72) !important;
				font-size: 18px !important;
				font-weight: 500 !important;
				line-height: 1.2 !important;
			}

			.wem-event-modal-schedule-list {
				display: grid !important;
				gap: 8px !important;
			}

			.wem-event-modal-schedule-row {
				align-items: center !important;
				background: #fff !important;
				border-radius: 14px !important;
				color: rgba(19, 27, 35, 0.72) !important;
				display: grid !important;
				font-size: 15px !important;
				gap: 10px !important;
				grid-template-columns: 42px minmax(0, 1fr) minmax(92px, auto) !important;
				line-height: 1.35 !important;
				min-height: 44px !important;
				padding: 10px 12px !important;
			}

			.wem-event-modal-schedule-row strong {
				color: #64acc3 !important;
				font-size: 13px !important;
				font-weight: 700 !important;
				line-height: 1.2 !important;
				text-transform: uppercase !important;
			}

			.wem-event-modal-schedule-row span,
			.wem-event-modal-schedule-row small {
				font-size: 15px !important;
				font-weight: 500 !important;
				line-height: 1.35 !important;
				overflow-wrap: anywhere !important;
			}

			.wem-event-modal-schedule-row small {
				text-align: right !important;
			}

			.wem-event-modal-links .route span {
				-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm3.5 13.5 3-9 4 4-7 5Zm6-7-3-3 4.5-1.5-1.5 4.5Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
				mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm3.5 13.5 3-9 4 4-7 5Zm6-7-3-3 4.5-1.5-1.5 4.5Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
			}

			.wem-event-modal-fact strong {
				color: rgba(19, 27, 35, 0.72) !important;
				font-size: 18px !important;
				font-weight: 500 !important;
				letter-spacing: 0 !important;
				line-height: 1.2 !important;
				overflow-wrap: anywhere !important;
			}

			.wem-event-modal-links {
				display: grid !important;
				gap: 8px !important;
				grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
			}

			.wem-event-modal-links a {
				align-items: center !important;
				background: #f8f8f8 !important;
				border: 0.5px solid #f5f5f5 !important;
				border-radius: 16px !important;
				color: rgba(19, 27, 35, 0.72) !important;
				display: inline-flex !important;
				font-size: 18px !important;
				font-weight: 500 !important;
				gap: 16px !important;
				justify-content: center !important;
				line-height: 1.2 !important;
				min-height: 56px !important;
				padding: 16px 8px !important;
				text-align: center !important;
				text-decoration: none !important;
			}

			.wem-event-modal-action-wrap {
				display: flex !important;
				justify-content: center !important;
			}

			.wem-event-modal-action {
				align-items: center !important;
				background: #64acc3 !important;
				border: 1px solid #64acc3 !important;
				border-radius: 100px !important;
				color: #fff !important;
				display: inline-flex !important;
				font-size: 16px !important;
				font-weight: 600 !important;
				justify-content: center !important;
				line-height: 1.2 !important;
				min-height: 56px !important;
				min-width: 209px !important;
				padding: 8px 16px !important;
				text-decoration: none !important;
			}

			.wem-event-modal-divider {
				border-top: 1px solid #ddd !important;
				width: 100% !important;
			}

			.wem-event-modal-contact {
				color: #64acc3 !important;
				font-size: 18px !important;
				font-weight: 500 !important;
				letter-spacing: 0.3px !important;
				line-height: 1.6 !important;
				overflow-wrap: anywhere !important;
			}

			@media (max-width: 767px) {
				.wem-event-modal-overlay {
					padding: 8px !important;
				}

				.wem-event-modal {
					border-radius: 32px !important;
					width: 100% !important;
				}

				.wem-event-modal-hero {
					min-height: 275px !important;
				}

				.wem-event-modal-hero-content {
					align-items: start !important;
					grid-template-columns: 1fr !important;
				}

				.wem-event-modal-hero h2 {
					font-size: 24px !important;
					max-width: 260px !important;
				}

				.wem-event-modal-social {
					justify-content: flex-start !important;
				}

				.wem-event-modal-content {
					gap: 32px !important;
					padding: 60px 16px 24px !important;
				}

				.wem-event-modal-facts,
				.wem-event-modal-links {
					grid-template-columns: 1fr !important;
				}

				.wem-event-modal-facts.has-schedule {
					grid-template-columns: 1fr !important;
				}

				.wem-event-modal-fact {
					min-height: 102px !important;
				}

				.wem-event-modal-schedule {
					padding: 16px !important;
				}

				.wem-event-modal-schedule-row {
					align-items: start !important;
					grid-template-columns: 1fr !important;
				}

				.wem-event-modal-schedule-row small {
					text-align: left !important;
				}

				.wem-event-modal-links a {
					flex-direction: column !important;
					min-height: 94px !important;
				}

				.wem-event-modal-contact {
					text-align: center !important;
				}
			}
		</style>
		<?php
	}
}
