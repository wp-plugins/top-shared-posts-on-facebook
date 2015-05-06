<?php

/*
Plugin Name: Top Shared Posts on Facebook
Description: Top shared posts on Facebook fetches total share counts for your blog posts on Facebook using Facebook Graph API and sorts the most shared ones in an ascending order together with their total share counts.
Author: Samuel Elh
Version: 0.1.2
Author URI: https://profiles.wordpress.org/elhardoum
*/

add_action('admin_menu', 'tspf_create_menu');
add_action( 'admin_init', 'register_tspf_settings' );

function tspf_create_menu() {
	global $tspf_settings_page;
	$tspf_settings_page = add_options_page( 'Top shared posts on Facebook settings', 'TSPF settings', 'manage_options', 'tspf_settings', 'tspf_settings_page' );
}

function tspf_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=tspf_settings">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'tspf_settings_link' );

function register_tspf_settings() {
	register_setting( 'tspf-settings-group', 'tspf_posts_count' );
	register_setting( 'tspf-settings-group', 'tspf_default_thumbnail' );
}

function tspf_settings_page() {
?>

<div class="wrap">

<h2>Top shared posts on Facebook - Settings</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'tspf-settings-group' ); ?>
    <?php do_settings_sections( 'tspf-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row"><label for="tspf_posts_count">Max. posts to show</label></th>
        <td><input type="number" name="tspf_posts_count" value="<?php echo esc_attr( get_option('tspf_posts_count') ); ?>" id="tspf_posts_count" max="20" min="2" />
		<br /><sub>Maximum items to show in the TSPF widget.</sub>
		</td>
        </tr>
		
		<tr valign="top">
        <th scope="row"><label for="tspf_default_thumbnail">Default thumbnail</label></th>
        <td><input type="text" size="36" name="tspf_default_thumbnail" value="<?php echo esc_attr( get_option('tspf_default_thumbnail') ); ?>" id="tspf_default_thumbnail" /><input id="tspf_default_thumbnail_btn" class="button" type="button" value="Upload Image" />
		<br /><sub>Enter a URL or upload an image to use as a default thumbnail in case a post doesn't have one set up already</sub>
		</td>
        </tr>
		
    </table>
	
	<?php submit_button(); ?>

</form>

<fieldset style="border: 1px solid #D3C6C6; padding: 1.33em;">
	<legend style="padding: 0 1em; font-weight: bold;">Widget Preview:</legend>
	<?php tspf_data('../wp-blog-header.php'); ?>
</fieldset>

</div>

<?php 

}

function tspf_script() {
?>
<script>
var main = document.getElementById( 'tspf-cont' );
var load = document.getElementById( 'tspf-loading' );

[].map.call( main.children, Object ).sort( function ( a, b ) {
    return +b.id.match( /\d+/ ) - +a.id.match( /\d+/ );
}).forEach( function ( elem ) {

    main.appendChild( elem );
    load.style.display = 'none';
    main.style.display = 'block';

});

$('#tspf-cont li').hide().filter(':lt(<?php echo tspf_default_post_count(); ?>)').show().filter('.na').hide();

var $msg = $('.tspf-msg');

if ($('ul.tspf-cont > li.count').length) {
    $msg.hide();    
} else {
    $msg.show();  
}

</script>

<?php
}

	function short_count($data) {
		$id = get_the_ID();
		$json_data = get_transient( 'tspf_cache_'. $id .'_tr' );
			if ($json_data === false) {
				$json = file_get_contents( 'http://api.facebook.com/restserver.php?method=links.getStats&format=json&urls=' . $data . '&pretty=1' );
				$json_data = json_decode($json, true);
				set_transient( 'tspf_cache_'. $id .'_tr', $json_data, 3600 );
			}
		$num = $json_data[0]['total_count'];
		if(empty ($num) ) {
			return 'n/a';
		} else {
			if( $num < 1000 ) return $num;
			$x = round($num);
			$x_number_format = number_format($x);
			$x_array = explode(',', $x_number_format);
			$x_parts = array('k', 'm', 'b', 't');
			$x_count_parts = count($x_array) - 1;
			$x_display = $x;
			$x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
			$x_display .= $x_parts[$x_count_parts - 1];
			return  $x_display;
		}

	}
	function tspf_hide_empty($data) {
		if ( $data == "n/a" ) return 'na';
		else return 'count';
	}
	function tspf_img() {
		if ( has_post_thumbnail() ) {
			return wp_get_attachment_url( get_post_thumbnail_id($post->ID) );
		} elseif (!get_option('tspf_default_thumbnail')) {
			return plugin_dir_url( __FILE__ ) . 'includes/no-thumbnail.jpg';
		} else {
			return esc_attr( get_option('tspf_default_thumbnail') );
		}
	}
	function tspf_default_post_count() {
		if (!get_option('tspf_posts_count')) {
			return esc_attr( '5' );
		} else {
			return esc_attr( get_option('tspf_posts_count') );
		}
	}
	function no_count_msg() {
		return '
		<p class="tspf-msg" style="display: none;">
			<strong>Nothing found.</strong>
			<br />
			<span>Looks like none of your posts have counts OR you don&#39;t have posts at all.</span>
			<br/>
			<span>&mdash; &#8220;Top Shared Posts on Facebook&#8220; Plugin</span>
		</p>';
	}
