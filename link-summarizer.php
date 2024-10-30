<?php
/*
Plugin Name: Link Summarizer
Plugin URI: http://mac.partofus.org/macpress/?p=40
Description: Create a link summarizer at the bottom of every post
Version: 1.8.1
Author: Christoph Erdle
Author URI: http://mac.partofus.org
*/

/*
        Copyright 2007-2009 Christoph Erdle (email: chris@team-erdle.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

$lnsum_version = '1.8.1';

function lnsum_debug($message) {
        if ( $fh = fopen("/tmp/lnsum-debug", "a+") ) {
                fwrite($fh, date('d.m.Y H:i:s').": ".$message."\n");
                fclose($fh);
        }
}

function command_replace($string, $command, $replacement) {
	return str_replace($command, $replacement, $string);
}

function truncate_linktext($truncatelink, $length) {
	/* if no length is given or length is set to 0 return the original link */
	if (($length==0) || ($length=="")) {
		return $truncatelink;
	}
	if (strlen($truncatelink) <= $length) {
		return $truncatelink;

	} else {
		if ( ($length % 2) == 1) {
			$lenght = $length + 1;
		}
		$head = substr($truncatelink, 0, ($length/2)-1);
		$tail = substr($truncatelink, strlen($truncatelink)-($length/2)+2);
		return $head."...".$tail;
	}
}

function get_link_summary() {
	global $wpdb, $id;
        $optionarray = get_option('plugin_linksummarizer');
	
	// load post content into variable
	$currpost = get_post($id, ARRAY_A);
	$content = $currpost['post_content'];
	
	// remove the link summarizer filter funktion "link_summary" from
	// the_content to get a pure output of the other plugins.
	// This is especially important if using plugins like Markdown which
	// generate HTML output from simplified input.
	remove_filter('the_content','link_summary');
	$link_summary = generate_summary(apply_filters('the_content', $content));
	add_filter('the_content','link_summary', get_filter_priority());

	// loop specific commands
	$summary_array = explode("<separatelinks>", $link_summary);
    	if ( $link_summary != '' ) {
		$outputformat= stripslashes($optionarray['lnsum_codetemplate']);
		// split output format string in parts
		$whileloop_startpos = strpos($outputformat, '%LINKLOOP_START%');
		$whileloop_endpos = strpos($outputformat, '%LINKLOOP_END%', $whileloop_startpos);
		$beforeloop = substr($outputformat, 0, $whileloop_startpos);
		$afterloop  = substr($outputformat, $whileloop_endpos + strlen('%LINKLOOP_END%'));
		$linkloop = substr($outputformat, $whileloop_startpos+strlen('%LINKLOOP_START%'), $whileloop_endpos - $whileloop_startpos - strlen('%LINKLOOP_START%'));
		echo '<div class="link-summarizer-loop">';
		if ( ( $linkloop === false ) || ( $whileloop_startpos === false ) || ( $whileloop_endpos === false ) ) {
			echo '<p style="color: #FF0000">Error in Link Summarizer Configuration!</p>';
			echo "</div>";
		} else {
			echo command_replace($beforeloop, '%SUMMARY_TITLE%', $optionarray['lnsum_headtext']);
			for ( $i=0; $i< count($summary_array); $i++ ) {
				echo command_replace($linkloop, '%CURRENT_LINK%', $summary_array[$i]);
			}
			echo command_replace($afterloop, '%SUMMARY_TITLE%', $optionarray['lnsum_headtext']);
			echo "</div>";
		}
	}
}

