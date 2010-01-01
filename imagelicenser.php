<?php

/*
Plugin Name: Image Licenser
Plugin URI: http://www.raphael-mack.de/wp-image-licenser/
Description: Easy tagging of images with a <a href="http://www.creativecommons.org">CreativeCommons</a> license using RDFa to enable search engines to understand the licensing meta data.
Version: 1.0
License: GPL
Author: Raphael Mack
Author URI: http://www.raphael-mack.de
Text Domain: imagelicenser
Domain Path: /lang/

Contact mail: mail@raphael-mack.de
*/


// prevent plugin from being used with wp versions under 2.9; otherwise do nothing!
global $wp_db_version;
if ( $wp_db_version >= 12329 ) {

// prevent file from being accessed directly

if ('imagelicenser.php' == basename($_SERVER['SCRIPT_FILENAME']))
	die ('Please do not access this file directly. Thanks!');

define("IL_VERSION", 10);
if (function_exists('load_plugin_textdomain')) load_plugin_textdomain('imagelicenser','/wp-content/plugins/image-licenser/langs');

// initiate options and variables
function imagelicenser_initialize() {
	add_option('imagelicenser_small', "false");
	add_option('imagelicenser_version', EV_VERSION);
	add_option('imagelicenser_showlink', "true");
	add_option('imagelicenser_defaultversion', "3.0");
	update_option('imagelicenser_version', EV_VERSION);
}

if ('true' == get_option('imagelicenser_space')) {
	$ev_space = '&nbsp;';
} else {
	$ev_space = '';
}

/***********************/
// logic

function ilic_shortcode_handler($atts, $content=null, $code="") {
   // $atts    ::= array of attributes
   // $content ::= text within enclosing form of shortcode element
   // $code    ::= the shortcode found, when == callback name

	$tag = $atts['lic'];
	// todo: tag (lic attribute) shall be nonempty

	if($atts['version']){
		$version = $atts['version'];
	}else{
		$version = get_option('imagelicenser_defaultversion');
	}
	if($atts['port']){
		$port = $atts['port'];
	}else{
		$port = get_option('imagelicenser_defaultport');
	}
	$link = $atts['link'];
	if ($link == "false") {
		$link = 0;
	}else if ($link == "true" || (get_option('imagelicenser_showlink') == 'true')) {
		$link = 1;
	}else{
		$link = 0;
	}

	$img = do_shortcode($content);

	preg_match("/src\s*=([\"'])(.*)(\\1)/", $img, $srcc);
	$output .= '<div about="' . $srcc[2] . '">';
	if (!is_feed()) $output .= "\n<!-- generated by WordPress plugin Image Licenser -->\n";
	$output .= $img;
	$licurl = 'http://creativecommons.org/licenses/' . $tag . '/' . $version . '/';
	if(strlen($port) > 0){
		$licurl .= $port . '/';
	}

	$output .= '<p class="wp-image-license">';
	$ev_small = get_option('imagelicenser_small');
	if ('true' == $ev_small) $output .= "<small>";

	if($link){
		$output .= "\n" . __('image by', 'imagelicenser') . ' ';
		$output .= '<a xmlns:cc="http://creativecommons.org/ns#" href="';
		$output .= get_the_author_meta('user_url') . '"';
		$output .= '" property="cc:attributionName" rel="cc:attributionURL">';
		$output .= get_the_author_meta('first_name') . ' ' . get_the_author_meta('last_name');
		$output .= '</a>, ';
	}
	$output .= "\n<a rel='license' href='$licurl'>";

	$licname = __("Creative Commons", "imagelicenser");
	$licname .= " ";
	$licname .= __($tag, "imagelicenser");
	$licname .= " ";
	$licname .= __("License", "imagelicenser");
	$licname .= " ";
	$licname .= $version;
	if(strlen($port) > 0){
		$licname .= " ";
		$licname .= __("Port_" . $port, "imagelicenser");
	}
	if($link){
		$output .= $licname;
	}
	if ('true' == $ev_small) $output .= "</small>";
	$output .= '</p>';
	$output .= "</a>\n";
	$output .= "</div>";

	return ($output);
	
}

// required filters
add_shortcode('cc', 'ilic_shortcode_handler');

//build admin interface
function imagelicenser_option_page() {

	global $wpdb, $table_prefix;
	$hidden_field_name = 'ilic_submit_hidden';

	if( $_POST[ $hidden_field_name ] == 'Y' ){
		echo '<div id="message" class="updated fade"><p><strong>' . __('Options saved.', 'imagelicenser' ) . '</strong></p></div>';
		if (!empty($_POST['imagelicenser_small'])) {
			update_option('imagelicenser_small', "true");
		} else {
			update_option('imagelicenser_small', "false");
		}

		if (!empty($_POST['imagelicenser_showlink'])) {
			update_option('imagelicenser_showlink', "true");
		} else {
			update_option('imagelicenser_showlink', "false");
		}
		update_option('imagelicenser_defaultversion', $_POST['imagelicenser_defaultversion']);
		update_option('imagelicenser_defaultport', $_POST['imagelicenser_defaultport']);
	}

	if ('true' == get_option('imagelicenser_small')) {
		$ev_small = 'checked="true"';
	} else {
		$ev_small = '';
	}
	if ('true' == get_option('imagelicenser_showlink')) {
		$ev_showlink = 'checked="true"';
	} else {
		$ev_showlink = '';
	}
	?>
	<div class="wrap" id="imagelicenser_options_panel">
	<?php echo '<h2>' . __('Image Licenser','imagelicenser') . '</h2>';?>

	<p><?php _e('Choose defaults for the image licenser plugin to tag images with a <a href="http://creativecommons.org/">CreativeCommons license</a> here. All images embedded in the <i>cc</i> shortcode will be equipped with <a href="http://wiki.creativecommons.org/RDFa">RDFa</a> tags to enable search engins to detect the license of the image.','imagelicenser'); ?>
<?php _e('For detailed information see the','imagelicenser'); ?> <a href="http://wordpress.org/extend/plugins/image-licenser/" title="<?php _e('plugin page','imagelicenser'); ?>"><?php _e('plugin page','imagelicenser'); ?></a> or the <a href="http://www.raphael-mack.de/wp-image-licenser/">website of the autor</a>.</p>

	<h3><?php echo __('Usage','imagelicenser'); ?></h3>
	[cc lic="<i>license</i>" version="<i>ver</i>" port="<i>country code</i>" link="<i>true|false</i>"]&lt;img src="..."&gt;[/cc]
	<br />
	<p>
		<i>license</i> shall be the code of the CreativeCommons license, one of "by", "by-nc", "by-nd", ...<br />
		<i>version</i> shall be the version of the CreativeCommons license, e. g. "3.0"<br />
		<i>country code</i> shall be the jurisdiction of the CreateiveCommons license, e. g. "de" for Germany<br />
	</p>
	<p>
	<?php _e('You may adapt your CSS style sheet to include a style for .wp-image-license.','imagelicenser'); ?>
	</p>
	<h3><?php _e('Examples','imagelicenser'); ?></h3>
	<p>
	[cc lic="by-nc" version="3.0" port="de"]&lt;img src="..."&gt;[/cc]
	</p>
	<h3><?php _e('Configuration','imagelicenser'); ?></h3>
	<div>
		<form name="ilic_opt_form" method="post" action="">
			<div>
				<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

				<label for="imagelicenser_showlink" style="cursor: pointer;"><input type="checkbox" name="imagelicenser_showlink" id="imagelicenser_showlink" value="<?php echo get_option('imagelicenser_showlink') ?>" <?php echo $ev_showlink; ?> /> <?php echo _e('Show the License link (if neither link="true" nor link="false" is given)','imagelicenser'); ?></label><br />
				<label for="imagelicenser_small" style="cursor: pointer;"><input type="checkbox" name="imagelicenser_small" id="imagelicenser_small" value="<?php echo get_option('imagelicenser_small') ?>" <?php echo $ev_small; ?> /> <?php echo _e('Use smaller font size for link','imagelicenser'); ?></label><br />
				 <?php echo _e('Version used, if no version is given in shortcode','imagelicenser'); ?>: <input type="text" name="imagelicenser_defaultversion" id="imagelicenser_defaultversion" value="<?php echo get_option('imagelicenser_defaultversion') ?>" /><br />
				 <?php echo _e('Jurisdiction used, if no port is given in shortcode','imagelicenser'); ?>: <input type="text" name="imagelicenser_defaultport" id="imagelicenser_defaultport" value="<?php echo get_option('imagelicenser_defaultport') ?>" /><br />
				<input type="hidden" name="page_options" value="imagelicenser_showlink,imagelicenser_small,imagelicenser_defaultversion,imagelicenser_defaultport" />
				<p class="submit">
					<input type="submit" name="Submit" id="imagelicenser_update_options" value="<?php echo _e('Save settings','imagelicenser'); ?> &raquo;" />
				</p>
			</div>
		</form>
	</div>
</div>
<?php
}

function imagelicenser_add_options_panel() {
	add_options_page('Image Licenser', 'Image Licenser', 'manage_options', 'imagelicenser_options_page', 'imagelicenser_option_page');
}

function imagelicenser_register_plugin_links($links, $file) {
	if (function_exists('load_plugin_textdomain')) load_plugin_textdomain('imagelicenser','/wp-content/plugins/image-licenser/langs');
	if ($file == plugin_basename(__FILE__)) {
		$links[] = '<a href="options-general.php?page=imagelicenser_options_page">' . __('Configuration','imagelicenser') . '</a>';
	}
	return $links;
}

if (( isset($_GET['activate']) && $_GET['activate'] == 'true' ) || ( get_option('imagelicenser_version') <> IL_VERSION )) {
	add_action('init', 'imagelicenser_initialize');
}
add_action('admin_menu', 'imagelicenser_add_options_panel');
add_filter('plugin_row_meta', imagelicenser_register_plugin_links, 10, 2);

} // closing if for version check

?>
