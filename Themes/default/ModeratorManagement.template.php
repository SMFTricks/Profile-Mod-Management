<?php

/**
 * ModeratorManagement.template.php
 *
 * @package Profile Moderator Management
 * @version 3.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2014, Diego Andrés
 * @license http://www.mozilla.org/MPL/MPL-1.1.html
 */

// The template for Moderator Board Managment.
function template_main()
{
	global $context, $scripturl, $txt;
	
	//Some Javascript things ;)
	echo '
	<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
		function selectBoards(ids)
		{
			var toggle = true;

			for (i = 0; i < ids.length; i++)
				toggle = toggle & document.forms.creator["mboard" + ids[i]].checked;

			for (i = 0; i < ids.length; i++)
				document.forms.creator["mboard" + ids[i]].checked = !toggle;
		}
	// ]]></script>
		<form method="post" action="', (!empty($context['profile_custom_submit_url']) ? $context['profile_custom_submit_url'] : $scripturl . '?action=profile;area=' . $context['menu_item_selected'] . ';u=' . $context['id_member']), ';save" name="creator" id="creator" accept-charset="', $context['character_set'], '">';

	//Load the Boards...
	echo '	
			<div class="windowbg">
				<span class="topslice"><span></span></span>
				<div class="content">

					<strong>', $txt['moderatormanagement_select_boards'], ':</strong><br />
					<table id="searchBoardsExpand" width="100%" border="0" cellpadding="0" cellspacing="0" align="center">';

			$alternate = true;
			foreach ($context['board_columns'] as $board)
			{
				if ($alternate)
					echo '
						<tr>';
						echo '
							<td width="50%">';

				if (!empty($board) && empty($board['child_ids']))
					echo '
								<label for="mboard', $board['id'], '" style="margin-left: ', $board['child_level'], 'ex;"><input type="checkbox" id="mboard', $board['id'], '" name="mboard[', $board['id'], ']" value="', $board['id'], '"', $board['is_moderator'] ? ' checked="checked"' : '', ' class="check" />', $board['name'], '</label>';
				elseif (!empty($board))
					echo '
								<a href="javascript:void(0);" onclick="selectBoards([', implode(', ', $board['child_ids']), ']); return false;" style="text-decoration: underline;">', $board['name'], '</a>';

					echo '
							</td>';
				if (!$alternate)
					echo '
						</tr>';

				$alternate = !$alternate;
			}

			echo '
					</table><br />

					<input type="checkbox" name="all" id="check_all" value=""'.($context['all_checked'] ? ' checked="checked"' : '').' onclick="invertAll(this, this.form, \'mboard\');" class="check" /><i> <label for="check_all">', $txt['check_all'], '</label></i><br />';

					// Go and save
					template_profile_save();

		echo '
				</div>
				<span class="botslice"><span></span></span>
			</div>
		</form>';

}