function link_summary($content) {
	global $wpdb, $id;		
        $optionarray = get_option('plugin_linksummarizer');
	if ($optionarray['lnsum_onlyloopshow']==1) return $content;
	$link_summary = generate_summary($content);
	// filter specific commands
	$summary_array = explode("<separatelinks>", $link_summary);
    	if ( $link_summary != '' ) {
		$outputformat= stripslashes($optionarray['lnsum_codeautomatic']);
		// split output format string in parts
		$whileloop_startpos = strpos($outputformat, '%LINKLOOP_START%');
		$whileloop_endpos = strpos($outputformat, '%LINKLOOP_END%', $whileloop_startpos);
		$beforeloop = substr($outputformat, 0, $whileloop_startpos);
		$afterloop  = substr($outputformat, $whileloop_endpos + strlen('%LINKLOOP_END%'));
		$linkloop = substr($outputformat, $whileloop_startpos+strlen('%LINKLOOP_START%'), $whileloop_endpos - $whileloop_startpos - strlen('%LINKLOOP_START%'));
		if ($optionarray['lnsum_position']==0) {
			// show summary above the content
			$local_content = '';
		}
		else {
			$local_content = $content;
		}
		$local_content .= '<div class="link-summarizer">';

		if ( ( $linkloop === false ) || ( $whileloop_startpos === false ) || ( $whileloop_endpos === false ) ) {
			$local_content .='<p style="color: #FF0000">Error in Link Summarizer Configuration!</p>';
			$local_content .="</div>";
		} else {
			$local_content .= command_replace($beforeloop, '%SUMMARY_TITLE%', $optionarray['lnsum_headtext']);
			for ( $i=0; $i< count($summary_array); $i++ ) {
				$local_content .= command_replace($linkloop, '%CURRENT_LINK%', $summary_array[$i]);
			}
			$local_content .= command_replace($afterloop, '%SUMMARY_TITLE%', $optionarray['lnsum_headtext']);
			$local_content .= "</div>";
		}
		if ($optionarray['lnsum_position']==0) {
			$local_content .= $content;
		}
		$content = $local_content;
	}
	return $local_content;
}

function check_index_rss_show($default_show, $post_id) {
	if ( $default_show == 0 ) {
		# index show disabled
		return FALSE;
	} else if ( $default_show == 1 ) {
		# index show enabled
		return TRUE;
	} else if ( $default_show == 2 ) {
		# index show enabled, but override to hide on per post basis
		$lnsum_show_custom = get_post_custom_values(lnsum_show, $post_id);
		if  ( count($lnsum_show_custom) > 0 ) {
			if ( $lnsum_show_custom[0] == '0' ) {
				return FALSE;
			}
		}
		return TRUE;
	} else if ( $default_show == 3 ) {
		# index show disabled, but override to show on per post basis
		$lnsum_show_custom = get_post_custom_values(lnsum_show, $post_id);
		if ( count($lnsum_show_custom) > 0 ) {
			if ( $lnsum_show_custom[0] == '1' ) {
				return TRUE;
			}
		}
		return FALSE;
	}
	# default setting, only will be used if there's an error in the config, otherwise the entries above will match at any given time
	return FALSE;
}


