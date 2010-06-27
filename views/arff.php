<?php
App::import('View', 'View', false);

class Arff extends View
{
	public $arffData = null;
	
/**
 * Constructor
 *
 * @param object $controller
 */
	public function __construct(&$controller) {
		parent::__construct($controller);
		
		$arffData = $this->viewVars['arff'];
	}
	
	protected function _header()
	{
		
	}
	
	protected function _body()
	{
		
	}
	
	public function render() {
		if(empty($this->arffData))
		{
			return;
		}
		
		
	}
}