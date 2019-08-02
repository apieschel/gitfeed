<?php 

/**
 * @package Gitfeed
 */
/*
Plugin Name: Gitfeed
Plugin URI: https://github.com/apieschel/gitfeed
Description: Plugin for displaying a feed for your latest Git commits on a portfolio site.
Version: 1.0
Author: Alex Pieschel
Author URI: https://gtrsoftware.com
License: GPLv2 or later
Text Domain: gitfeed
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

load_plugin_textdomain('gitfeed', false, basename( dirname( __FILE__ ) ) . '/languages' );

if ( file_exists( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'env.php' ) ) {
	include_once 'env.php';
	add_action('init', gf_set_up_env);
}

add_shortcode( 'gitfeed', 'gf_git_feed' );

function gf_git_feed() {
	$user = get_option('gf_user');
	$password = get_option('gf_pass');
	$url = 'https://api.github.com/users/' . $user . '/repos';
	$headers = array('Authorization' => 'Basic '.base64_encode("$user:$password"), 'User-Agent' => $user);
	$wpget = wp_remote_get( $url, array('headers' => $headers) );
	$data = json_decode($wpget["body"]);
	$repos = array();
	
	if(gettype($data) == 'object' OR !$password OR !$user) {
		echo '<div class="container">Uh oh, it looks like either your credentials need to be entered, or you have exceeded the API call limit.</div>';
	} else {
		// loop through the data, and create a new array with timestamps as keys
		for($i = 0; $i < count($data); $i++) {
			$array = array();
			$current = $data[$i];
			array_push($array, $current->name);
			array_push($array, $current->description);
			array_push($array, $current->language);
			// subtract five hours to adjust to U.S. Central time
			$repos[strtotime($data[$i]->updated_at) - (60 * 60 * 5)] = $array;
		}
		curl_close($ch);
		
		// sort the array in reverse order according to the timestamps
		krsort($repos);
		
		// if there are more than 10 repos, then only keep the 10 most recently updated
		// maintain the existing keys for the timestamps
		if(count($repos) > 9) {
			$repos = array_slice($repos, 0, 10, TRUE);
		}
		
		$args = array();
		$commits = array();
		$commit_stats = array();
		
		// use built-in Wordpress Requests class to send multiple aynchronous requests
		foreach($repos as $key=>$value) {
			$url = 'https://api.github.com/repos/' . $user . '/' . $value[0] . '/commits';
			$headers = array('Authorization' => 'Basic '.base64_encode("$user:$password"));
			array_push($args, array('type' => 'GET', 'headers' => $headers, 'url' => $url));
		}
		
		$responses = Requests::request_multiple($args);

		foreach ($responses as $response) {
				if (!is_a( $response, 'Requests_Response' )) {
						echo 'We got a ' . $response->status_code . ' error on our hands.<br><br><br>';
						break;
				}
				// handle success
				$data = json_decode($response->body);
				//var_dump($data);
				$commits[strtotime($data[0]->commit->author->date) - (60 * 60 * 5)] = $data[0];
		}
		
		krsort($commits);
		$commits = array_values($commits);

		// loop to grab latest commit stats and patch data for each commit
		foreach($commits as $key=>$value) {
			$sha = $value->sha;
			$url = $value->url;
			$headers = array('Authorization' => 'Basic '.base64_encode("$user:$password"), 'User-Agent' => $user);
			$wpget = wp_remote_get( $url, array('headers' => $headers) );
			$data = json_decode($wpget["body"]);
			array_push($commit_stats, $data);
		}
		
		// display the data
		echo '<div class="container-fluid">';
			echo '<h2 style="font-size:1.4rem; font-weight:normal; text-align:center; margin-bottom:20px;">This custom WordPress plugin displays a feed of ' . $user . '&apos;s Git repos, sorted from the most recently updated.</h2>';
			echo '<p style="text-align:center; margin-bottom:40px;"><a target="_blank" style="color:#0000EE;" href="https://github.com/' . $user . '">Link to ' . $user . '&apos;s Github Page</a></p>';
			
			$count = 0;
			foreach($repos as $key=>$value) {	
				echo '<div style="background:#edffed; border:1px solid lightgrey; margin:0 auto; margin-bottom:20px; padding:40px; width:75%;">';
					echo '<h3 style="text-align:center; font-size:1.3rem; margin-bottom:20px;"><strong>' . $value[0] . '</strong>: ' . $value[1] . '</h3>';
					echo '<p><span style="color:green;"><em>';
					esc_html_e('Last updated', 'gitfeed');
					echo '</em>: ' . date("F j, Y, g:i a", $key) . ' U.S. Central Time</span></p>';
				
					$response = $commits[$count];
					echo '<p><em>Latest Commit</em>: ' . $response->commit->message . '</p>';
				
					$response2 = $commit_stats[$count];
					echo '<p><em>Total Code Changes</em>: ' . $response2->stats->total . '</p>';	
					echo '<p><em>Lines Added</em>: ' . $response2->stats->additions . '</p>';	
					echo '<p><em>Lines Deleted</em>: ' . $response2->stats->deletions . '</p>';	
				
					foreach($response2->files as $key=>$value) {
							echo '<p><em>File Changed</em>: ' . $response2->files[$key]->filename . '</p>';
							echo '<p><em>Patch</em>: ' . esc_html($response2->files[$key]->patch) . '</p>';
					}
				echo '</div>';
				$count++;
			}
		echo '</div>';
	}
}

// Set up admin options page
// https://www.smashingmagazine.com/2016/03/making-a-wordpress-plugin-that-uses-service-apis/
add_action( "admin_menu", "gf_plugin_menu_func" );
function gf_plugin_menu_func() {
   add_submenu_page( "options-general.php",  // Which menu parent
                  "Gitfeed",            // Page title
                  "Gitfeed",            // Menu title
                  "manage_options",       // Minimum capability (manage_options is an easy way to target administrators)
                  "gitfeed",            // Menu slug
                  "gf_plugin_options"     // Callback that prints the markup
               );
}

// Print the markup for the page
function gf_plugin_options() {
  if ( !current_user_can( "manage_options" ) )  {
     wp_die( __( "You do not have sufficient permissions to access this page." ) );
  }
	
	if ( isset($_GET['status']) AND $_GET['status']=='success') { ?>
   <div id="message" class="updated notice is-dismissible">
      <p><?php esc_html_e("Settings updated!", "gitfeed"); ?></p>
      <button type="button" class="notice-dismiss">
         <span class="screen-reader-text"><?php esc_html_e("Dismiss this notice.", "gitfeed"); ?></span>
      </button>
   </div>
	<?php } ?>	 
	
	<form method="post" action="<?php echo esc_url(admin_url( 'admin-post.php')); ?>">
		<input type="hidden" name="action" value="update_github_settings" />

		<h3><?php esc_html_e("GitHub Account Information", "gitfeed"); ?></h3>
		<p>
			<label><?php esc_html_e("GitHub User: ", "gitfeed"); ?></label>
			<input class="" type="text" name="gf_user" value="<?php echo esc_attr(get_option('gf_user')); ?>" />
		</p>

		<p>
			<label><?php esc_html_e("GitHub Password: ", "gitfeed"); ?></label>
			<input class="" type="text" name="gf_pass" value="<?php echo esc_attr(get_option('gf_pass')); ?>" />
		</p>

		<input class="button button-primary" type="submit" value="<?php esc_html_e("Save", "gitfeed"); ?>" />
	</form> 
<?php }

add_action( 'admin_post_update_github_settings', 'gf_handle_save' );

function gf_handle_save() {
   // Get the options that were sent
   $user = (!empty($_POST["gf_user"])) ? $_POST["gf_user"] : NULL;
   $pass = (!empty($_POST["gf_pass"])) ? $_POST["gf_pass"] : NULL;

   // Validation

   // Update the values
   update_option( "gf_user", $user, TRUE );
   update_option("gf_pass", $pass, TRUE);

   // Redirect back to settings page
   // The ?page=github corresponds to the "slug" 
   // set in the fourth parameter of add_submenu_page() above.
   $redirect_url = get_bloginfo("url") . "/wp-admin/options-general.php?page=gitfeed&status=success";
   header("Location: ".$redirect_url);
   exit;
}