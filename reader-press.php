<?php
/*
Plugin Name: Reader Press
Plugin URI: http://andrewjesaitis.com/projects/reader-digest
Description: Send your shared items in Google Reader to your blog.
Version: 0.0
Author: Andrew Jesaitis
Author URI: http://andrewjesaitis.com
*/

//Copyright 2010  Andrew Jesaitis  (email : andrew@andrewjesaitis.com)
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
//
// This is an add-on for WordPress
// http://wordpress.org/
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************

/***************************************
*SET UP FUNCTIONS
****************************************/
register_activation_hook(__FILE__, 'add_defaults_fn');
add_action('admin_menu', 'rd_create_menu');
add_action('admin_init', 'register_mysettings' );
add_action('rd_cron', 'rd_getData');
add_filter('cron_schedules', 'register_schedules');
register_deactivation_hook(__FILE__, 'unregister_cron');

/***************************************
*BEGIN OPTIONS PAGE
***************************************/

//Define default option settings
function add_defaults_fn() {
    $arr = array("rd_feed" => "Paste Feed URL Here", "rd_time" => "06:00", "rd_interval" => "Daily", "rd_number" => "5", "rd_title" => "Enter title here", "rd_category"=>"Uncategorized");
    update_option('plugin_options', $arr);
}

//Set up functional relationships for options page
function register_mysettings() {
	register_setting('plugin_options', 'plugin_options', 'plugin_options_validate');
	add_settings_section('main_section', 'Settings', 'section_text_fn', __FILE__);
	add_settings_field('rd_feed', 'Google Reader Shared Feed', 'rd_feed_string_fn', __FILE__, 'main_section');
	add_settings_field('rd_time', 'Time (HH:MM in 24h):', 'rd_time_string_fn', __FILE__, 'main_section');
	add_settings_field('rd_interval', 'Post Inverval:', 'rd_interval_dropdown_fn', __FILE__, 'main_section');
	//add_settings_field('rd_number', 'Max Number of Items to Post', 'rd_number_string_fn', __FILE__, 'main_section');
	add_settings_field('rd_title', 'Post Title', 'rd_title_string_fn', __FILE__, 'main_section');
	add_settings_field('rd_category', 'Select Category', 'setting_dropdown_fn', __FILE__, 'main_section');
}

function rd_create_menu() {
	add_options_page('Reader Press Plugin Settings', 'Reader Press', 'administrator', __FILE__, 'rd_settings_page_fn');
}

/****************CALLBACK FUNCTIONS*********************/
function  section_text_fn() {
	echo '<p>Please set the following options.</p>';
}

function rd_feed_string_fn() {
	$options = get_option('plugin_options');
	echo "<input id='rd_feed' name='plugin_options[rd_feed]' size='120' type='text' value='{$options['rd_feed']}' />";
}

function rd_time_string_fn() {
	$options = get_option('plugin_options');
	echo "<input id='rd_time' name='plugin_options[rd_time]' size='10' type='text' value='{$options['rd_time']}' />";
}

function  rd_interval_dropdown_fn() {
	$options = get_option('plugin_options');
	$items = array("Daily", "Weekly", "Monthy");
	echo "<select id='rd_interval' name='plugin_options[rd_interval]'>";
	foreach($items as $item) {
		$selected = ($options['rd_interval']==$item) ? 'selected="selected"' : '';
		echo "<option value='$item' $selected>$item</option>";
	}
	echo "</select>";
}

function rd_number_string_fn() {
	$options = get_option('plugin_options');
	echo "<input id='rd_number' name='plugin_options[rd_number]' size='10' type='text' value='{$options['rd_number']}' />";
}

function rd_title_string_fn() {
	$options = get_option('plugin_options');
	echo "<input id='rd_title' name='plugin_options[rd_title]' size='80' type='text' value='".wp_specialchars($options['rd_title'])."' />";
}

function  setting_dropdown_fn() {
	$options = get_option('plugin_options');
	$dropdown_options = array('show_option_all' => '',
	 						  'hide_empty' => 0,
 							  'hierarchical' => 1,
                              'show_count' => 0, 
                              'depth' => 0, 
                              'orderby' => 'ID', 
                              'selected' => $options['rd_category'], 
                              'name' => 'plugin_options[rd_category]');
    wp_dropdown_categories($dropdown_options);
}

//Display admin page options
function rd_settings_page_fn() {
?>
<div id="wrap">
	<h2>Reader Press</h2>

		<p>1. Go to Google Reader</p>
		
		<p>2. Click on shared items</p>
		
		<p>3. Click on Sharing settings (Right hand side of header)</p>
		
		<p>4. Click on "Preview your shared items page in a new window"</p>
	
		<p>5. Click on atom feed icon</p>
		
		<p>6. Copy this atom feed url to the field below</p>
		
		<p>Feel free to contact me with feature requests, bugs, or code suggestions. </p>
		
		<p><a href="http://andrewjesaitis.com">AndrewJesaitis.com</a> // <a href="mailto:andrew@andrewjesaitis.com">andrew@andrewjesaitis.com</a></p>
	
<form method="post" action="options.php">
	<?php settings_fields('plugin_options');?>
	<?php do_settings_sections(__FILE__);?>
	<p class="submit">
			<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
			<input name="rd_post" type="submit" class="button-primary"  value="Post Single Digest" />
			<input name="rd_auto" type="submit" class="button-primary" value="Start Automatic Posting" />
		</p>
	</form>
</div>
<?php }

