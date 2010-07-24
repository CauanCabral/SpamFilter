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
		'atributes' => array(),
		'entries' => array()
	);
	
	protected $_settings = array();
	
	public function __construct($settings = array())
	{
		$default_settings = array(
			'tokenSeparator' => '/\s|\[|\]|<|>|\?|;|\"|\'|\=|\/|:|\(|\)|!|&/',
			'binaryPath' => '/usr/bin/',
			'domain' => 'dom -a %s %s',
			'inductor' => 'bci  %s %s %s',
			'classifier' => 'bcx -cClassified -pConfidence',
			'output' => array(
				'types' => 'both', // valores válidos: tab, arff e both (ambos)
				'missingSymbol' => '?',
				'classAttribute' => 'spam',
			)
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
		
		// formata as instancias para salvar como arquivo '.tab'
		$modelContent = $this->_entriesFormatTab($trainingSet, true);
		
		// salva arquivo '.tab' com as entradas (instancias)
		if( !$this->_writeFile($this->_model['name'] . '.tab', $modelContent) )
		{
			trigger_error(__('Model file can\'t be saved. Check system permissions'), E_USER_ERROR);
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
			trigger_error(__('Can\'t generate domain.'), E_USER_ERROR);
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
			trigger_error(__('Can\'t generate classifier model.'), E_USER_ERROR);
			return false;
		}
		
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
				trigger_error(__('Model not exist or can\'t be read.'), E_USER_ERROR);
				return false;
			}
		}
		
		$fileContent = $this->_entriesFormatTab($entries);
		
		// salva arquivo '.tab' com as entradas (instancias) que serão classificadas
		if( !$this->_writeFile('samples.tab', $modelContent) )
		{
			trigger_error(__('Samples file can\'t be saved. Check system permissions'), E_USER_ERROR);
		}
		
		// executa o classificador
		exec(
			$this->_settings['binaryPath'] .
			sprintf(
				$this->_settings['classifier'],
				$this->_model['name'] . '.nbc',
				'samples.tab', 
				'tmp.cls'), 
			$exec_out,
			$status
		);
		
		if($status !== 0)
		{
			trigger_error(__('Can\'t classify samples.'), E_USER_ERROR);
			return false;
		}
	}
	
	/**
	 * Método responsável por carregar dados do modelo
	 * para classificação
	 * 
	 * @return bool $succcess
	 */
	protected function __loadModel()
	{
		// não é necessário ainda
		
		return true;
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