<?php

/**
 * Class Github_API
 */
class Github_API {
	
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize the Github API shortcode.
	 */
	private function init() {
		add_shortcode( 'gitfeed', array( $this, 'gf_git_feed' ) );	
		
		add_action( 'admin_menu', array( $this, 'gf_plugin_menu_func' ) );
		
		add_action( 'admin_post_update_github_settings', array( $this, 'gf_handle_save' ) );
	}
	
	/**
	 * Handle the API requests.
	 */
	public function gf_git_feed() {
		$user = get_option('gf_user');
		$password = get_option('gf_pass');
		$url = 'https://api.github.com/users/' . $user . '/repos';
		$headers = array('Authorization' => 'Basic '.base64_encode("$user:$password"), 'User-Agent' => $user);
		$wpget = wp_remote_get( $url, array('headers' => $headers) );
		$data = json_decode($wpget["body"]);
		$repos = array();

		if(gettype($data) == 'object' OR !$password OR !$user) {
			echo '<div class="container">Uh oh, it looks like either your credentials need to be entered, or you have exceeded the API call limit.</div>';
			var_dump($this);
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

			// sort the array in reverse order according to the timestamps
			krsort($repos);

			// if there are more than 10 repos, then only keep the 10 most recently updated
			// maintain the existing keys for the timestamps
			if(count($repos) > 9) {
				$repos = array_slice($repos, 0, 10, TRUE);
			}

			$args = array();
			$args2 = array();
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
				$commits[strtotime($data[0]->commit->author->date) - (60 * 60 * 5)] = $data[0];
			}

			krsort($commits);
			$commits = array_values($commits);

			foreach($commits as $key=>$value) {
				$url = $value->url;
				$headers = array('Authorization' => 'Basic '.base64_encode("$user:$password"));
				array_push($args2, array('type' => 'GET', 'headers' => $headers, 'url' => $url));
			}

			$responses2 = Requests::request_multiple($args2);

			foreach ($responses2 as $response) {
				if (!is_a( $response, 'Requests_Response' )) {
					echo 'We got a ' . $response->status_code . ' error on our hands.<br><br><br>';
					break;
				}
				// handle success
				$data = json_decode($response->body);
				$commit_stats[strtotime($data->commit->author->date) - (60 * 60 * 5)] = $data;
			}

			krsort($commit_stats);
			$commit_stats = array_values($commit_stats);

			// display the data ?>
			<div class="container-fluid">
				<h2 class="gf-header">This custom WordPress plugin displays a feed of <?php echo $user ?>&apos;s Git repos, sorted from the most recently updated.</h2>
				<p class="gf-subhead">
					<a target="_blank" class="gf-link" href="https://github.com/<?php echo $user; ?>">Link to <?php echo $user ?>&apos;s Github Page</a>
				</p>

				<?php 
				$count = 0;
				foreach($repos as $key=>$value): ?>	
					<div class="gf-repo">
						<h3 class="gf-repohead"><strong><?php echo $value[0]; ?></strong>: <?php echo $value[1]; ?></h3>
						<p>
							<span class="gf-green">
								<em><?php esc_html_e('Last updated', 'gitfeed'); ?></em>: <?php echo date("F j, Y, g:i a", $key); ?> U.S. Central Time
							</span>
						</p>
						
						<?php
						$response = $commits[$count];
						echo '<p><em>';
						esc_html_e('Latest Commit', 'gitfeed');
						echo '</em>: ' . $response->commit->message . '</p>';

						$response2 = $commit_stats[$count];
						echo '<p><em>';
						esc_html_e('Total Code Changes: ', 'gitfeed');
						echo '</em>: ' . $response2->stats->total . '</p>';	
						echo '<p><em>';
						esc_html_e('Lines Added: ', 'gitfeed');
						echo '</em>: ' . $response2->stats->additions . '</p>';	
						echo '<p><em>Lines Deleted</em>: ' . $response2->stats->deletions . '</p>';	

						foreach($response2->files as $key=>$value) {
								echo '<p><em>File Changed</em>: ' . $response2->files[$key]->filename . '</p>';
								echo '<p><em>Patch</em>: ' . esc_html($response2->files[$key]->patch) . '</p>';
						}
					echo '</div>';
					$count++;
				endforeach;
			echo '</div>';
		}
	}
	
	/** 
	 * Set up admin options page
	 * https://www.smashingmagazine.com/2016/03/making-a-wordpress-plugin-that-uses-service-apis/
	 */
	public function gf_plugin_menu_func() {
		 add_submenu_page( "options-general.php",  // Which menu parent
				"Gitfeed",            // Page title
				"Gitfeed",            // Menu title
				"manage_options",       // Minimum capability (manage_options is an easy way to target administrators)
				"gitfeed",            // Menu slug
				array($this, "gf_plugin_options")     // Callback that prints the markup
		 );
	}

	/** 
	 * Print the markup for the admin options page
	 */
	public function gf_plugin_options() {
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
				<input class="" type="password" name="gf_pass" value="<?php echo esc_attr(get_option('gf_pass')); ?>" />
			</p>

			<input class="button button-primary" type="submit" value="<?php esc_html_e("Save", "gitfeed"); ?>" />
		</form> 
	<?php }
	
	public function gf_handle_save() {
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
}