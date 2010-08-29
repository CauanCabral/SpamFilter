<?php
class Comment extends AppModel
{	
	public $name = 'Comment';
	
	public $displayField = 'name';
	
	public function getStats()
	{
		$comments = $this->find('all', array('fields' => array('spam')));
		
		$stats = array(
			'spam' => 0,
			'total' => 0
		);
		
		foreach($comments as $c)
		{
			$stats['total']++;
			
			$stats['spam'] += $c[$this->name]['spam'];
		}
		
		return $stats;
	}
}
?>