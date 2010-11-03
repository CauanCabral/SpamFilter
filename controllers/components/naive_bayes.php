<?php
class NaiveBayesComponent extends Object {
	
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
		'name' => 'spams',
		'attributes' => array(),
		'entries' => array()
	);
	
	protected $_settings = array();
	
	public function __construct()
	{
		$this->_settings = array(
			'tokenSeparator' => '/\s|\[|\]|<|>|\?|;|\"|\'|\=|\/|:|\(|\)|!|&/',
			'binaryPath' => '/usr/bin/',
			'domain' => 'dom -a %s %s',
			'inductor' => 'bci  %s %s %s',
			'classifier' => 'bcx -cClassified -pConfidence -x -w %s %s %s',
			'compact' => 'tar -zcf %s.tar.gz %s',
			'output' => array(
				'types' => 'both', // valores válidos: tab, arff e both (ambos)
				'missingSymbol' => '?',
				'classAttribute' => 'spam',
				'classAttributeValues' => array('spam', 'not_spam')
			)
		);
	}
	
	public function initialize(&$controller, $settings = array()) 
	{
		$this->controller = $controller;
		
		$this->_settings = Set::merge($this->_settings, $settings);
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
			trigger_error(__('Training set must be an array', true), E_USER_ERROR);
		}
		
		if( $this->_settings['output']['types'] == 'both' || $this->_settings['output']['types'] == 'tab' )
		{
			// formata as instancias para salvar como arquivo '.tab'
			$modelContent = $this->_entriesFormatTab($trainingSet, true);
			
			// salva arquivo '.tab' com as entradas (instancias)
			if( !$this->_writeFile($this->_model['name'] . '.tab', $modelContent) )
			{
				trigger_error(__('Model file can\'t be saved. Check system permissions', true), E_USER_ERROR);
			}
		}
		
		if( $this->_settings['output']['types'] == 'both' || $this->_settings['output']['types'] == 'arff')
		{
			// formata as instancias para salvar como arquivo '.arff'
			$modelContent = $this->_entriesFormatArff($trainingSet);
			
			// salva arquivo '.arff' com as entradas (instancias)
			if( !$this->_writeFile($this->_model['name'] . '.arff', $modelContent) )
			{
				trigger_error(__('Model file can\'t be saved. Check system permissions', true), E_USER_ERROR);
			}
		}
		
		// array auxiliar que armazenará a saída do último binário executado via 'exec'
		$exec_out = array();
		
		// cria o domínio dos dados
		exec(
			$this->_settings['binaryPath'] .
			sprintf(
				$this->_settings['domain'],
				$this->_model['name'] . '.tab',
				$this->_model['name'] . '.dom'), 
			$exec_out,
			$status
		);
		
		if($status !== 0)
		{
			trigger_error(__('Can\'t generate domain.', true), E_USER_ERROR);
			return false;
		}
		
		unset($exec_out); // limpa array de controle
		
		// induz o classificador (modelo)
		exec(
			$this->_settings['binaryPath'] .
			sprintf(
				$this->_settings['inductor'],
				$this->_model['name'] . '.dom',
				$this->_model['name'] . '.tab', 
				$this->_model['name'] . '.nbc'), 
			$exec_out,
			$status
		);
		
		if($status !== 0)
		{
			trigger_error(__('Can\'t generate classifier model.', true), E_USER_ERROR);
			return false;
		}
		
		// compacta arquivos gerados
		exec(
			sprintf(
				$this->_settings['compact'],
				$this->_model['name'],
				$this->_model['name'] . '.tab ' . $this->_model['name'] . '.nbc ' . $this->_model['name'] . '.arff'
			),
			$exec_out,
			$status
		);
		
		return true;
	}
	
	/**
	 * @TODO implementar método para atualização do classificador
	 * 
	 */
	public function modelUpdate() {}

	/**
	 * Classifica um conjunto de entradas
	 * 
	 * @param array $entries
	 */
	public function classify($entries)
	{
		// caso não haja um modelo carregado, tenta carrega-lo
		if(empty($this->_model['entries']))
		{
			// se não for possível carregar, aborta o classificador
			if( $this->__loadModel() === false)
			{
				trigger_error(__('Model not exist or can\'t be read.', true), E_USER_ERROR);
				return false;
			}
		}
		
		$fileContent = $this->_entriesFormatTab($entries);
		
		// salva arquivo '.tab' com as entradas (instancias) que serão classificadas
		if( !$this->_writeFile('samples.tab', $fileContent) )
		{
			trigger_error(__('Samples file can\'t be saved. Check system permissions', true), E_USER_ERROR);
		}
		
		$exec_out = array();
		
		// executa o classificador
		exec(
			$this->_settings['binaryPath'] .
			sprintf(
				$this->_settings['classifier'],
				$this->_model['name'] . '.nbc',
				'samples.tab',
				'output.tmp'), 
			$exec_out,
			$status
		);
		
		if($status !== 0)
		{
			trigger_error(__('Can\'t classify samples.', true), E_USER_ERROR);
			return false;
		}
		
		$tmp = file_get_contents('output.tmp');
		
		$classified = explode(' ', $tmp);
		$l = count($classified) - 1;
		
		return array(
			'class' => $classified[$l - 3],
			'confidence' => $classified[$l - 2]
		);
	}
	
	/**
	 * 
	 * @param array $trainingSet
	 * 
	 * @return string Nome do arquivo tar com os dados
	 */
	public function export($trainingSet)
	{
		if(!is_array($trainingSet))
		{
			trigger_error(__('Training set must be an array', true), E_USER_ERROR);
		}
		
		if( $this->_settings['output']['types'] == 'both' || $this->_settings['output']['types'] == 'tab' )
		{
			// formata as instancias para salvar como arquivo '.tab'
			$modelContent = $this->_entriesFormatTab($trainingSet, true);
			
			// salva arquivo '.tab' com as entradas (instancias)
			if( !$this->_writeFile($this->_model['name'] . '.tab', $modelContent) )
			{
				trigger_error(__('Model file can\'t be saved. Check system permissions', true), E_USER_ERROR);
			}
		}
		
		if( $this->_settings['output']['types'] == 'both' || $this->_settings['output']['types'] == 'arff')
		{
			// formata as instancias para salvar como arquivo '.arff'
			$modelContent = $this->_entriesFormatArff($trainingSet);
			
			// salva arquivo '.arff' com as entradas (instancias)
			if( !$this->_writeFile($this->_model['name'] . '.arff', $modelContent) )
			{
				trigger_error(__('Model file can\'t be saved. Check system permissions', true), E_USER_ERROR);
			}
		}
		
		// array auxiliar que armazenará a saída do último binário executado via 'exec'
		$exec_out = array();
		
		// compacta arquivos gerados
		exec(
			sprintf(
				$this->_settings['compact'],
				$this->_model['name'],
				$this->_model['name'] . '.tab ' . $this->_model['name'] . '.arff'
			),
			$exec_out,
			$status
		);
		
		if($status !== 0)
		{
			trigger_error(__('Can\'t compact data.', true), E_USER_ERROR);
			return false;
		}
		
		$name = $this->_model['name'] . '.tar.gz';
		
		chmod($name, 0777);
		
		return $name;
	}
	
	/**
	 * Método responsável por carregar dados do modelo
	 * para classificação
	 * 
	 * @return bool $succcess
	 */
	protected function __loadModel()
	{
		$tmp = file_get_contents($this->_model['name'] . '.tab');
		$tmp = explode("\n", $tmp);  
		
		$aux = explode(' ', $tmp[0]);
		
		unset($tmp);
		
		$header = array();
		
		// identifica o tamanho de cada coluna (atributo) para formatação final
		foreach($aux as $attr)
		{
			// verifica o tamanho da string com valor da coluna
			if(!isset($header[$attr]))
			{
				$header[$attr] = mb_strlen($attr);
			}
		}
		
		// remove a classe da classificação, pois isso é adicionado automaticamente
		if(isset($header[$this->_settings['output']['classAttribute']]))
			unset($header[$this->_settings['output']['classAttribute']]);
		
		$this->_model['attributes'] = $header;
		
		return !empty($this->_model['attributes']);
	}
	
	/**
	 * Gera um string formatada (estilo arquivo '.tab') para um conjunto de valores
	 * de entrada.
	 * 
	 * @param array $entries Array com as instâncias
	 * @param bool $includeHeader Flag de controle para incluir cabeçalho dos tokens
	 * na string de retorno
	 * 
	 * @return string $output String contendo os tokens identificados dentro
	 * do array de entradas com suas repectivas frequências formatado como um arquivo '.tab'
	 */
	protected function _entriesFormatTab($entries, $overrideAttributes = false)
	{
		$header = array();
		$lines = array(); 
		
		// identifica os atributos para cada entrada
		foreach($entries as $entry)
		{
			$lines[] = array(
				'class' => $entry['class'],
				'attributes' => $this->_identifyAttributes($entry['content'])
			);
		}
		
		if($overrideAttributes)
		{
			$this->_model['entries'] = $lines;
			
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
		$output = implode(" ", array_keys($header)) . " {$this->_settings['output']['classAttribute']}\n";
		
		// adiciona linhas restantes
		foreach($lines as $line)
		{
			foreach($header as $key => $len)
			{
				if(isset($line['attributes'][$key]))
					$output .= $line['attributes'][$key] . str_repeat(' ', $len - mb_strlen($line['attributes'][$key]) + 1);
					
				else
					$output .= $this->_settings['output']['missingSymbol'] . str_repeat(' ', $len - strlen($this->_settings['output']['missingSymbol']) + 1);
			}
			
			// concatena coluna referente a classe
			$output .= $line['class'];
			
			$output .= "\n";
		}
		
		return $output;
	}
	
	/**
	 * Gera um string formatada (estilo arquivo '.arff') para um conjunto de valores
	 * de entrada.
	 * 
	 * @param array $entries Array com as instâncias
	 * @param bool $includeHeader Flag de controle para incluir cabeçalho dos tokens
	 * na string de retorno
	 * 
	 * @return string $output String contendo os tokens identificados dentro
	 * do array de entradas com suas repectivas frequências formatado como um arquivo '.arff'
	 */
	protected function _entriesFormatArff($entries, $overrideEntries = false, $overrideAttributes = false)
	{
		$header = array();
		$lines = array();
		
		// caso já tenha sido identificado os atributos para as entradas
		if(!empty($this->_model['entries']) && $overrideEntries === false)
		{
			$lines = $this->_model['entries'];
		}
		else if($overrideEntries === true)
		{
			// identifica os atributos para cada entrada
			foreach($entries as $entry)
			{
				$lines[] = array(
					'class' => $entry['class'],
					'attributes' => $this->_identifyAttributes($entry['content'])
				);
			}
		}
		
		// identifica o tipo de cada coluna (atributo)
		foreach($lines as $line)
		{
			foreach($line['attributes'] as $key => $col)
			{
				// não verifica o tipo do atributo em diferentes instancias
				if(isset($header[$key]))
					continue;
				
				// ignora/remove atributos pouco significativos
				if($col < 3)
					continue;
				
				// verifica o tipo do atributo
				if(is_numeric($col))
					$header[$key] = 'numeric';
				else if(is_string($col))
					$header[$key] = 'string';
			}
		}
		
		// inicia conteúdo do arquivo de saída
		$output = '@RELATION ' . $this->_model['name'] . "\n\n";
		
		foreach($header as $attr => $type)
		{
			$output .= '@ATTRIBUTE ' . $attr . ' ' . $type . "\n";
		}
		
		$output .= '@ATTRIBUTE ' . $this->_settings['output']['classAttribute'] . ' {' . implode(',', $this->_settings['output']['classAttributeValues']) . '}';
		$output .= "\n\n@DATA\n";
		
		// adiciona linhas restantes
		foreach($lines as $line)
		{
			foreach($header as $key => $len)
			{
				if(isset($line['attributes'][$key]))
					$output .= $line['attributes'][$key];
					
				else
					$output .= $this->_settings['output']['missingSymbol'];
					
				$output .= ',';
			}
			
			// concatena coluna referente a classe
			$output .= $line['class'];
			
			$output .= "\n";
		}
		
		return $output;
	}
	
	/**
	 * Separa a string de entrada em Tokens e conta a frequência
	 * de cada uma delas dentro da sentença inicial.
	 * 
	 * @param string $entry Conteúdo de entrada
	 * 
	 * @return array $out Array tendo como índices os tokens idenficados e como valor
	 * associado a frequência de cada um.
	 */
	protected function _identifyAttributes($entry)
	{
		$tokens = preg_split($this->_settings['tokenSeparator'], $entry, null, PREG_SPLIT_NO_EMPTY);
		
		$out = array('links_count' => 0);
		
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
	
	/**
	 * Transforma a string de entrada em lower-case e substitui caracteres especiais
	 * (incluindo acentos) em correspondentes ASCII
	 * 
	 * @param string $s String de entrada
	 * 
	 * @return string $out String de entrada com caracteres especiais substituidos por
	 * correspondentes ASCII
	 */
	private function _unifyString($s)
	{
		$out = mb_strtolower($s, 'UTF-8');
		$out = Inflector::slug($out);
		
		return $out;
	}
	
	/**
	 * Escreve um arquivo no sistema e torna-o
	 * disponível a todos os usuários (permissão 777)
	 * 
	 * @param string $name Nome do arquivo
	 * @param string $data Conteúdo do arquivo
	 * 
	 * @return bool $success
	 */
	private function _writeFile($name, $data)
	{
		$file = new SplFileObject($name, "w");
		$written = $file->fwrite($data);
		chmod($name, 0777);
		
		return ($written !== null);
	}
}