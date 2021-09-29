<?php

/**
 * @package Profile Moderator Management
 * @version 3.1
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2021, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

function template_Manage_profileMod()
{
	global $context, $scripturl, $txt;
	
	echo '
	<form method="post" action="', $scripturl, '?action=profile;area=modmanagement;u=', $context['id_member'], ';save" name="moderatormanagement" id="moderatormanagement">
		<div class="cat_bar">
			<h3 class="catbg profile_hd">
				', $txt['modmanagement'], '
			</h3>
		</div>
		<p class="information">', $txt['modmanagement_desc'], '</p>
		<div class="windowbg">';

			// Add the board list
			boards_list();

			// Go and save
			template_profile_save();

	echo '
		</div><!-- .windowbg -->
	</form>
	<br />';
}

function boards_list()
{
	global $context, $txt;

	echo '
							<fieldset id="mod_boards">
								<legend>', $txt['modmanagement_select'], '</legend>
								<ul class="padding floatleft">';

	foreach ($context['categories'] as $category)
	{
			echo '
									<li class="category">
										<a href="javascript:void(0);" onclick="selectBoards([', implode(', ', $category['child_ids']), '], \'moderatormanagement\'); return false;"><strong>', $category['name'], '</strong></a>
										<ul>';

		foreach ($category['boards'] as $board)
		{
				echo '
											<li class="board" style="margin-', $context['right_to_left'] ? 'right' : 'left', ': ', $board['child_level'], 'em;">
												<input type="checkbox" name="modmanagement[', $board['id'], ']" id="brd', $board['id'], '" value="', $board['id'], '"', $board['selected'] ? ' checked' : '', '> <label for="brd', $board['id'], '">', $board['name'], '</label>
											</li>';
		}

		echo '
										</ul>
									</li>';
	}

	echo '
								</ul>
								<br class="clear"><br>
								<input type="checkbox" id="checkall_check" onclick="invertAll(this, this.form, \'modmanagement\');">
								<label for="checkall_check"><em>', $txt['check_all'], '</em></label>
							</fieldset>';
}