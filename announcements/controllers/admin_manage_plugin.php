<?php
/**
 * Announcements Plugin
 * 
 * @package blesta
 * @subpackage blesta.plugins.Announcements
 * @copyright Copyright (c) 2005, Naja7host SARL.
 * @link http://www.naja7host.com/ Naja7host
 */
 
class AdminManagePlugin extends AppController {
	
	/**
	 * Performs necessary initialization
	 */
	private function init() {
		// Require login
		$this->parent->requireLogin();
		
	}
	
	/**
	 * Returns the view to be rendered when managing this plugin
	 */
	public function index() {
		$this->init();
		$this->redirect($this->base_uri . "plugin/announcements/admin_main/");
	}
}	
?>