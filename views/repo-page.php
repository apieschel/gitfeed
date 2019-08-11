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
 * @var str $user					 the Github user
 */

?>

<div>
	<h2 class="gf-header">
		<?php echo sprintf(esc_html__('This custom WordPress plugin displays a feed of %s Git repos, sorted from the most recently updated.', 'gitfeed'), $user . '&apos;s'); ?>
	</h2>
	<p class="gf-subhead">
		<a target="_blank" class="gf-link" href="<?php echo esc_url('https://github.com/' . $user); ?>">
			<?php echo sprintf(esc_html__('Link to %s Github Page', 'gitfeed'), $user . '&apos;s'); ?>
		</a>
	</p>

	<?php $count = 0;
			
	foreach($repos as $key=>$value): ?>	
		<div class="gf-repo">
			<h3 class="gf-repohead">
				<strong><?php echo esc_html($value[0]); ?></strong>: <?php echo esc_html($value[1]); ?>
			</h3>
			
			<div class="grid">
				<div>
					<p>
						<span class="gf-green">
							<?php esc_html_e('Last updated', 'gitfeed'); ?>: <?php echo date("F j, Y, g:i a", $key); ?> 
							<?php esc_html_e('U.S. Central Time', 'gitfeed'); ?>
						</span>
					</p>

					<?php
						$response = $commits[$count]; 
						$response2 = $commit_stats[$count];
					?>

					<p>
						<?php esc_html_e('Latest Commit', 'gitfeed'); ?>:  
						<?php echo esc_html($response->commit->message); ?>
					</p>
				</div>

				<div>
					<p>
						<?php esc_html_e('Total Code Changes', 'gitfeed'); ?>:  
						<?php echo esc_html($response2->stats->total); ?>
					</p>	
					<p>
						<?php esc_html_e('Lines Added', 'gitfeed'); ?>:  
						<?php echo esc_html($response2->stats->additions); ?>
					</p>	
					<p>
						<?php esc_html_e('Lines Deleted', 'gitfeed'); ?>:  
						<?php echo esc_html($response2->stats->deletions); ?>
					</p>
				</div>
			</div>	

			<?php foreach($response2->files as $key=>$value): ?>
					<p>
						<?php esc_html_e('File', 'gitfeed'); ?>:  
						<?php echo esc_html($response2->files[$key]->filename); ?>
					</p>
					
					<div class="scroll">
						<table class="tab-size" data-tab-size="4">
							<tbody>
								<tr>		  
									<td><pre class="pretty"><?php echo esc_html($response2->files[$key]->patch); ?></pre></td>
								</tr>
							</tbody>
						</table>
					</div>
			<?php endforeach; ?>
		</div>

		<?php $count++;
	endforeach; ?>
</div>