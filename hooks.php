<?php

/**
 * hooks.php
 *
 * @package Profile Moderator Management
 * @version 3.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2014, Diego Andrés
 * @license http://www.mozilla.org/MPL/MPL-1.1.html
 */

	if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
		require_once(dirname(__FILE__) . '/SSI.php');

	elseif (!defined('SMF'))
		exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

	$hooks = array(
		'integrate_pre_include' => '$sourcedir/Profile-ModeratorManagement.php',
		'integrate_profile_areas' => '$sourcedir/Profile-ModeratorManagement.php|PMM::profile#',
		'integrate_profile_save' => '$sourcedir/Profile-ModeratorManagement.php|PMM::save#',
	);

	foreach ($hooks as $hook => $function)
		add_integration_function($hook, $function);