function generate_summary($content) {
	global $wpdb, $id;
        
	$siteurl = explode("/", get_option('siteurl'));
        $called_uri = $siteurl[0]."//".$siteurl[2].$_SERVER['REQUEST_URI'];
        $site_id = url_to_postid($called_uri);

        $optionarray = get_option('plugin_linksummarizer');
        $lnsum_show = $optionarray['lnsum_defaultshow'];

        // sum up if the summary is to be shown depending on the global settings, the page displayed (index page) and
        // post specific settings...

        // check if the called site is the index page and lnsum_indexshow is set to enabled
	if ( $site_id == 0 ) {
		// get post individual settings, could override global settings
		$lnsum_show = check_index_rss_show($optionarray['lnsum_indexshow'], $id);
        } else {
                // only call the select statement if clear that the called site is not the index page
                if ( $site_id != '0' ) {
                        $lnsum_show_custom = get_post_custom_values(lnsum_show);
                        if ( count($lnsum_show_custom) > 0 ) {
                                if ( $lnsum_show_custom[0] == '0' ) {
                                        $lnsum_show = FALSE;
                                } else {
                                        $lnsum_show = TRUE;
                                }
                        }
                }
        }

        if ( is_feed() ) {
		// get post individual settings, could override global settings
		$lnsum_show = check_index_rss_show($optionarray['lnsum_rssshow'], $id);
        }

        // generate list of links to be displayed
        if ( $lnsum_show == TRUE ) {
		$linkssummary = '';
                $linkarray = array();
                $output_counter = 0;
                $regexarray = explode('lnseparator', $optionarray['lnsum_omitregex']);
                preg_match_all('/<a(.+?)href="(.+?)"(.*?)>(.+?)<\/a>/', $content, $matches);
                for ( $i=0; $i < count($matches[0]); $i++ ) {
                        $link_match = $matches[0][$i];
                        $link_number++;
                        $link_url = $matches[2][$i];
			if ($optionarray['lnsum_titleshow']==1) {
				if (preg_match_all('/.*title="(.+?)".*/', $matches[3][$i], $titlematch)>0) {
					$link_title=$titlematch[1][0];
				} else {
					if (preg_match_all('/.*title="(.+?)".*/', $matches[1][$i],$titlematch)>0) {
						$link_title=$titlematch[1][0];
					} else {
						$link_title="";
					}
				}
			} else {
				$link_title="";
			}
                        $output=true;
                        $link_tmpurl=str_replace('&amp;','&',$link_url);
                        foreach ( $regexarray as $currentregex ) {
                                if ( $currentregex === '' ) {
                                } else {
                                        if ( $optionarray['lnsum_regperl'] != 0 ) {
                                                if ( $optionarray['lnsum_regcase'] == 0 ) { 
                                                        if ( preg_match("/".$currentregex."/",$link_tmpurl) ) {
                                                                $output=false;
                                                        }
                                                } else {
                                                        if ( preg_match("/".$currentregex."/i",$link_tmpurl) ) {
                                                                $output=false;
                                                        }
                                                }
                                        } else {
                                                if ( $optionarray['lnsum_regcase'] == 0 ) {
                                                        if ( ereg($currentregex,$link_tmpurl) ) {
                                                                $output=false;
                                                        }
                                                } else {
                                                        if ( eregi($currentregex,$link_tmpurl) ) {
                                                                $output=false;
                                                        }
                                                }
                                        }
                                }
                        }
                        if ( $output === true ) {
                                if ( !array_key_exists($link_url, $linkarray) ) {
                                        $linkarray = array_merge($linkarray, array($link_url => 1));
                                        $output_counter++;
                                        if ( $output_counter > 1 ) {
                                        	$link_summary .="<separatelinks>";
                                        }
                                        if ( $optionarray['lnsum_urlshow'] != 0 ) {
						if ($link_title=="") {
                                                	$link_summary .= "<a".$matches[1][$i]."href='".$matches[2][$i]."'".$matches[3][$i].">".truncate_linktext($matches[2][$i], $optionarray['lnsum_linktruncate'])."</a>";
                                        	} else {
                                                	$link_summary .= "<a".$matches[1][$i]."href='".$matches[2][$i]."'".$matches[3][$i].">".truncate_linktext($link_title, $optionarray['lnsum_linktruncate'])."</a>";
						}
						
					} else {
						if ($link_title=="") {
                                                	$link_summary .= "<a".$matches[1][$i]."href='".$matches[2][$i]."'".$matches[3][$i].">".truncate_linktext($matches[4][$i], $optionarray['lnsum_linktruncate'])."</a>";
                                        	} else {
                                                	$link_summary .= "<a".$matches[1][$i]."href='".$matches[2][$i]."'".$matches[3][$i].">".truncate_linktext($link_title, $optionarray['lnsum_linktruncate'])."</a>";
						}
					}
                                } else {
                                        $linkarray[$link_url] = $linkarray[$link_url]+1;
                                }
                        }
                }
                return $link_summary;
        }
        return "";
}

add_filter('the_content', 'link_summary', get_filter_priority());

// Add admin menu
function lnsum_add_options_to_admin() {
    if ( function_exists('add_options_page') ) {
                add_options_page('Link Summarizer', 'Link Summarizer', 8, basename(__FILE__), 'link_summarizer_options_subpanel');
    }
}

function lnsum_txtLineBreaktoSemiColon($input) {
        $input = preg_replace("/\r\n|\r|\n/s",'lnseparator',$input);
        $input = str_replace('\\\\','\\',$input);
        return $input;
}

function lnsum_txtSemiColonToLineBreak($input) {
        $input = str_replace('lnseparator',"\n", $input);
        return $input;
}

function get_filter_priority() {
	$optionarray = get_option('plugin_linksummarizer');
	$filter_priority = $optionarray['lnsum_filterpriority'];
	if (is_numeric($filter_priority)) { return $filter_priority; } else { return 20; }
}

