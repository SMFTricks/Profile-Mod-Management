<?php

/**
 * @package Profile Moderator Management
 * @version 3.1
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2021, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

if (!defined('SMF'))
	die('No direct access...');

class ProfileModManagement
{
	/**
	 * @var array The boards the user will moderate
	 */
	private static $_boards = [];

	/**
	 * @var array The data to be inserted in the database
	 */
	private static $_mod_boards = [];

	/**
	 * ProfileModManagement::Manage_profileAreas()
	 *
	 * Add our new section to the profile areas
	 * 
	 * @param array $profile_areas Contains the profile menu areas
	 * @return void
	 */
	public static function Manage_profileAreas(&$profile_areas)
	{
		global $txt;

		// Load the language file
		loadLanguage(__CLASS__. '/');

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
					'token' => 'profile-modboard%u',
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

	/**
	 * ProfileModManagement::Manage_profileSave()
	 *
	 * Intercept the current area when saving so we can add the moderators
	 * 
	 * @param array $post_errors Contains the profile errors (if any)
	 * @param int $memID Contains the current profile member id
	 * @return void
	 */
	public static function Manage_profileSave(&$profile_vars, &$post_errors, $memID, $cur_profile, $current_area)
	{
		if ($current_area == 'modmanagement')
			if (empty($post_errors))
			{
				self::Manage_profileDo($memID);
				$force_redirect = false;
			}
	}

	/**
	 * ProfileModManagement::Manage_profileDo()
	 *
	 * Do the actual saving
	 * 
	 * @param int $memID Contains the current profile member id
	 * @return void
	 */
	public static function Manage_profileDo($memID)
	{
		global $smcFunc;

		// Only save for this page
		if (isset($_REQUEST['sa']) && $_REQUEST['sa'] == 'modmanagement')
		{
			self::$_boards = (isset($_REQUEST['modmanagement']) && is_array($_REQUEST['modmanagement']) ? $_REQUEST['modmanagement'] : []);
			// First remove those that were deselected
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}moderators
				WHERE id_member = {int:id_member}' . (empty(self::$_boards) ? '' : '
					AND id_board NOT IN ({array_int:boards})'),
				[
					'id_member' => $memID,
					'boards' => self::$_boards,
				]
			);

			// Add him as moderator for selected boards
			if (!empty(self::$_boards))
			{
				// Sort the info
				self::$_mod_boards = [];
				foreach (self::$_boards as $board)
				{
					self::$_mod_boards[] = [
						$memID,
						$board,
					];
				}

				// Insert the the new boards the user moderates
				$smcFunc['db_insert']('ignore',
					'{db_prefix}moderators',
					[
						'id_member' => 'int', 
						'id_board' => 'int',
					],
					self::$_mod_boards,
					[]
				);
			}
		}

		// Get us outta here
		redirectexit('action=profile;u=' . $memID . ';area=modmanagement;updated');
	}

	/**
	 * ProfileModManagement::Manage_profileMod()
	 *
	 * Loads the actual section with the boards and categories
	 * 
	 * @param int $memID Contains the current profile member id
	 * @return void
	 */
	public static function Manage_profileMod($memID)
	{
		global $context, $smcFunc, $cur_profile, $txt;

		// Load the templates
		loadtemplate('Profile-ModManagement');

		// Area details
		$context['sub_template'] = 'Manage_profileMod';
		$txt['membergroups_new_board_desc'] = $txt['modmanagement_select'];

		$request = $smcFunc['db_query']('', '
			SELECT b.id_cat, c.name AS cat_name, b.id_board, b.name, b.child_level, m.id_member AS is_mod
			FROM {db_prefix}boards AS b
				LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)
				LEFT JOIN {db_prefix}moderators AS m ON (m.id_board = b.id_board) AND m.id_member = {int:user}
			ORDER BY board_order',
			[
				'user' => $memID
			]
		);
		$context['num_boards'] = $smcFunc['db_num_rows']($request);

		$context['categories'] = [];
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			// This category hasn't been set up yet..
			if (!isset($context['categories'][$row['id_cat']]))
				$context['categories'][$row['id_cat']] = [
					'id' => $row['id_cat'],
					'name' => $row['cat_name'],
					'boards' => []
				];

			// Set this board up, and let the template know when it's a child.  (indent them..)
			$context['categories'][$row['id_cat']]['boards'][$row['id_board']] = [
				'id' => $row['id_board'],
				'name' => $row['name'],
				'child_level' => $row['child_level'],
				'selected' => (!empty($row['is_mod']) ? 1 : 0),
			];
		}
		$smcFunc['db_free_result']($request);

		// Now, let's sort the list of categories into the boards for templates that like that.
		$temp_boards = [];
		foreach ($context['categories'] as $category)
		{
			$temp_boards[] = [
				'name' => $category['name'],
				'child_ids' => array_keys($category['boards'])
			];
			$temp_boards = array_merge($temp_boards, array_values($category['boards']));

			// Include a list of boards per category for easy toggling.
			$context['categories'][$category['id']]['child_ids'] = array_keys($category['boards']);
		}

		// Profile updated?
		if (isset($context['profile_updated']))
			$context['profile_updated'] = $context['user']['is_owner'] ? $txt['profile_updated_own'] : sprintf($txt['profile_updated_else'], $cur_profile['member_name']);
	}
}