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

<div class="container-fluid">
	<h2 class="gf-header">
		<?php esc_html_e('This custom WordPress plugin displays a feed of Git repos, sorted from the most recently updated.', 'gitfeed'); ?>
	</h2>
	<p class="gf-subhead">
		<a target="_blank" class="gf-link" href="https://github.com/<?php echo $user; ?>">
			Link to <?php echo $user ?>&apos;s Github Page
		</a>
	</p>

	<?php $count = 0;
			
	foreach($repos as $key=>$value): ?>	
		<div class="gf-repo">
			<h3 class="gf-repohead">
				<strong><?php echo $value[0]; ?></strong>: <?php echo $value[1]; ?>
			</h3>
			<p>
				<span class="gf-green">
					<em><?php esc_html_e('Last updated', 'gitfeed'); ?></em>: <?php echo date("F j, Y, g:i a", $key); ?> U.S. Central Time
				</span>
			</p>

			<?php
				$response = $commits[$count]; 
				$response2 = $commit_stats[$count];
			?>

			<p>
				<em><?php esc_html_e('Latest Commit', 'gitfeed'); ?></em>: 
				<?php echo $response->commit->message; ?>
			</p>
			<p>
				<em><?php esc_html_e('Total Code Changes: ', 'gitfeed'); ?></em>: 
				<?php echo $response2->stats->total; ?>
			</p>	
			<p>
				<em><?php esc_html_e('Lines Added: ', 'gitfeed'); ?></em>: 
				<?php echo $response2->stats->additions; ?>
			</p>	
			<p>
				<em>Lines Deleted</em>: 
				<?php echo $response2->stats->deletions; ?>
			</p>	

			<?php foreach($response2->files as $key=>$value): ?>
					<p>
						<em>File Changed</em>: 
						<?php echo $response2->files[$key]->filename; ?>
					</p>
					<p>
						<em>Patch</em>: 
						<?php echo esc_html($response2->files[$key]->patch); ?>
					</p>
			<?php endforeach; ?>
		</div>

		<?php $count++;
	endforeach; ?>
</div>