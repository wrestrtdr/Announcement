<?php
/**
 * Announcements Plugin
 * 
 * @package blesta
 * @subpackage blesta.plugins.Announcements
 * @copyright Copyright (c) 2005, Naja7host SARL.
 * @link http://www.naja7host.com/ Naja7host
 */
class ClientMain extends AnnouncementsController {

	/**
	 * Pre-action
	 */
	public function preAction() {
		parent::preAction();

		$this->company_id = (isset($this->client->company_id) ? $this->client->company_id : Configure::get("Blesta.company_id"));
		
		// Restore structure view location of the client portal
		$this->structure->setDefaultView(APPDIR);
		$this->structure->setView(null, $this->orig_structure_view);
	}


	/**
	 * View client profile ticket widget
	 */
	public function index() {
	}
	

}
?>