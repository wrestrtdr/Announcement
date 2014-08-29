<?php
/**
 * Announcements Plugin
 * 
 * @package blesta
 * @subpackage blesta.plugins.Announcements
 * @copyright Copyright (c) 2005, Naja7host SARL.
 * @link http://www.naja7host.com/ Naja7host
 */
class ClientWidget extends AnnouncementsController {

	/**
	 * Pre-action
	 */
	public function preAction() {
		parent::preAction();

		$this->company_id = (isset($this->client->company_id) ? $this->client->company_id : Configure::get("Blesta.company_id"));
		$this->client_id = $this->Session->read("blesta_client_id");
		
		Language::loadLang("client_widget", null, PLUGINDIR . "announcements" . DS . "language" . DS);
		
		$this->uses(array("Announcements.Announcements"));
		
		// Restore structure view location of the client portal
		$this->structure->setDefaultView(APPDIR);
		$this->structure->setView(null, $this->orig_structure_view);
	}


	/**
	 * View client profile ticket widget
	 */
	public function index() {
	
		// Ensure a valid client was given
		if (!$this->isAjax()) {
			header($this->server_protocol . " 401 Unauthorized");
			exit();
		}
		
		// $this->announcements = $this->Announcements->getAnnouncementClient($this->company_id, $this->client_id , $page = 1) ;
		
		// foreach ($this->announcements as $announcement ) {		
			// if ($this->Announcements->hasAccessToAnnouncement($announcement->id, $this->company_id, $this->client_id))
				// $this->setMessage("notice",  $this->Announcements->truncateHtml($announcement->body) , false, array(
					// 'notice_title' => $announcement->title ,
					// 'notice_buttons' => array(
						// array(
							// 'class' => "btn",
							// 'url' => $this->Html->safe($this->base_uri . "plugin/announcements/client_main/view/" . $announcement->id . "/"),
							// 'label' => Language::_("ClientWidget.read_more", true)
						// )
					// )
				// ), 
				// false );
		// }
		
		if ($this->isAjax())
			return $this->renderAjaxWidgetIfAsync();		
	
	}
	
	public function newannounce() {
	
		// Ensure a valid client was given
		if (!$this->isAjax()) {
			header($this->server_protocol . " 401 Unauthorized");
			exit();
		}
				

		
		$this->announcements = $this->Announcements->getAnnouncementClient($this->company_id, $this->client_id , $page = 1) ;
		
		// foreach ($this->announcements as $announcement ) {		
			// if ($this->Announcements->hasAccessToAnnouncement($announcement->id, $this->company_id, $this->client_id))
				// $this->setMessage("notice",  $this->Announcements->truncateHtml($announcement->body) , false, array(
					// 'notice_title' => $announcement->title ,
					// 'notice_buttons' => array(
						// array(
							// 'class' => "btn",
							// 'url' => $this->Html->safe($this->base_uri . "plugin/announcements/client_main/view/" . $announcement->id . "/"),
							// 'label' => Language::_("ClientWidget.read_more", true)
						// )
					// )
				// ), 
				// false );
		// }
		
		foreach ($this->announcements as $announcement ) {		
			if ($this->Announcements->hasAccessToAnnouncement($announcement->id, $this->company_id, $this->client_id))
				//unset($announcement);
				$announcement->body = $this->Announcements->truncateHtml($announcement->body);
				$announcement->date_added = $this->Date->cast($announcement->date_added , "d-m-Y");				
				
				
				
				// $this->setMessage("notice",  $this->Announcements->truncateHtml($announcement->body) , false, array(
					// 'notice_title' => $announcement->title ,
					// 'notice_buttons' => array(
						// array(
							// 'class' => "btn",
							// 'url' => $this->Html->safe($this->base_uri . "plugin/announcements/client_main/view/" . $announcement->id . "/"),
							// 'label' => Language::_("ClientWidget.read_more", true)
						// )
					// )
				// ), 
				// false );
		}
		
		$vars = array(
			'announcements' => $this->announcements,
		);
		
		// print_r($vars);
		// Set the partial for currency amounts
		$response = $this->partial("client_widget_newannounce" , $vars );

		// JSON encode the AJAX response
		$this->outputAsJson($response);
			return false;			
			
		// if ($this->isAjax())
			// return $this->renderAjaxWidget( "client_widget_newannounce" , false );	

	
	}	
	

}
?>