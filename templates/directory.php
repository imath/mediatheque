<?php
// Directory template
?>

<div id="mediatheque-container">
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