function link_summarizer_options_subpanel() {
	global $lnsum_version;
        if ( ($_POST['action']=='delete_step1') ) {
		check_admin_referer('lnsum-delete-options')
                ?>
                <div class="wrap">
                        <form method="post" action="<?php echo $_SERVER['PHP_SELF'].'?page='.basename(__FILE__); ?>">
				<? wp_nonce_field('lnsum-delete-options2'); ?>
                                <h2><?php _e('Do you really want to delete the plugin settings?', 'link-summarizer'); ?></h2>
				<legend>
					<?php _e('All your settings will be deleted. You have to deactivate and reactivate the plugin to get it working again.', 'link-summarizer'); ?>
				</legend> 
                                <input type="hidden" name="action" value="delete-step2">
                                <div class="submit">
                                        <a href="<?php echo $SERVER_['PHP_SELF'].'?page='.basename(__FILE__); ?>">
					<?php _e('NO, leave these settings alone!', 'link-summarizer'); ?></a>&nbsp;&nbsp;
                                        <input type="submit" name="delete" value="<?php _e('Yes, delete all that crap!', 'link-summarizer'); ?>">
                                </div>
                        </form>
                </div>
                <?php
        } elseif ($_POST['action'] == 'delete_step2') {
		check_admin_referer('lnsum_delete_options2');
		delete_option('plugin_linksummarizer');
                        ?>
                        <div class="wrap">
				<?php _e('Options for Link Summarizer were deleted. To reset the options to the default deactivate and reactivate the plugin from the plugin panel. As an alternative you can also update the settings to new settings from below.', 'link-summarizer'); ?>
			</div>
                        <?php
	} elseif ($_POST['action'] == 'lnsum_update' ) {
				check_admin_referer('lnsum_update-options');
				if (isset($_POST['lnsum_titleshow'])) {
					$lnsum_titleshow = 1;
				} else {
					$lnsum_titleshow = 0;
				}
                                $optionarray_update = array (
                                        'lnsum_headtext' => $_POST['lnsum_headtext'],
					'lnsum_codeautomatic' => $_POST['lnsum_codeautomatic'],
					'lnsum_codetemplate' => $_POST['lnsum_codetemplate'],
                                        'lnsum_defaultshow' => $_POST['lnsum_defaultshow'],
                                        'lnsum_omitregex' => lnsum_txtLineBreakToSemiColon($_POST['lnsum_omitregex']),
                                        'lnsum_indexshow' => $_POST['lnsum_indexshow'],
                                        'lnsum_regperl' => $_POST['lnsum_regperl'],
                                        'lnsum_urlshow' => $_POST['lnsum_urlshow'],
                                        'lnsum_regcase' => $_POST['lnsum_regcase'],
                                        'lnsum_rssshow' => $_POST['lnsum_rssshow'],
                                        'lnsum_onlyloopshow' => $_POST['lnsum_onlyloopshow'],
					'lnsum_linktruncate' => $_POST['lnsum_linktruncate'],
					'lnsum_titleshow' => $lnsum_titleshow,
					'lnsum_filterpriority' => $_POST['lnsum_filterpriority'],
					'lnsum_position' => $_POST['lnsum_position']
                                );
                                // if link summarizer's options don't exist, e.g. after the settings were deleted and updated 
                                // afterwords, we must use add_option
                                add_option('plugin_linksummarizer', $optionarray_update, 'Link Summarizer Plugin Options');
                                update_option('plugin_linksummarizer', $optionarray_update); 
	}

	if ( ($_POST['action'] != 'delete_step1') && ($_POST['action'] != 'delete_step2') ) {
                        $optionarray_def = get_option('plugin_linksummarizer');
			if (!isset($optionarray_def['lnsum_filterpriority'])) {
				$optionarray_def['lnsum_filterpriority'] = 20;
			}
?>
<div class=wrap>
        <h2><?php _e('Link Summarizer Options', 'link-summarizer'); ?></h2>
		<div align="right">
			<?php printf(__("You are running version <b>%s</b> of the plugin.", 'link-summarizer'), $lnsum_version); ?>
		</div>
		<form name="lnsum-options" method="post" action="">
		<?php if (! function_exists(settings_fields)) {
			wp_nonce_field('lnsum_update-options');
			} else { settings_fields('lnsum_update'); } ?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
							<?php _e('Default setting for showing summaries', 'link-summarizer'); ?>
						</th>
        				<td>
							<p>
								<label for="radio_show1">
									<input id="radio_show1" type="radio" name="lnsum_defaultshow" value="1" <?php echo ($optionarray_def['lnsum_defaultshow']!=0 ?'checked="checked"':'') ?> />
        								<?php _e('Show summaries', 'link-summarizer'); ?>
								</label>
        							<br />
        							<label for="radio_show2">
									<input id="radio_show2" type="radio" name="lnsum_defaultshow" value="0" <?php echo ($optionarray_def['lnsum_defaultshow']==0 ?'checked="checked"':'') ?> />
        								<?php _e('Hide summaries', 'link-summarizer'); ?>
								</label>
        						</p>
							<p>
								<?php _e('This setting can be overriden on a per post basis using a custom field named "lnsum_show" (without parantheses):', 'link-summarizer'); ?>
								<ul>
									<li>
										<?php _e("To disable it by post, if the default is enabled, use a value of '0' in the custom field.", 'link-summarizer'); ?>
									</li>
									<li>
										<?php _e("To enable it by post, if the default is disabled, use a value of '1' in the custom field.", 'link-summarizer'); ?>
									</li>
								</ul>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('In which way do you want the summary to appear?', 'link-summarizer'); ?>
						</th>
						<td>
							<p>
								<label for="radio_autoshow1">
        								<input id="radio_autoshow1" type="radio" name="lnsum_onlyloopshow" value="1" <?php echo ($optionarray_def['lnsum_onlyloopshow']!=0 ?'checked="checked"':'') ?> />
        								<?php _e("Don't show summaries automatically at the end of the post. <b>You have to include the call to the function 'get_link_summary()' manually in your template files.</b>", 'link-summarizer'); ?>
								</label>
        						<br />
        						<label for="radio_autoshow2">
									<input id="radio_autoshow2" type="radio" name="lnsum_onlyloopshow" value="0" <?php echo ($optionarray_def['lnsum_onlyloopshow']==0 ?'checked="checked"':'') ?> />
									<?php _e("Show summaries automatically at the end of the post. You can additionally include the call to the function 'get_link_summary()' in your template files to fulfill special design needs.", 'link-summarizer'); ?>
								</label>
        					</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Where do you want the summary to appear?', 'link-summarizer'); ?>
						</th>
						<td>
							<p
								<label for="radio_position1">
									<input id="radio_position1" type="radio" name="lnsum_position" value="1" <?php echo ($optionarray_def['lnsum_position']!=0 ?'checked="checked"':'') ?> />
									<?php _e('Show summary below the post', 'link-summarizer'); ?>
								</label>
								<br/>
								<label for="radio_position2">
									<input id="radio_position2" type="radio" name="lnsum_position" value="0" <?php echo ($optionarray_def['lnsum_position']==0 ?'checked="checked"':'') ?> />
									<?php _e('Show summary above the post', 'link-summarizer'); ?>
								</label>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Link Summary Heading Text', 'link-summarizer'); ?></th>
						<td>
        					<input name="lnsum_headtext" type="text" id="lnsum_headtext" value="<?php echo $optionarray_def['lnsum_headtext']; ?>" size="50"/>
						</td>
					</tr>
					<tr valign="top">
						<th>
							<?php _e('Code to be used to display the link summary', 'link-summarizer'); ?>
						</th>	
						<td>
							<?php _e('The following variables and commands can be used:', 'link-summarizer'); ?>
							<ul>
								<li><b>%SUMMARY_TITLE%</b>: <?php _e('show the set heading for the summary', 'link-summarizer'); ?></li>
								<li><b>%LINKLOOP_START%</b>: <?php _e('all code until the matching end tag will be used for every link', 'link-summarizer'); ?></li>
								<li><b>%LINKLOOP_END%</b>: <?php _e('ends the loop for displaying the links', 'link-summarizer'); ?></li>
								<li><b>%CURRENT_LINK%</b>: <?php _e('inserts the formatted link', 'link-summarizer'); ?></li>
							</ul>
							<?php _e('When shown automatically:'); ?><br/>
							<textarea name="lnsum_codeautomatic" id="lnsum_codeautomatic" cols="100%" rows="8"><?php echo stripslashes($optionarray_def['lnsum_codeautomatic']); ?></textarea>
							<br/>
							<?php _e("When shown via the template function 'get_link_summary()':", 'link-summarizer'); ?><br/>
							<textarea name="lnsum_codetemplate" id="lnsum_codetemplate" cols="100%" rows="8"><?php echo stripslashes($optionarray_def['lnsum_codetemplate']); ?></textarea>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Show summaries on index page', 'link-summarizer'); ?></th>
						<td>
							<p>
								<label for="radio_index1">
        								<input id="radio_index1" type="radio" name="lnsum_indexshow" value="1" <?php echo ($optionarray_def['lnsum_indexshow']==1 ?'checked="checked"':'') ?> />
        								<?php _e('Show summaries on index page', 'link-summarizer'); ?>
								</label>
        							<br />
        							<label for="radio_index2">
									<input id="radio_index2" type="radio" name="lnsum_indexshow" value="0" <?php echo ($optionarray_def['lnsum_indexshow']==0 ?'checked="checked"':'') ?> />
        								<?php _e('Hide summaries on index page', 'link-summarizer'); ?>
								</label>
								<br />
								<label for="radio_index3">
									<input id="radio_index3" type="radio" name="lnsum_indexshow" value="2" <?php echo ($optionarray_def['lnsum_indexshow']==2 ?'checked="checked"':'') ?> />
									<?php _e("Show summaries on index page per default, but allow override by setting the custom field 'lnsum_show' to '0' to disable display on a per post basis", 'link-summarizer'); ?>
								</label>
								<br />
								<label for="radio_index4">
									<input id="radio_index4" type="radio" name="lnsum_indexshow" value="3" <?php echo ($optionarray_def['lnsum_indexshow']==3 ?'checked="checked"':'') ?> />
									<?php _e("Hide summaries on index page per default, but allow override by setting the custom field 'lnsum_show' to '1' to enable display on a per post basis", 'link-summarizer'); ?>
								</label>
							<p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Show summaries in rss feeds', 'link-summarizer'); ?>
						</th>
						<td>
							<p>
								<label for="radio_rss1">
        								<input id="radio_rss1" type="radio" name="lnsum_rssshow" value="1" <?php echo ($optionarray_def['lnsum_rssshow']==1 ?'checked="checked"':'') ?> />
	        							<?php _e('Show summaries in feed', 'link-summarizer'); ?>
								</label>
        							<br />
        							<label for="radio_rss2">
									<input id="radio_rss2" type="radio" name="lnsum_rssshow" value="0" <?php echo ($optionarray_def['lnsum_rssshow']==0 ?'checked="checked"':'') ?> />
        								<?php _e('Hide summaries in feed', 'link-summarizer'); ?>
								</label>
								<br />
								<label for="radio_rss1">
	        							<input id="radio_rss1" type="radio" name="lnsum_rssshow" value="2" <?php echo ($optionarray_def['lnsum_rssshow']==2 ?'checked="checked"':'') ?> />
        								<?php _e("Show summaries in feed, but allow override by setting the custom field 'lnsum_show' to '0' to disable display on a per post basis", 'link-summarizer'); ?>
								</label>
        							<br />
        							<label for="radio_rss2">
									<input id="radio_rss2" type="radio" name="lnsum_rssshow" value="3" <?php echo ($optionarray_def['lnsum_rssshow']==3 ?'checked="checked"':'') ?> />
									<?php _e("Hide summaries in feed, but allow override by setting then custom field 'lnsum_show' to '1' to enable display on a per post basis", 'link-summarizer'); ?>
								</label>

							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Show link text or URL in summary', 'link-summarizer'); ?>
						</th>
						<td>
							<p>
								<label for="radio_link1">
        								<input id="radio_link1" type="radio" name="lnsum_urlshow" value="1" <?php echo ($optionarray_def['lnsum_urlshow']!=0 ?'checked="checked"':'') ?> />
        								<?php _e('Show URL in summary', 'link-summarizer'); ?>
								</label>
        							<br />
        							<label for="radio_link2">
									<input id="radio_link2" type="radio" name="lnsum_urlshow" value="0" <?php echo ($optionarray_def['lnsum_urlshow']==0 ?'checked="checked"':'') ?> />
        								<?php _e('Show link text in summary', 'link-summarizer'); ?>
								</label>
								<br />
								<label for="checkbox_title">
									<input id="checkbox_title" type="checkbox" name="lnsum_titleshow[]" value="show" <?php echo ($optionarray_def['lnsum_titleshow']==1 ?'checked="checked"':'') ?> />
									<?php _e('Prefer contents of title attribute if existing', 'link-summarizer'); ?>
								</label>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Truncate links longer than (0 never truncates):', 'link-summarizer'); ?>
						</th>
						<td>
	        				<input name="lnsum_linktruncate" type="text" id="lnsum_linktruncate" value="<?php echo $optionarray_def['lnsum_linktruncate']; ?>" size="4"/>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e('Regular expressions you want to use: see', 'link-summarizer'); ?> <a href="http://www.regular-expressions.info" target="_blank">www.regular-expressions.info</a></th>
        				<td>
							<p>
								<label for="radio_regexengine1">
									<input id="radio_regexengine1" type="radio" name="lnsum_regperl" value="1" <?php echo ($optionarray_def['lnsum_regperl']!=0 ?'checked="checked"':'') ?> />
        								<?php _e('Perl compatible syntax, faster (no starting and ending "/" needed, remember to escape "/" when you use it, e.g. in URLs), see', 'link-summarizer'); ?> <a href="http://www.regular-expressions.info/pcre.html" target="_blank">http://www.regular-expressions.info/pcre.html</a>
								</label>
							<br />
        							<label for="radio_regexengine2">
									<input id="radioa2" type="radio" name="lnsum_regperl" value="0" <?php echo ($optionarray_def['lnsum_regperl']==0 ?'checked="checked"':'') ?> />
        								<?php _e('simpler syntax, not as powerful as the perl regular expressions, slower, see', 'link-summarizer'); ?> <a href="http://www.regular-expressions.info/posix.html" target="_blank">http://www.regular-expressions.info/posix.html</a>
								</label>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('RegEx to describe links not to be displayed (seperate multiple regex with line breaks)', 'link-summarizer'); ?>
						</th>
						<td>
        					<textarea name="lnsum_omitregex" id="lnsum_omitregex" cols="60" rows="8" style="width:98%; fonst-size: 12 px"><?php echo lnsum_txtSemiColonToLineBreak($optionarray_def['lnsum_omitregex']); ?></textarea>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Do you want to use case sensitive or case insensitive matching for the above regular expressions?', 'link-summarizer'); ?>
						</th>
						<td>
							<p>
	 							<label for="radio_regexcase1">
        								<input id="radio_regexcase1" type="radio" name="lnsum_regcase" value="1" <?php echo ($optionarray_def['lnsum_regcase']!=0 ?'checked="checked"':'') ?> />
        								<?php _e('case insensitive matching', 'link-summarizer'); ?>
								</label>
        						<br />
								<label for="radio_regexcase2">
	        							<input id="radio_regexcase2" type="radio" name="lnsum_regcase" value="0" <?php echo ($optionarray_def['lnsum_regcase']==0 ?'checked="checked"':'') ?> />
        								<?php _e('case sensitive matching', 'link-summarizer'); ?>
								</label>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Filter priority (default = 20)', 'link-summarizer'); ?>
						</th>
						<td>
							<input name="lnsum_filterpriority" type="text" id="lnsum_filterpriority" value="<?php echo $optionarray_def['lnsum_filterpriority']; ?>" size="3"><br/>
							<label for="lnsum_filterpriority"><?php _e("This setting is important if you use plugins like Twitter Tools or Sociable and don't want to exclude the links generated by regex (here you must set the priority to something &lt; 10), or MarkDown (in that case you must set something &gt; 10).", 'link-summarizer'); ?></label>
						</td>
					</tr>
							
				</tbody>
			</table>
        	<div class="submit" >
		<input type="hidden" value="lnsum_update" name="action" />
                <input type="submit" name="submit" class="button-primary" value="<?php _e('Update Options', 'link-summarizer'); ?> &raquo;" />
        	</div>
        </form>
</div>
<div class="wrap">
	<h2>
		<?php _e('Donate', 'link-summarizer'); ?>
	</h2>
	<p>
		<?php _e('If you like this plugin consider donating a small amount to the author using PayPal to support further plugin development.', 'link-summarizer'); ?>
	</p>
	<div align="center">
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
	<input type="hidden" name="cmd" value="_s-xclick"/>
	<input border="none" align="center" type="image" src="https://www.paypal.com/de_DE/DE/i/btn/btn_donate_LG.gif" name="submit" alt="Zahlen Sie mit PayPal - schnell, kostenlos und sicher!"/>
	<!--img alt="" border="0" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1"/-->
	<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----"/>
	</form>
	</div>
	<p>
		<?php _e('If you think donating money is somehow impersonal you could also choose items from my', 'link-summarizer'); ?> <a href="http://www.amazon.de/gp/registry/wishlist/SSZ4A3W1G6M9/" target="_blank"><?php _e('Amazon.de Whislist', 'link-summarizer'); ?></a>.
</div>
<div class="wrap">
        <form name="deletesettings" method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?page=' . basename(__FILE__); ?>">
		<?php wp_nonce_field('lnsum-delete-options'); ?>
                <h2><?php _e('Delete settings from database', 'link-summarizer'); ?></h2>
		<input type="hidden" name="action" value="delete_step1" />
                <div class="submit"><input type="submit" class="button-primary" name="delete" value="<?php _e('delete settings', 'link-summarizer');?>" /></div>
        </form>
</div>
<?php
        }
}

