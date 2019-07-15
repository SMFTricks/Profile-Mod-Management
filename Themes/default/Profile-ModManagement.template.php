<?php

/**
 * Profile-ModManagement.template.php
 *
 * @package Profile Moderator Management
 * @version 3.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2019, SMF Tricks
 * @license http://www.mozilla.org/MPL/ MPL 2.0
 */

// The template for Moderator Board Managment.
function template_Manage_profileMod()
{
	global $context, $scripturl, $txt;
	
	echo '
	<form method="post" action="', $scripturl, '?action=profile;area=modmanagement;u=', $context['id_member'], ';save" name="creator" id="creator" accept-charset="', $context['character_set'], '">
		<div class="cat_bar">
			<h3 class="catbg profile_hd">
				', $txt['modmanagement'], '
			</h3>
		</div>
		<p class="information">', $txt['modmanagement_desc'], '</p>
		<div class="windowbg">
			<div class="flow_hidden boardslist">
				<ul>';

	foreach ($context['categories'] as $category)
    {
        echo '
					<li>
						<a href="javascript:void(0);" onclick="selectBoards([', implode(', ', $category['child_ids']), '], \'creator\'); return false;">', $category['name'], '</a>
            			<ul>';

        foreach ($category['boards'] as $board)
        {
            echo '
							<li style="margin-', $context['right_to_left'] ? 'right' : 'left', ': ', $board['child_level'], 'em;">
								<label for="mod_brd', $board['id'], '"><input type="checkbox" id="brd', $board['id'], '" name="mod_brd[', $board['id'], ']" value="', $board['id'], '"', !empty($board['selected']) ? ' checked' : '', '> ', $board['name'], '</label>
							</li>';
        }

        echo '
						</ul>
					</li>';
    }

    echo '
				</ul>
    		</div>
			<input type="checkbox" name="all" id="check_all" value="" onclick="invertAll(this, this.form, \'mod_brd\');" class="check" /><i> <label for="check_all">', $txt['check_all'], '</label></i><br />';

	// Go and save
	template_profile_save();

	echo '
		</div><!-- .windowbg -->
	</form>
	<br />';
}