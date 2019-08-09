<?php
/**
 * Repo Page.
 *
 * @since 1.0
 * @package Gitfeed
 *
 * @var arr $commits  		 array of commit data.
 * @var arr $commit_stats  array of stats for latest commits.
 * @var arr $repos  			 array of repositories.
 */

?>

<div>
	<h2 class="gf-header">
		<?php esc_html_e('This custom WordPress plugin displays a feed of Git repos, sorted from the most recently updated.', 'gitfeed'); ?>
	</h2>
	<p class="gf-subhead">
		<a target="_blank" class="gf-link" href="<?php echo esc_url('https://github.com/' . $user); ?>">
			<?php esc_html_e('Link to Github Page', 'gitfeed'); ?>
		</a>
	</p>

	<?php $count = 0;
			
	foreach($repos as $key=>$value): ?>	
		<div class="gf-repo">
			<h3 class="gf-repohead">
				<strong><?php echo esc_html($value[0]); ?></strong>: <?php echo esc_html($value[1]); ?>
			</h3>
			<p>
				<span class="gf-green">
					<em><?php esc_html_e('Last updated', 'gitfeed'); ?></em>: <?php echo date("F j, Y, g:i a", $key); ?> 
					<?php esc_html_e('U.S. Central Time', 'gitfeed'); ?>
				</span>
			</p>

			<?php
				$response = $commits[$count]; 
				$response2 = $commit_stats[$count];
			?>

			<p>
				<em><?php esc_html_e('Latest Commit', 'gitfeed'); ?></em>:  
				<?php echo esc_html($response->commit->message); ?>
			</p>
			<p>
				<em><?php esc_html_e('Total Code Changes', 'gitfeed'); ?></em>:  
				<?php echo esc_html($response2->stats->total); ?>
			</p>	
			<p>
				<em><?php esc_html_e('Lines Added', 'gitfeed'); ?></em>:  
				<?php echo esc_html($response2->stats->additions); ?>
			</p>	
			<p>
				<em><?php esc_html_e('Lines Deleted', 'gitfeed'); ?></em>:  
				<?php echo esc_html($response2->stats->deletions); ?>
			</p>	

			<?php foreach($response2->files as $key=>$value): ?>
					<p>
						<em><?php esc_html_e('File Changed', 'gitfeed'); ?></em>:  
						<?php echo esc_html($response2->files[$key]->filename); ?>
					</p>
					<p>
						<em><?php esc_html_e('Patch', 'gitfeed'); ?></em>: 
						<?php echo esc_html($response2->files[$key]->patch); ?>
					</p>
			<?php endforeach; ?>
		</div>

		<?php $count++;
	endforeach; ?>
</div>