add_action('admin_menu','lnsum_add_options_to_admin');
add_action('admin_init','lnsum_admin_init');

function lnsum_admin_init() {
	register_setting( 'lnsum', 'lnsum_headtext');
	register_setting( 'lnsum', 'lnsum_codeautomatic');
	register_setting( 'lnsum', 'lnsum_codetemplate');
	register_setting( 'lnsum', 'lnsum_defaultshow', 'intval');
	register_setting( 'lnsum', 'lnsum_omitregex');
	register_setting( 'lnsum', 'lnsum_indexshow', 'intval');
	register_setting( 'lnsum', 'lnsum_regperl', 'intval');
	register_setting( 'lnsum', 'lnsum_urlshow', 'intval');
	register_setting( 'lnsum', 'lnsum_regcase', 'intval');
	register_setting( 'lnsum', 'lnsum_rssshow', 'intval');
	register_setting( 'lnsum', 'lnsum_onlyloopshow', 'intval');
	register_setting( 'lnsum', 'lnsum_linktruncate', 'intval');
	register_setting( 'lnsum', 'lnsum_titleshow', 'intval');
	register_setting( 'lnsum', 'lnsum_filterpriority', 'intval');
	register_setting( 'lnsum', 'lnsum_position', 'intval');
}

function lnsum_install() {
        /* Add default option values if not defined
           default regex is ^#.*, this means that all local anchors are not shown */
        $optionarray_defaults = array(
                'lnsum_headtext' => 'Link Summary',
		'lnsum_codeautomatic' => '<h3>%SUMMARY_TITLE%</h3><ul>%LINKLOOP_START%<li>%CURRENT_LINK%</li>%LINKLOOP_END%</ul>',
		'lnsum_codetemplate' => '<h3>%SUMMARY_TITLE%</h3><ul>%LINKLOOP_START%<li>%CURRENT_LINK%</li>%LINKLOOP_END%</ul>',
                'lnsum_defaultshow' => "1",
                'lnsum_omitregex' => '^#.*',
                'lnsum_indexshow' => "1",
                'lnsum_regperl' => "0",
                'lnsum_urlshow' => "1",
                'lnsum_regcase' => "0",
                'lnsum_rssshow' => "0",
                'lnsum_onlyloopshow' => "0",
		'lnsum_linktruncate' => "45",
		'lnsum_titleshow' => 0,
		'lnsum_filterpriority' => 20,
		'lnsum_position' => 1
        );
        add_option('plugin_linksummarizer', $optionarray_defaults, 'Link Summarizer Plugin Options');
	// Upgrade from previous versions of the plugin
	// create settings not set in version that was installed originally
	$current_options = get_option('plugin_linksummarizer');
	$execute_update=false;
	if (!isset($current_options['lnsum_codeautomatic'])) {
		$current_options['lnsum_codeautomatic'] = '<h3>%SUMMARY_TITLE%</h3><ul>%LINKLOOP_START%<li>%CURRENT_LINK%</li>%LINKLOOP_END%</ul>';
		$execute_update=true;
	}

	if (!isset($current_options['lnsum_codetemplate'])) {
		$current_options['lnsum_codetemplate'] = '<h3>%SUMMARY_TITLE%</h3><ul>%LINKLOOP_START%<li>%CURRENT_LINK%</li>%LINKLOOP_END%</ul>';
		$execute_update=true;
	}
	if (!isset($current_options['lnsum_linktruncate'])) {
		$current_options['lnsum_linktruncate'] = "45";
		$execute_update=true;
	}
	if (!isset($current_options['lnsum_titleset'])) {
		$current_options['lnsum_titleshow'] = "1";
		$execute_update=true;
	}
	if (!isset($current_options['lnsum_filterpriority'])) {
		$current_options['lnsum_filterpriority'] = "20";
		$execute_update=true;
	}
	if (!isset($current_options['lnsum_position'])) {
		$current_options['lnsum_position'] = "1";
		$execute_update=true;
	}
	update_option('plugin_linksummarizer', $current_options);
}

// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
/*
if ( function_exists('add_action') ) {
        $plugin_dir = dirname(__FILE__);
        $plugin_name = basename(__FILE__);
        $plugin_path_pos = strpos($plugin_dir, "plugins");
        $plugin_path = substr($plugin_dir, $plugin_path_pos + 8);

        if ( $plugin_path == FALSE )
                $plugin_path = "";
        else if ( $plugin_path != "" )
                $plugin_path .= "/";

       // add_action('activate_'.$plugin_path.$plugin_name,'lnsum_install');
	add_action('activate_'.WP_PLUGIN_DIR.'/'.basename(__FILE__), 'lnsum_instal');
}
*/
register_activation_hook(__FILE__, 'lnsum_install');

$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain('link-summarizer', false, $plugin_dir.'/languages/')

?>
