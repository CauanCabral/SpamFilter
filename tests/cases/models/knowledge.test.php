<?php
/* Knowledge Test cases generated on: 2010-11-06 15:11:05 : 1289067725*/
App::import('Model', 'Knowledge');

class KnowledgeTestCase extends CakeTestCase {
	var $fixtures = array('app.knowledge', 'app.comment');

	function startTest() {
		$this->Knowledge =& ClassRegistry::init('Knowledge');
	}

	function endTest() {
		unset($this->Knowledge);
		ClassRegistry::flush();
	}

}
?>