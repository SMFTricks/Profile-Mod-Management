<?php

/**
 * Profile-ModeratorManagement.php
 *
 * @package Profile Moderator Management
 * @version 3.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2015, Diego Andrés
 * @license http://www.mozilla.org/MPL/MPL-1.1.html
 */

if (!defined('SMF'))
	die('No direct access...');

class PMM
{

	public static function profile(&$profile_areas)
	{
		global $txt;

		// Forum Profile
		$insert = 'forumprofile';
		$counter = 0;
		foreach ($profile_areas['edit_profile']['areas'] as $area => $dummy)
			if (++$counter && $area == $insert)
				break;
		$profile_areas['edit_profile']['areas'] = array_merge(
			array_slice($profile_areas['edit_profile']['areas'], 0, $counter),
			array(
				'moderatormanagement' => array(
					'label' => $txt['moderatormanagement'],
					'file' => 'Profile-ModeratorManagement.php',
					'function' => 'PMM::ModeratorManagement',
					'icon' => 'boards',
					'sc' => 'post',
					'permission' => array(
						'own' => array('manage_boards'),
						'any' => array('manage_boards'),
					),
				),
			),
			array_slice($profile_areas['edit_profile']['areas'], $counter)
		);
	}

	public static function save($profile_vars, $post_errors, $memID)
	{

		if ((isset($_REQUEST['area']) && ($_REQUEST['area'] == 'moderatormanagement')) && empty($post_errors))
		{
			self::ModeratorManagement2($profile_vars, $post_errors, $memID);
			$force_redirect = false;
		}

	}

	public static function ModeratorManagement()
	{
		global $context, $txt, $smcFunc;

		loadtemplate('ModeratorManagement');

		// Create the tabs for the template.
		$context[$context['profile_menu_name']]['tab_data'] = array(
			'title' => $txt['moderatormanagement'],
			'description' => $txt['moderatormanagement_desc'],
			'icon' => 'profile_hd.png',
		);
		$context['sub_template'] = 'main';

		// Updated? Ugly fix but...
		if (isset($_REQUEST['saved']))
			$context['profile_updated'] = sprintf($txt['profile_updated_else'], $context['member']['name']);

		//Fist Load the list where he/she/it is a Moderator ;)
		$request = $smcFunc['db_query']('', '
			SELECT id_board
			FROM {db_prefix}moderators
			WHERE id_member = {int:id_member}',
			array(
				'id_member' => $context['member']['id'],
			)
		);
		
		$collectModeratorPositions = array();
		while($row = $smcFunc['db_fetch_assoc']($request)) 
			$collectModeratorPositions[$row['id_board']] = $row['id_board'];
		
		// Find all the boards this user is allowed to see.
		$request = $smcFunc['db_query']('', '
			SELECT b.id_cat, c.name AS cat_name, b.id_board, b.name, b.child_level
			FROM {db_prefix}boards AS b
				LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)
			WHERE {query_see_board}'
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
				'is_moderator' => isset($collectModeratorPositions[$row['id_board']]),
				'child_level' => $row['child_level'],
			);
		}
		$smcFunc['db_free_result']($request);

		// Now, let's sort the list of categories into the boards for templates that like that.
		$temp_boards = array();
		foreach ($context['categories'] as $category)
		{
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

		$context['all_checked'] = $context['num_boards'] == count($collectModeratorPositions);
		
	}

	public static function ModeratorManagement2($profile_vars, $post_errors, $memID)
	{
		global $context, $smcFunc;
	
		if(empty($memID))
			return;
		
		//First Remove it ;)
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}moderators
			WHERE id_member = {int:id_member}',
			array(
				'id_member' => $memID,
			)
		);
		
		//Nothing to do?
		if(empty($_REQUEST['mboard']))
			return;
		
		//No Double Entries ;)
		$_REQUEST['mboard'] = array_unique($_REQUEST['mboard']);

		// Previous code was ugly, I think this is going to work better :P
		$insert = array();
		foreach($_REQUEST['mboard'] as $board)
		{
			$insert[] = array(
				'id_member' => $memID,
				'id_board' => $board,
			);
		}

		// Make him moderator :)
		$smcFunc['db_insert']('',
			'{db_prefix}moderators',
			array(
				'id_member' => 'int', 
				'id_board' => 'int',
				),
			$insert,
			array()
		);


		if (!$context['user']['is_owner'])
		{
			redirectexit('action=profile;u='.$context['member']['id']. ';area=moderatormanagement;saved');
		}

	}
}