function plugin_options_validate($input) {
	//TODO:code to validate user's choices
	return $input; // return validated input
}

/*********************************
 * BEGIN PLUGIN CODE
 *********************************/

//catch post single button post action
if (isset($_POST['rd_post'])) {
	$parsedFeed = rd_get_data();
	//only want to post if there are entries
	if(count($parsedFeed) >= 1){
		rd_post_digest($parsedFeed);
	}
}
//catch auto post button post action
else if (isset($_POST['rd_auto'])) {
	//grab our options and determine post time and interval
	$options = get_option('plugin_options');
	$rawTime = $options['rd_time'];
	$interval = strtolower($options['rd_interval']);
	//we want to start in the next period, since we shouldn't pass a past date to the cron function
	switch($interval){
		case 'daily':
			$timestamp = strtotime($rawTime . ' +1 ' . 'day');
			break;
		case 'weekly':
			$timestamp = strtotime($rawTime . ' +1 ' . 'week');
			break;
		case 'monthly':
			$timestamp = strtotime($rawTime . ' +1 ' . 'month');
			break;
		//the only way to hit this case is to hard code it above; used for debugging purposes only
		case 'test':
			$timestamp = strtotime('+2 seconds');
			break;
	}
	//TODO: Customize timezone, default to EST for now
	$timestamp -= $offset;
	$offset = -4 * 3600;
	register_cron($timestamp, $interval);
}

/**
 * Registers additional cron frequencies
 * @return void
 */

function register_schedules(){
	return array (
			'weekly'	=>	array (
			'interval'	=>	604800,
			'display'	=>	__( 'Once weekly' )
			),
			'monthly'	=>	array (
			'interval'	=>	108000,
			'display'	=>	__( 'Once monthly' )
			)
	);
}

/**
 * Schedules a cron event
 * @param time $timestamp
 * @param string $interval
 * @return void
 */

function register_cron ($timestamp, $interval){
	if (check_cron_registered()){
		unregister_cron();
	}
	wp_schedule_event($timestamp, $interval, 'rd_cron');
}

/**
 * Unregisters cron event
 * @return void
 */

function unregister_cron(){
	wp_clear_scheduled_hook ('rd_cron');
}

/**
 * Checks if cron event is already scheduled is scheduled to post
 * @return boolean
 */

function check_cron_registered(){
	return wp_next_scheduled('rd_cron');
}
/**
 * Retrieves and parses rss xml
 * @return array Represents the entire xml document parsed into array
 */

function rd_get_data(){
		$options = get_option('plugin_options');
		$feed = $options['rd_feed'];
		$cat = $options['rd_category'];
		
		$xml = @simplexml_load_file($feed);
		
		if(!$xml){
			echo "Error loading data. Check your feed url.";
			return false;
		}
			
		foreach($xml->entry as $data){
				$namespaces = $data->getNameSpaces(true);

				$tmp['title']		= $data->title;
				//first try to get the summary, then try to get content
				if($data->summary){
					$tmp['content'] 	= (string)$data->summary;
				}elseif($data->content){
					$tmp['content'] 	= (string)$data->content;
				}
				$tmp['href'] 		= $data->link['href'];
				$tmp['timestamp']	= strtotime($data->published);
				$feedArr[] = $tmp;			
			
		}

		/* sort feed array by timestamp */
		if(count($feedArr) > 0){
			$tmp = $feedArr;
			foreach ($tmp as $key=>$row) {
				$text[$key] = $row['timestamp'];
			}
		
			array_multisort($text,SORT_DESC,$tmp);
		
			$feedArr = $tmp;
		}
		/* end sorting */
		

		//now query db for last digest post as determined by category
		$args = array('numberposts' =>	1,
					  'category'	=>	$cat,
					  'orderby'	    =>	'date');
		$latestReaderDigest = get_posts($args);
		//just have to set up a loop so get_the_time is happy, but we break during first iteration
		foreach($latestReaderDigest as $e){
			$cutoffTime = get_the_time('U', $e);
			break; //only want first element
		}
		//Get rid of entries that have been posted before
		foreach ($feedArr as $entry){
			if($entry['timestamp'] < $cutoffTime){
				$key = key($feedArr);
				//trucate array
				array_splice($feedArr, ($key + 1));
			}
		}
		return $feedArr;	
}
/**
 * Formats enteries into html and posts entry
 * 
 * @param $postData Array containing entries to post (ie prefiltered)
 * @return void
 */	
function rd_post_digest($postData) {
	$options = get_option('plugin_options');
	$feed = $options['rd_feed'];
	$cat = $options['rd_category'];
	//Post is wrapped in ul tags
	$out = "<ul>";
	//set up i to make sure we don't include more entries than wanted, commented out right now, but may be added back in future
	//$i = 0;
	//$max = $options['rd_number'];
	foreach($postData as $k=>$v){
		//each entry wrapped in li tags
		$out .= "<li>
			<a class=\"digestLink\" href=\"{$v['href']}\">{$v['title']}</a>
			{$v['content']}
			</li><br/>";
		//$i++;
		//if($i == $max) break;	
	}
	$out .= "</ul>";
	   $title = $options['rd_title'];			
	  $readerPost = array(
	     'post_title' => $title,
	     'post_content' => $out,
	     'post_status' => 'publish',
	     'post_author' => 1,
	     'post_category' => array($cat),
	  );
	
	// Insert the post into the database
	wp_insert_post($readerPost);
}
?>