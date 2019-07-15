<?php

/**
 * Profile-ModManagement.php
 *
 * @package Profile Moderator Management
 * @version 3.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2019, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

if (!defined('SMF'))
	die('No direct access...');

class ProfileModManagement
{
	public static function Manage_profileAreas(&$profile_areas)
	{
		global $txt;

		// Profile information
		$before = 'theme';
		$temp_buttons = array();
		foreach ($profile_areas['edit_profile']['areas'] as $k => $v) {
			if ($k == $before) {
				$temp_buttons['modmanagement'] = array(
					'label' => $txt['modmanagement'],
					'file' => 'Profile-ModManagement.php',
					'function' => 'ProfileModManagement::Manage_profileMod',
					'icon' => 'settings',
					'sc' => 'post',
					'token' => 'profile-modb%u',
					'permission' => array(
						'own' => array('manage_boards'),
						'any' => array('manage_boards'),
					),
				);
			}
			$temp_buttons[$k] = $v;
		}
		$profile_areas['edit_profile']['areas'] = $temp_buttons;
	}

	public static function Manage_profileSave(&$profile_vars, &$post_errors, $memID, $cur_profile, $current_area)
	{
		if ($current_area == 'modmanagement')
		{
			if (empty($post_errors))
			{
				self::Manage_profileDo($profile_vars, $post_errors, $memID);
				$force_redirect = false;
			}
		}
	}

	public static function Manage_profileMod($memID)
	{
		global $context, $modSettings, $smcFunc, $cur_profile, $sourcedir, $txt;

		loadtemplate('Profile-ModManagement');
		$context['sub_template'] = 'Manage_profileMod';

		// Find all the boards this user is allowed to see.
		$request = $smcFunc['db_query']('order_by_board_order', '
			SELECT b.id_cat, c.name AS cat_name, b.id_board, b.name, b.child_level,
				m.id_member AS is_mod
			FROM {db_prefix}boards AS b
				LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)
				LEFT JOIN {db_prefix}moderators AS m ON (m.id_board = b.id_board) AND m.id_member = {int:user}
			WHERE {query_see_board}
				AND redirect = {string:empty_string}',
			array(
				'empty_string' => '',
				'user' => $memID,
			)
		);
		$context['num_boards'] = $smcFunc['db_num_rows']($request);
		$context['categories'] = array();
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			// This category hasn't been set up yet..
			if (!isset($context['categories'][$row['id_cat']]))
				$context['categories'][$row['id_cat']] = array(
					'id' => $row['id_cat'],
					'name' => $row['cat_name'],
					'boards' => array()
				);

			// Set this board up, and let the template know when it's a child.  (indent them..)
			$context['categories'][$row['id_cat']]['boards'][$row['id_board']] = array(
				'id' => $row['id_board'],
				'name' => $row['name'],
				'child_level' => $row['child_level'],
				'selected' => (!empty($row['is_mod']) ? 1 : 0),
			);
		}
		$smcFunc['db_free_result']($request);

		require_once($sourcedir . '/Subs-Boards.php');
		sortCategories($context['categories']);

		// Now, let's sort the list of categories into the boards for templates that like that.
		$temp_boards = array();
		foreach ($context['categories'] as $category)
		{
			// Include a list of boards per category for easy toggling.
			$context['categories'][$category['id']]['child_ids'] = array_keys($category['boards']);

			$temp_boards[] = array(
				'name' => $category['name'],
				'child_ids' => array_keys($category['boards'])
			);
			$temp_boards = array_merge($temp_boards, array_values($category['boards']));
		}

		$max_boards = ceil(count($temp_boards) / 2);
		if ($max_boards == 1)
			$max_boards = 2;

		// Now, alternate them so they can be shown left and right ;).
		$context['board_columns'] = array();
		for ($i = 0; $i < $max_boards; $i++)
		{
			$context['board_columns'][] = $temp_boards[$i];
			if (isset($temp_boards[$i + $max_boards]))
				$context['board_columns'][] = $temp_boards[$i + $max_boards];
			else
				$context['board_columns'][] = array();
		}

		// Profile updated?
		if (isset($context['profile_updated']))
			$context['profile_updated'] = $context['user']['is_owner'] ? $txt['profile_updated_own'] : sprintf($txt['profile_updated_else'], $cur_profile['member_name']);
	}

	public static function Manage_profileDo($profile_vars, $post_errors, $memID)
	{
		global $context, $smcFunc;

		if (isset($_POST['sa']) && $_POST['sa'] == 'modmanagement' && empty($_POST['mod_brd']))
			$_POST['mod_brd'] = array();

		if (isset($_POST['mod_brd']))
		{
			// First remove those that were deselected
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}moderators
				WHERE id_member = {int:id_member}' . (empty($_POST['mod_brd']) ? '' : '
					AND id_board NOT IN ({array_int:boards})'),
				array(
					'id_member' => $memID,
					'boards' => $_POST['mod_brd'],
				)
			);

			// Add him as moderator for selected boards
			foreach($_POST['mod_brd'] as $board)
			{
				// Make him moderator :)
				$smcFunc['db_insert']('ignore',
					'{db_prefix}moderators',
					array(
						'id_member' => 'int', 
						'id_board' => 'int',
						),
					array(
						$memID,
						$board,
					),
					array()
				);
			}
		}

		// Get us outta here
		redirectexit('action=profile;u='.$context['member']['id']. ';area=modmanagement;updated');
	}
}