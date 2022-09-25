<?php

/*
Plugin Name:  Moderation Email Recipient Override
Description:  This Plugin overrides the default recipients of moderation emails.
Version:      1.0
Author:       Lukas Bril
Text Domain:  mod-email-recip
Domain Path: /languages
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html

*/
/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */

 
namespace Mod_email_recipient_override;
if ( ! defined( 'ABSPATH' ) ) {
	return;
};
/**
 * custom option and settings
 */
function settings_init() {
	// Register a new setting for "wporg" page.
	register_setting( 'discussion', 'mod_email_recipients_setting', array('sanitize_callback' => 'Mod_email_recipient_override\sanitize_callback') );

	// Register a new section in the "wporg" page.
	add_settings_section(
		'mod_email_recipients_section',
		__( 'Email recipients for comment moderation notifications', 'mod-email-recip' ), 'Mod_email_recipient_override\section_developers_callback',
		'discussion'
	);

	add_settings_field(
		'mod_email_recipients', // As of WP 4.6 this value is used only internally.
		                        // Use $args' label_for to populate the id inside the callback.
			__( 'Moderation email recipients', 'mod-email-recip' ),
			'Mod_email_recipient_override\field_cb',
		'discussion',
		'mod_email_recipients_section',
		array(
			'label_for'         => 'mod_email_recipients',
			'class'             => 'mod_email_recipients_row',
		)
	);
}

/**
 * Register our wporg_settings_init to the admin_init action hook.
 */
add_action( 'admin_init', 'Mod_email_recipient_override\settings_init' );


function my_plugin_init() {
	load_plugin_textdomain( 'mod-email-recip', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
  }
  add_action('init', 'Mod_email_recipient_override\my_plugin_init');

/**
 * Custom option and settings:
 *  - callback functions
 */


/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function section_developers_callback( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Unless empty, this setting overrides the default recipients of comment moderation emails.', 'mod-email-recip' ); ?></p>
	<?php
}

/**
 * Pill field callback function.
 *
 * WordPress has magic interaction with the following keys: label_for, class.
 * - the "label_for" key value is used for the "for" attribute of the <label>.
 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
 * Note: you can add custom key value pairs to be used inside your callbacks.
 *
 * @param array $args
 */
function field_cb( $args ) {
	// Get the value of the setting we've registered with register_setting()
	$options = get_option( 'mod_email_recipients_setting' );
	?>
	<input
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			name="mod_email_recipients_setting[<?php echo esc_attr( $args['label_for'] ); ?>]" type="text" value="<?php echo $options[ $args['label_for'] ]?>">
			
	<p class="description">
		<?php esc_html_e( 'Enter multiple email addresses by separating them with comma', 'mod-email-recip' ); ?>
	</p>
	<?php
}

function sanitize_callback( $input = NULL) {
	$can_validate = method_exists( 'WP_Customize_Setting', 'validate' );
	$temp = $input['mod_email_recipients'];
	$new = '';
	foreach(explode(",",$temp) as $email) {
		if(empty(trim($email))) {
			continue;
		}
		preg_match('/\w+\@\w+\.\w+/',$email, $matches);
		if ( empty($matches)) {
			add_settings_error(
				'mod_email_recipients_setting',
				esc_attr( 'settings_eror' ),
				sprintf(__( '%s does not seem to be a valid email address', 'mod-email-recip' ),trim($email)),
				'error'
			);
		} elseif (!empty(trim($email))) {
			$new .= trim($email).',';
		}
	}
	if (!empty($new)) {
		$input['mod_email_recipients'] = substr_replace($new ,"", -1);
	}
	settings_errors('mod_email_recipients_setting');
	return $input;
}

function override_mod_email_recipients($emails,$cid) {
	$options = get_option( 'mod_email_recipients_setting' );
    // Only do this when a single post is displayed
    if ( !empty($options['mod_email_recipients']) ) { 
	    $emails = array();
		foreach(explode(",",$options['mod_email_recipients']) as $email) {
			if (!empty(trim($email))) {
				array_push($emails,trim($email));
			}
		}
	}
	return $emails;
}
    // Hook our function to WordPress the_content filter
add_filter('comment_moderation_recipients','Mod_email_recipient_override\override_mod_email_recipients',10,2);