<?php
/**
 * Общие хелперы блога (core-посты): мета-строка, время чтения, карточка статьи.
 *
 * Карточка и мета-строка одинаковы в blog-hero (featured), blog-grid и
 * article-related — одна функция, без дубля разметки. Иконки — служебные SVG
 * темы (assets/blog/*.svg), инлайнятся в currentColor.
 *
 * @package rosenberger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Инлайн служебной иконки темы (assets/blog/<name>.svg), окрашивается currentColor.
 */
function rosenberger_blog_icon( string $name ): string {
	static $cache = array();
	if ( ! isset( $cache[ $name ] ) ) {
		$path           = get_theme_file_path( "assets/blog/icon-{$name}.svg" );
		$cache[ $name ] = is_readable( $path ) ? (string) file_get_contents( $path ) : '';
	}
	return $cache[ $name ];
}

/**
 * Оценка времени чтения статьи: ~200 слов/мин, минимум 1 мин.
 */
function rosenberger_blog_reading_time( int $post_id ): int {
	$content = get_post_field( 'post_content', $post_id );
	$words   = str_word_count( wp_strip_all_tags( (string) $content ) );
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Мета-строка статьи: дата • время чтения • автор (с иконками).
 */
function rosenberger_blog_meta_html( int $post_id ): string {
	$date    = get_the_date( 'd.m.y', $post_id );
	$minutes = rosenberger_blog_reading_time( $post_id );
	$author  = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) );

	ob_start();
	?>
	<div class="blog-meta">
		<span class="blog-meta__item"><?php echo rosenberger_blog_icon( 'date' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $date ); ?></span>
		<span class="blog-meta__item"><?php echo rosenberger_blog_icon( 'time' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $minutes ); ?>&nbsp;Min.</span>
		<span class="blog-meta__item"><?php echo rosenberger_blog_icon( 'person' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $author ); ?></span>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Карточка статьи для сетки (изображение + бейдж + мета + заголовок + Weiterlesen).
 *
 * @param int  $post_id Запись.
 * @param bool $badge   Показать бейдж «Empfohlener Beitrag» (для sticky-постов).
 */
function rosenberger_blog_card_html( int $post_id, bool $badge = false ): string {
	$link      = get_permalink( $post_id );
	$title     = get_the_title( $post_id );
	$thumb_id  = get_post_thumbnail_id( $post_id );
	$thumb_src = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';

	ob_start();
	?>
	<a class="blog-card" href="<?php echo esc_url( $link ); ?>">
		<div class="blog-card__image">
			<?php if ( $thumb_src ) : ?>
				<img src="<?php echo esc_url( $thumb_src ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
			<?php endif; ?>
			<?php if ( $badge ) : ?>
				<span class="blog-badge">Empfohlener Beitrag</span>
			<?php endif; ?>
		</div>
		<div class="blog-card__body">
			<?php echo rosenberger_blog_meta_html( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<h3 class="blog-card__title"><?php echo esc_html( $title ); ?></h3>
			<span class="blog-card__more">Weiterlesen&nbsp;&rarr;</span>
		</div>
	</a>
	<?php
	return (string) ob_get_clean();
}
