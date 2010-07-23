<?php
class Comment extends AppModel
{	
	public $name = 'Comment';
	
	public $displayField = 'name';
	
	protected $tokenSeparator = '/\s|\[|\]|<|>|(\?|\.|\:|;)/';
	
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
	
	/**
	 * Faz parser dos comentários e retorna estatísticas sobre
	 * os tokens
	 * 
	 * 
	 * @return unknown_type
	 */
	public function getTokensStats()
	{
		
	}
	
	protected function _combineTokens($c1, $c2)
	{
		
	}
	
	protected function _parseComment($comment)
	{
		$tokens = preg_split($this->tokenSeparator, $comment);
		
		$out = array(
			'qt_links' => array('value' => '', 'type' => 'links', 'count' => 0)
		);
		
		// identifica os links presentes
		$links = filter_var_array($tokens, FILTER_VALIDATE_URL);
		
		foreach($links as $link)
		{
			if($link !== FALSE && $link !== null)
			{
				$out['qt_links']['count']++;
			}
		}
		
		foreach($tokens as $token)
		{
			$out['freq' . $token] = array(
				'value' => $token,
				'type' => $type,
				'count' => 1
			);
		}
	}
}
?>