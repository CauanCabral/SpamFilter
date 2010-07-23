<?php
class NaiveBayesComponent extends Object {
	
	protected $_modelName = 'model';
	
	protected $_modelPath = './';
	
	/**
	 * Atributo que guarda informações sobre o modelo
	 * atual gerado pelo algorítmo NaiveBayes
	 * Informações armazenadas são:
	 * 	'atributes' => uma lista com o nome de todos os atributos
	 * 	'entries' => uma matriz com a contagem de cada atributo
	 * 
	 * @var array
	 */
	protected $_model = array(
		'atributes' => array(),
		'entries' => array()
	);
	
	/**
	 * Expressão regular que define os separadores dos tokens
	 * válidos que formam um atributo
	 * 
	 * @var string
	 */
	protected $_tokenSeparator = '/\s|\[|\]|<|>|\?|;|\"|\'|\=|\/|:|\(|\)|!|&/';
	
	protected $_missingSymbol = '?';
	
	protected $_classAttribute = 'spam';
	
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
	
	/**
	 * Gera um model para o classificador
	 * 
	 * @param array $trainingSet
	 */
	public function modelGenerate($trainingSet)
	{	
		if(!is_array($trainingSet))
		{
			trigger_error(__('Training set must be an array'), E_USER_ERROR);
		}
		
		$inputFile = $this->_entriesFormat($trainingSet, true);
		
		$file = new SplFileObject("comments.tab", "w");
		$written = $file->fwrite($inputFile);
		
		echo ($written !== null) ? "Arquivo escrito" : "Falha ao escrever arquivo"; 
	}
	
	public function modelUpdate($entry, $class)
	{
		$_entry = $this->_entryFormat($entry);
	}

	/**
	 * 
	 * Classifica um conjunto de entradas
	 * 
	 * @param array $entries
	 */
	public function classify($entries)
	{
		$inputFile = $this->_entriesFormat($entries);
		
		$file = new SplFileObject("comment.tab", "w");
		$written = $file->fwrite($inputFile);
		
		echo ($written !== null) ? "Arquivo escrito" : "Falha ao escrever arquivo";
	}
	
	/**
	 * 
	 * Método responsável por carregar o modelo de filtro
	 * NaiveBayes para uso futuro (classificação)
	 */
	protected function __loadModel()
	{
		
	}
	
	/**
	 * 
	 * 
	 * @param unknown_type $entry
	 * @param unknown_type $includeHeader
	 */
	protected function _entriesFormat($entries, $overrideAttributes = false)
	{
		$header = array();
		$lines = array(); 
		
		$first = true;
		
		// identifica os atributos para cada entrada
		foreach($entries as $entry)
		{
			$lines[] = array(
				'class' => $entry['class'],
				'attributes' => $this->_identifyAttributes($entry['content'])
			);
		}
		
		$this->_mode['entries'] = $lines;
		
		if($overrideAttributes)
		{
			// identifica o tamanho de cada coluna (atributo) para formatação final
			foreach($lines as $line)
			{
				foreach($line['attributes'] as $key => $col)
				{
					// ignora/remove atributos pouco significativos
					if($col < 3)
						continue;
					
					// verifica o tamanho da string com valor da coluna
					if(!isset($header[$key]))
					{
						if(mb_strlen($key) > strlen($col))
							$header[$key] = mb_strlen($key);
						else
							$header[$key] = strlen($col);
					}
					else if($header[$key] < strlen($col))
						$header[$key] = strlen($col);
				}
			}
		
			$this->_model['attributes'] = $header;
		}
		else
			$header = $this->_model['attributes'];
		
		// inicia formatação final definindo linha de cabeçalho
		$output = implode(" ", array_keys($header)) . " {$this->_classAttribute}\n";
		
		// adiciona linhas restantes
		foreach($lines as $line)
		{
			foreach($header as $key => $len)
			{
				if(isset($line['attributes'][$key]))
					$output .= $line['attributes'][$key] . str_repeat(' ', $len - mb_strlen($line['attributes'][$key]) + 1);
					
				else
					$output .= $this->_missingSymbol . str_repeat(' ', $len - strlen($this->_missingSymbol) + 1);
			}
			
			// concatena coluna referente a classe
			$output .= $line['class'] . str_repeat(' ', strlen($this->_classAttribute) - strlen($line['class']) + 1);
			
			$output .= "\n";
		}
		
		return $output;
	}
	
	protected function _identifyAttributes($entry)
	{
		$tokens = preg_split($this->_tokenSeparator, $entry, null, PREG_SPLIT_NO_EMPTY);
		
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
			$t = $this->_unifyString($token);
			
			if( mb_strlen($t) < 3 )
				continue;
			
			$id = $t . '_freq';
			
			if(isset($out[$id]))
			{
				$out[$id]++;
			}
			else
			{
				$out[$id] = 1;
			}
		}
		
		return $out;
	}
	
	private function _unifyString($s)
	{
		$out = mb_strtolower($s, 'UTF-8');
		$out = Inflector::slug($out);
		
		return $out;
	}
}