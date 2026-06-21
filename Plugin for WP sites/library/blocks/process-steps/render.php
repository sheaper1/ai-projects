<?php
defined( 'ABSPATH' ) || exit;

$heading        = wp_kses_post( $attributes['heading'] ?? 'So läuft es' );
$heading_italic = wp_kses_post( $attributes['headingItalic'] ?? 'mit mir ab' );
$subtext        = wp_kses_post( $attributes['subtext'] ?? '' );
$button_text    = wp_kses_post( $attributes['buttonText'] ?? '' );
$button_url     = esc_url( $attributes['buttonUrl'] ?? '#' );
$steps          = is_array( $attributes['steps'] ?? null ) ? $attributes['steps'] : [];
$wrapper        = get_block_wrapper_attributes( [ 'class' => 'process-steps' ] );
?>
<section <?php echo $wrapper; ?>>
	<div class="process-steps__inner">
		<div class="process-steps__intro">
			<h2 class="process-steps__heading">
				<?php echo $heading; ?>
				<?php if ( '' !== trim( wp_strip_all_tags( $heading_italic ) ) ) : ?>
					<em><?php echo $heading_italic; ?></em>
				<?php endif; ?>
			</h2>
			<?php if ( $subtext ) : ?>
				<p class="process-steps__subtext"><?php echo $subtext; ?></p>
			<?php endif; ?>
			<?php if ( $button_text ) : ?>
				<a class="process-steps__button" href="<?php echo $button_url; ?>"><?php echo $button_text; ?></a>
			<?php endif; ?>
		</div>
		<div class="process-steps__list">
			<?php foreach ( $steps as $step ) : ?>
				<article class="process-steps__item">
					<div class="process-steps__number"><?php echo esc_html( $step['number'] ?? '' ); ?></div>
					<div class="process-steps__content">
						<h3><?php echo esc_html( $step['title'] ?? '' ); ?></h3>
						<p><?php echo esc_html( $step['text'] ?? '' ); ?></p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
