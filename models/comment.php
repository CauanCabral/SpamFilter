<?php
class Comment extends AppModel
{	
	public $name = 'Comment';
	
	public $displayField = 'author';
	
	public $hasOne = array(
		'Knowledge'
	);
}
?>