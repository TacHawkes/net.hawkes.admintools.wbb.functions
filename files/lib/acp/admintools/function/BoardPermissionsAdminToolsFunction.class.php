<?php
// wcf imports
require_once(WCF_DIR.'lib/acp/admintools/function/AbstractAdminToolsFunction.class.php');

/**
 * Copies board permissions
 *
 * This file is part of Admin Tools 2.
 *
 * Admin Tools 2 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Admin Tools 2 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Admin Tools 2.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author	Oliver Kliebisch
 * @copyright	2009 Oliver Kliebisch
 * @license	GNU General Public License <http://www.gnu.org/licenses/>
 * @package	net.hawkes.admintools.wbb.functions
 * @subpackage 	acp.admintools.function
 * @category 	WBB
 */
class BoardPermissionsAdminToolsFunction extends AbstractAdminToolsFunction {
	public $permissionSettings = array();
	public $moderatorSettings = array();

	/**
	 * Prepares the permission list
	 */
	public function __construct() {
		$this->readPermissionSettings();
		$this->readModeratorPermissionSettings();
	}

	/**
	 * @see AdminToolsFunction::execute($data)
	 */
	public function execute($data) {
		parent::execute($data);

		$parameters = $data['parameters']['wbb.boardPermissions'];
		$sourceBoardID = intval($parameters['sourceBoard']);		
		$targetBoardIDs = ArrayUtil::toIntegerArray(explode(',', $parameters['targetBoards']));
		if (in_array($sourceBoardID, $targetBoardIDs)) {
			$this->setReturnMessage('error', WCF::getLanguage()->get('wbb.acp.admintools.function.wbb.boardPermissions.sourceInTargetArray'));
			return;
		}

		if ($parameters['copyUsers']) {
			$this->copyAllocations('userID', 'board_to_user', $sourceBoardID, $targetBoardIDs, $this->permissionSettings);
		}

		if ($parameters['copyUsergroups']) {
			$this->copyAllocations('groupID', 'board_to_group', $sourceBoardID, $targetBoardIDs, $this->permissionSettings);
		}

		if ($parameters['copyModerators']) {
			$this->copyAllocations('userID, groupID', 'board_moderator', $sourceBoardID, $targetBoardIDs, $this->moderatorSettings);
		}

		// reset cache
		WCF::getCache()->clear(WBB_DIR.'cache/', 'cache.board*', true);
		// reset all sessions
		Session::resetSessions();
	}

	/**
	 * Copies allocations inside of tables
	 * 
	 * @param $IDfield
	 * @param $tableName
	 * @param $sourceID
	 * @param $targetIDs
	 */
	protected function copyAllocations($IDfield, $tableName, $sourceID, $targetIDs, $fields) {
		$sql = "DELETE FROM wbb".WBB_N."_".$tableName." WHERE boardID IN (".implode(',', $targetIDs).")";
		WCF::getDB()->sendQuery($sql);
		foreach($targetIDs as $targetID) {
			$sql = "INSERT INTO 	wbb".WBB_N."_".$tableName." 
						(boardID, ".$IDfield.", ".implode(',', $fields).")
				SELECT 		".$targetID.", ".$IDfield.", ".implode(',', $fields)."
				FROM 		wbb".WBB_N."_".$tableName."
				WHERE 		boardID = ".$sourceID;
			WCF::getDB()->sendQuery($sql);
		}
	}

	/**
	 * Gets available permission settings.
	 */
	protected function readPermissionSettings() {
		$sql = "SHOW COLUMNS 
			FROM 		wbb".WBB_N."_board_to_group";
		$result = WCF::getDB()->sendQuery($sql);
		while ($row = WCF::getDB()->fetchArray($result)) {
			if ($row['Field'] != 'boardID' && $row['Field'] != 'groupID') {
				$this->permissionSettings[] = $row['Field'];
			}
		}
	}
	
	/**
	 * Gets available permission settings.
	 */
	protected function readModeratorPermissionSettings() {
		$sql = "SHOW COLUMNS 
			FROM 		wbb".WBB_N."_board_moderator";
		$result = WCF::getDB()->sendQuery($sql);
		while ($row = WCF::getDB()->fetchArray($result)) {
			if ($row['Field'] != 'boardID' && $row['Field'] != 'groupID' && $row['Field'] != 'userID') {
				$this->moderatorSettings[] = $row['Field'];
			}
		}
	}
}
?>