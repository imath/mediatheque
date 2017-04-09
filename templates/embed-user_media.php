<?php
/**
 * Contains the post embed base template for the User Media Post type
 *
 * When a post is embedded in an iframe, this file is used to create the output
 * if the active theme does not include an embed-user_media.php template.
 *
 * @package mediatheque
 * @since 1.0.0
 */

get_header( 'embed' );

if ( have_posts() ) :
	while ( have_posts() ) : the_post(); ?>
		<div <?php post_class( 'wp-embed' ); ?>>

			<p class="wp-embed-heading">
				<a href="<?php the_permalink(); ?>" target="_top">
					<?php the_title(); ?>
				</a>
			</p>

			<div class="wp-embed-excerpt"><?php mediatheque_embed_excerpt(); ?></div>

			<div class="wp-embed-footer">
				<?php the_embed_site_title() ?>

				<div class="wp-embed-meta">
					<?php
					/**
					 * Prints additional meta content in the embed template.
					 *
					 * @since 1.0.0
					 */
					do_action( 'mediatheque_embed_content_meta' ); ?>
				</div>
			</div>
		</div>

	<?php endwhile;
else :
	get_template_part( 'embed', '404' );
endif;

get_footer( 'embed' );
