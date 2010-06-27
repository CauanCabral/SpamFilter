<?php
class NaiveBayesComponent extends Object {
	
	protected $_tokenSeparator = '/\s|\[|\]|<|>|(\?|;)/';
	
	protected $_settings = array();
	
	public function __construct($settings = array())
	{
		$default_settings = array(
			'binary_path' => '/usr/bin/',
			'inductor' => 'bci',
			'classifier' => 'bcx'
		);
		
		$this->_settings = array_merge($default_settings, $settings);
	}
	
	public function initialize(&$controller) 
	{
		$this->controller = $controller;
	}
	
	public function generateModel($trainingSet)
	{
		if(!is_array($trainingSet))
		{
			trigger_error(__('Training set must be an array'), E_USER_ERROR);
		}
		
		
	}
	
	public function updateModel($entry, $class)
	{
		$_entry = $this->_entryFormat($entry);
		
		
	}

	public function classify($entry)
	{
		$_entry = $this->_entryFormat($entry);
	}
	
	protected function _entryFormat($entry)
	{
		$out = array();
		
		$attributes = $this->_identifyAttributes($entry);
		
		foreach($attributes as $attr => $count)
		{
			echo $attr, ' : ', $count;
		}
		
		return $out;
	}
	
	protected function _identifyAttributes($entry)
	{
		$tokens = preg_split($this->_tokenSeparator, $entry);
		
		$out = array(
			'links_count' => 0
		);
		
		// identifica os links presentes
		$links = filter_var_array($tokens, FILTER_VALIDATE_URL);
		
		foreach($links as $link)
		{
			if(!empty($link))
			{
				$out['links_count']++;
			}
		}
		
		foreach($tokens as $token)
		{
			if(empty($token) || strlen($token) < 3)
				continue;
			
			$id = $token . '_freq';
			
			if(isset($out[$id]))
			{
				$out[$id]++;
			}
			else
			{
				$out[$token . '_freq'] = 1;
			}
		}
		
		return $out;
	}
}