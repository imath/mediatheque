<?php
// Display template
?>

<div id="mediatheque-container" style="height: <?php echo esc_attr( mediatheque_get_tag( 'height' ) ); ?>; width: <?php echo esc_attr( mediatheque_get_tag( 'width' ) ); ?>;">
	<div id="media"></div>
</div>

<?php
/**
 * Output the Feedback underscore's template.
 */
mediatheque_get_template_part( 'feedback', 'mediatheque-feedback' );

/**
 * Output the User Media underscore's template.
 */
mediatheque_get_template_part( 'user-media', 'mediatheque-media' ); ?>