function tspf_data($wp_head) {

	require_once( $wp_head );

	

	query_posts('&showposts=-1');

		if(have_posts()) {

        	echo '
        	<noscript>You need JavaScript enabled to view this content</noscript>
			<div id="tspf-loading">
				<img src="'. plugin_dir_url( __FILE__ ) . 'includes/spinner.gif' .'" />
				<span>loading..</span>
			</div>
			<section id="tspf"><ul id="tspf-cont" class="tspf-cont" style="display: none;">';
			
			while(have_posts()) : the_post();

				$prmlk = '"'. get_the_permalink(). '",';
				$links = array( $prmlk );
				foreach ($links as $items) {
					$link = get_the_permalink();
					$img = tspf_img();
					$title = get_the_title();
					$id = get_the_ID();
					$short_count = short_count($link);
					$hide_empty = tspf_hide_empty($short_count);
					
					echo '
					<li id="'. $short_count . '" class="tspf-'. $id . ' ' . $hide_empty . '"> 
						<a href="'. $link .'" title="This post was shared ' . $short_count . ' times" class="tspf-avatar">
							<style type="text/css">#tspf ul li.tspf-' . $id . ' a.tspf-avatar:before { content: "'. $short_count . '"; }</style>
							<img src="' . plugin_dir_url( __FILE__ ) . 'includes/pixel.gif' . '" style="background-image: url('. $img .');" height="60" width="60" alt="'. $title .'" />
						</a>
						<div class="tspf-link">
							<a href="'. $link .'">
								<span>'. $title .'</span>
							</a>
							<br />
							<span class="tspf-count">'. $short_count .' shares</span>
						</div>
					</li>';

				}

			endwhile;
			wp_reset_query();

		}
		
			echo no_count_msg(). '</u></section>';
			return tspf_script();

}

add_action('admin_enqueue_scripts', 'tspf_admin_scripts');
 
function tspf_admin_scripts() {
    if (isset($_GET['page']) && $_GET['page'] == 'tspf_settings') {
        wp_enqueue_media();
		wp_register_script('tspf-enq-1', plugin_dir_url( __FILE__ ) . 'includes/admin.js', array('jquery'));
		wp_register_script( 'tspf-enq-2', '//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js', array('jquery') );
		wp_enqueue_script('tspf-enq-1');
		wp_enqueue_script('tspf-enq-2');
		wp_enqueue_style('tspf-css', plugin_dir_url( __FILE__ ) . 'includes/style.css' );
    }
}

add_action( 'wp_enqueue_scripts', 'tspf_enqueue_scripts' );  
	
function tspf_enqueue_scripts() {
	wp_register_script( 'tspf-enq-wp-js', '//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js', array('jquery') );
	wp_enqueue_script('tspf-enq-wp-js');
	wp_enqueue_style('tspf-wp-css', plugin_dir_url( __FILE__ ) . 'includes/style.css' );
}



// TSPF widget

class tspf_widget extends WP_Widget {

function __construct() {
	parent::__construct(
		'tspf_widget', 
		__('Top shared posts on Facebook', 'wordpress'), 
		array( 'description' => __( 'Displays top shared x posts on Facebook for your blog', 'wordpress' ), ) 
	);
}

public function widget( $args, $instance ) {
	$title = apply_filters( 'widget_title', $instance['title'] );
	echo $args['before_widget'];
	if ( ! empty( $title ) )
	echo $args['before_title'] . $title . $args['after_title'];
	echo tspf_data('./wp-blog-header.php');
	echo $args['after_widget'];
}
		
public function form( $instance ) {
	if ( isset( $instance[ 'title' ] ) ) {
		$title = $instance[ 'title' ];
	} else {
		$title = __( 'Top shared on Facebook', 'wordpress' );
}

?>
<p>
	<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Widget title:' ); ?></label> 
	<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>
<?php
}
	
public function update( $new_instance, $old_instance ) {
	$instance = array();
	$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
	return $instance;
}
}

function tspf_load_widget() {
	register_widget( 'tspf_widget' );
}
add_action( 'widgets_init', 'tspf_load_widget' );

// The end !