<?php
/*
Plugin Name: Caperz
Plugin URI: http://www.caperz.us
Description: Show activities for kids on your blog
Version: 0.1
Author: Caperz
Author URI: www.caperz.us
*/

define("SITE_URL","www.caperz.us");
define("API_URL","api.caperz.us");

function url_slug($str) {
	return preg_replace('/--+/','-',preg_replace('/[^a-z0-9-]+/','',preg_replace('/\s+/','-',strtolower($str))));
}

function demilitarize($time) {
	$bits = explode(":",$time);
	$bits[1] = substr("0".$bits[1],-2);
	if ($bits[0] == 0) return "12:".$bits[1]."am";
	else if ($bits[0] == 12) return "12:".$bits[1]."pm";
	else if ($bits[0] < 10) return substr($bits[0],-1).":".$bits[1]."am";
	else if ($bits[0] < 12) return $bits[0].":".$bits[1]."am";
	else return ($bits[0]-12).":".$bits[1]."pm";
}

class wp_caperz extends WP_Widget {

	// constructor
	function wp_caperz() {
		parent::WP_Widget(false, $name = __('Caperz', 'wp_widget_plugin'),
			array( 'description' => __( 'Adds a list of kids activities to your sidebar', 'wp_widget_plugin' ), )
		);
	}

	// widget form creation
	function form($instance) {

		// Check values
		if( $instance) {
			 $zip = esc_attr($instance['zip']);
			 // $textarea = esc_textarea($instance['textarea']);
			 $checkbox = esc_attr($instance['checkbox']);
		} else {
			 $zip = '';
			 $checkbox = ''; 
			 // $textarea = '';
		}
		?>

		<p>
		<label for="<?php echo $this->get_field_id('zip'); ?>"><?php _e('Zip Code', 'wp_widget_plugin'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('zip'); ?>" name="<?php echo $this->get_field_name('zip'); ?>" type="text" value="<?php echo $zip; ?>" />
		</p>
		<p>
		<input id="<?php echo $this->get_field_id('checkbox'); ?>" name="<?php echo $this->get_field_name('checkbox'); ?>" type="checkbox" value="1" <?php checked( '1', $checkbox ); ?> />
		<label for="<?php echo $this->get_field_id('checkbox'); ?>"><?php _e('Widget can link out to Caperz', 'wp_widget_plugin'); ?></label>
		</p>
		<?php
	}

	// update widget
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		// Fields
		$instance['zip'] = strip_tags($new_instance['zip']);
		$instance['checkbox'] = strip_tags($new_instance['checkbox']);
		return $instance;
	}

	// display widget
	function widget($args, $instance) {
?><style>
.caperz_widget p { margin: 0; padding: 0;}
.caperz_title { font-size: 1.5em; }
.caperz_event { margin: 3px 0 3px 0; }
.caperz_topline { font-weight: bold; }
.caperz_sub { font-size: 90%; }
.caperz_more { text-align: right; font-size: 90%; }
.caperz_powered { text-align: center; padding : 5px 0 0 0;}
</style><?php
		extract( $args );
		// these are the widget options
		// $title = apply_filters('widget_title', $instance['title']);
		$zip = $instance['zip'];
		$checkbox = $instance['checkbox'];
		echo $before_widget;
		// Display the widget
		echo '<div class="widget-text wp_widget_plugin_box caperz_widget">';
		echo $before_title."<span class='caperz_title'>Happening Nearby Today</span>".$after_title;
		
		if ($checkbox != true) echo "<div class='caperz_event'>We cannot access the activities API without permission to link out. Update this in Wordpress Admin.</div>";
		elseif (strlen($zip) != 5) echo "<div class='caperz_event'>To show activities, please enter a 5-digit ZIP code in Wordpress Admin.</div>";
		else {
			$date = date('Y/m/d H:i:s');
			$events = file_get_contents("http://".API_URL."/activities.php?zip=".$zip."&events&date=".$date);
			$list = json_decode($events, true);
			if (count($list) < 5) {
				$classes = file_get_contents("http://".API_URL."/activities.php?zip=".$zip."&date=".$date);
				$list = array_slice(array_merge($list, json_decode($classes, true)), 0, 5);
			}

			if (count($list) == 0) echo "<div class='caperz_event'>There are no activities in ZIP ".$zip." today.</div>";
			foreach($list as $key => $value) {
				echo "<div class='caperz_event'>";
				echo "<p class='caperz_topline'><a href='http://".SITE_URL."/providers/".$value["location_id"]."-".url_slug($value["name"])."/".$value["class_id"]."/' target='_blank'>".$value["class_name"]."</a></p>";
				echo "<p class='caperz_sub'>".$value["name"]."</p>";
				echo "<p class='caperz_sub'>".demilitarize($value["start_time"])."</p>";
				echo "</div>";
			}
			echo "<p class='caperz_more'><a href='http://".SITE_URL."/search.php?zip=".$zip."' target='_blank'>&#187; See more activities</a></p>";
			echo "<p class='caperz_powered'><a href='http://".SITE_URL."' target='_blank'><img src='http://".SITE_URL."/images/caperz_pwr.png' style='margin-top: 7px;' /></a></p>";
		}
		echo "</div>";
		echo $after_widget;
	}
}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("wp_caperz");'));

?>