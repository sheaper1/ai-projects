<?php
/**
 * Объект-Übersicht: слева Kurzbeschreibung + тёмная карточка маклера,
 * справа Objektbeschreibung (длинный текст из редактора записи).
 * Данные маклера — глобальные («Настройки сайта»).
 *
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$post_id   = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
$icons     = get_stylesheet_directory_uri() . '/assets/property/icons/';
$short     = get_post_meta( $post_id, 'property_short_desc', true );
$nr        = get_post_meta( $post_id, 'property_object_nr', true );
$desc_html = apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) );

$agent = array(
	'name'     => function_exists( 'rosenberger_contact' ) ? rosenberger_contact( 'agent_name' ) : '',
	'role'     => function_exists( 'rosenberger_contact' ) ? rosenberger_contact( 'agent_role' ) : '',
	'phone'    => function_exists( 'rosenberger_contact' ) ? rosenberger_contact( 'agent_phone' ) : '',
	'email'    => function_exists( 'rosenberger_contact' ) ? rosenberger_contact( 'agent_email' ) : '',
	'portrait' => function_exists( 'rosenberger_contact' ) ? rosenberger_contact( 'agent_portrait' ) : '',
);
$tel = preg_replace( '/[^+0-9]/', '', (string) $agent['phone'] );
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'property-overview' ) ); ?>>
	<div class="property-overview__inner">

		<div class="property-overview__col property-overview__col--left">
			<?php if ( $short ) : ?>
			<div class="property-overview__intro">
				<h2 class="property-overview__heading">Kurzbeschreibung</h2>
				<p class="property-overview__lead"><?php echo esc_html( $short ); ?></p>
			</div>
			<?php endif; ?>

			<aside class="property-overview__agent">
				<?php if ( $agent['portrait'] ) : ?>
				<div class="property-overview__agent-photo">
					<img src="<?php echo esc_url( $agent['portrait'] ); ?>" alt="<?php echo esc_attr( $agent['name'] ); ?>" loading="lazy" />
				</div>
				<?php endif; ?>
				<div class="property-overview__agent-body">
					<div class="property-overview__agent-id">
						<?php if ( $agent['name'] ) : ?><span class="property-overview__agent-name"><?php echo esc_html( $agent['name'] ); ?></span><?php endif; ?>
						<?php if ( $agent['role'] ) : ?><span class="property-overview__agent-role"><?php echo esc_html( $agent['role'] ); ?></span><?php endif; ?>
					</div>
					<ul class="property-overview__agent-contacts">
						<?php if ( $agent['phone'] ) : ?>
						<li>
							<span class="property-overview__agent-icon"><img src="<?php echo esc_url( $icons . 'phone.svg' ); ?>" alt="" width="12" height="12" /></span>
							<a href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo esc_html( $agent['phone'] ); ?></a>
						</li>
						<?php endif; ?>
						<?php if ( $agent['email'] ) : ?>
						<li>
							<span class="property-overview__agent-icon"><img src="<?php echo esc_url( $icons . 'email.svg' ); ?>" alt="" width="12" height="12" /></span>
							<a href="mailto:<?php echo esc_attr( $agent['email'] ); ?>"><?php echo esc_html( $agent['email'] ); ?></a>
						</li>
						<?php endif; ?>
						<?php if ( $nr ) : ?>
						<li class="property-overview__agent-nr"><span>Objekt-Nr.:</span><span><?php echo esc_html( $nr ); ?></span></li>
						<?php endif; ?>
					</ul>
					<div class="property-overview__agent-actions">
						<a class="property-overview__btn property-overview__btn--solid" href="/kontakt/">Besichtigung anfragen</a>
						<a class="property-overview__btn property-overview__btn--ghost" href="/kontakt/">Exposé als PDF</a>
					</div>
				</div>
			</aside>
		</div>

		<div class="property-overview__col property-overview__col--right">
			<h2 class="property-overview__heading">Objektbeschreibung</h2>
			<div class="property-overview__desc"><?php echo wp_kses_post( $desc_html ); ?></div>
		</div>

	</div>
</section>
