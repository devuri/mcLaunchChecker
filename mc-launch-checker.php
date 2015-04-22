<?php
/*
Plugin Name: Manifest Launch Checker
Plugin URI: http://manifestbozeman.com
Description: Pre-flight utility that checks for common configuration problems.
Version: 1.0
Author: Philip Downer
Author URI: http://philipdowner.com
License: GPLv2
*/

/*  Copyright 2012  Philip Downer  (email : philip@manifestbozeman.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//NAMESPACE
// manifest_mlc_*

add_action('admin_notices', 'manifest_mlc_admin_notices');
function manifest_mlc_admin_notices() {
	//MAKE SURE THE FUNCTION EXISTS FOR THE PLUGIN
	if ( !function_exists('manifest_get_environment') ) {
		exit('The function manifest_get_environment() must be available.');
	}
	
	//SETUP A BLANK ARRAY FOR NOTICES
	$notices = array();
	
	//CHECK TO SEE WHAT THE ENVIRONMENT IS
	if ( manifest_get_environment() !== 'live' ) {
		$notices[] = array(
			'class' => 'error',
			'message' => 'The current developer environment is set to "'.manifest_get_environment().'". This should be set to "live" before launching the site.'
		);
	}
	
	//SEE IF THE WP-MIGRATE.PHP FILE exists
	if ( file_exists(ABSPATH.'wp-migrate.php')) {
		$notices[] = array(
			'class' => 'error',
			'message' => 'The wp-migrate.php file is still in the root directory. This could cause the site to be hacked. Delete the file.',
		);
	}
	
	//MAKE SURE THE ADMIN EMAIL IS SET CORRECTLY
	if ( get_option('admin_email') == 'pd2@manifestbozeman.com' ) {
		$notices[] = array(
			'class' => 'error',
			'message' => 'The admin email is <u>'.get_option('admin_email').'</u>. This should probably be set to the client\'s email address.',
			'link' => 'options-general.php',
			'link_text' => 'Change the email address &raquo;',
		);
	}
	
	//MAKE SURE SEARCH ENGINES ARE NOT BLOCKED
	if ( !get_option('blog_public') ) {
		$notices[] = array(
			'class' => 'updated',
			'message' => 'The site is blocked to search engines. If this is not your intention, modify the setting before launching the site.',
			'link' => 'options-privacy.php',
			'link_text' => 'Allow search engines &raquo;',
		);
	}
	
	//MAKE SURE AKISMET IS ENABLED
	if ( !is_plugin_active('akismet/akismet.php') ) {
		$notices[] = array(
			'class' => 'error',
			'message' => 'The Akismet plugin should be activated and configured.',
			'link' => 'plugins.php',
			'link_text' => 'Go to plugins &raquo;'
		);
	}
	
	
	//MAKE SURE GOOGLE ANALYTICS IS CONFIGURED CORRECTLY
	if ( !is_plugin_active('google-analytics-for-wordpress/googleanalytics.php') ) {
		$manifest_options = get_option('manifest_theme_options');
		if ( !empty($manifest_options) && empty($manifest_options['analytics_ua']) ) {
			$notices[] = array(
				'class' => 'error',
				'message' => 'No Google Analytics UA code has been provided in the theme options.',
				'link' => 'themes.php?page=theme_options',
				'link_text' => 'Edit the theme options',
			);
		} elseif ( empty($manifest_options) ) {
			$notices[] = array(
				'class' => 'updated',
				'message' => 'This theme does not use Manifest\'s standard theme options. You should manually check to ensure that Google Analytics tracking code is embedded in the theme\'s code. Alternatively, use a plugin such as <em>Yoast WP SEO</u> to add the code.',
			);
		}
	} else { //YOAST GOOGLE ANALYTICS IS ACTIVE
		$yoast_options = get_option('Yoast_Google_Analytics');
		if ( !$yoast_options['uastring'] ) {
			$notices[] = array(
				'class' => 'error',
				'message' => 'No Google Analytics UA code has been provided to the Yoast Google Analytics plugin. This will result in the site not being tracked by Google Analytics.',
				'link' => 'options-general.php?page=google-analytics-for-wordpress',
				'link_text' => 'Update the theme options &raquo;'
			);
		}
	}
	
	//CHECK KEY THEME OPTIONS
	$manifest_options = get_option('manifest_theme_options');
	if ( !empty($manifest_options) ) {
		$recommended = array('email','telephone','address');
		$options_check = false;
		foreach ( $recommended as $option ) {
			if ( empty($manifest_options[$option]) ) {
				$options_check = true;
			}
		}
		if ( $options_check ) {
			$notices[] = array(
				'class' => 'updated',
				'message' => 'Several key theme options (like email, telephone and address) are blank. Many theme options rely on this functionality. Please double-check before launching the site.',
				'link' => 'themes.php?page=theme_options',
				'link_text' => 'Update the theme options &raquo;'
			);
		}
	}
	
	//IS THE LAUNCH CHECKER PLUGIN ACTIVE?
	if ( manifest_get_environment() != "development" ) {
		$notices[] = array(
			'class' => 'error',
			'message' => 'The Manifest Launch Checker plugin should be disabled before launching the site.',
			'link' => 'plugins.php',
			'link_text' => 'Disable this plugin &raquo;',
		);
	}
	
	//CHECK THE WP_DEBUG CONSTANT
	if ( defined('WP_DEBUG') ) {
		if ( WP_DEBUG == true ) {
			$notices[] = array(
				'class' => 'error',
				'message' => 'The PHP constant \'WP_DEBUG\' is set to true. Please modify the wp_config.php file and change the setting to false',
			);
		}
	}
	
	//PRINT OUT THE ERROR MESSAGES
	foreach ( $notices as $notice ) {
		echo '<div class="'.$notice['class'].'">';
			echo '<p>'.$notice['message'].'</p>';
			if ( $notice['link'] ) {
				echo '<p><a href="'.get_admin_url(get_current_blog_id(),$notice['link']).'">'.$notice['link_text'].'</a></p>';
			}
		echo '</div>';
	}
}
?>