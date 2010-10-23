<?php
App::import('Lib', array('BaseClassifier', 'Pa', 'NaiveBayes'));

/**
 * Modelo responsável por gerenciar os diferentes classificadores
 * da aplicação.
 * Faz uma ponte entre o classificador e a aplicação, preparando
 * os dados para o classificador e retornando seus dados.
 * 
 * Projeto desenvolvido para o trabalho final de graduação em Bacharelado
 * em Ciência da Computação, acadêmico Cauan Cabral.
 * 
 * 
 * @author Cauan Cabral
 * @link http://cauancabral.net
 * @copyright Cauan Cabral @ 2010
 * @license MIT License
 *
 */
class Classifier extends AppModel
{
	public $name = 'Classifier';

	/**
	 * ER responsável pela quebra das mensagens em tokens
	 * 
	 * @var $_tokenSeparator string
	 */
	protected $_tokenSeparator = '/\s|\[|\]|<|>|\?|;|\"|\'|\=|\/|:|\(|\)|!|&/';

	/**
	 * Atributo com referência ao classificador utilziado
	 * no momento
	 * 
	 * @var $_model object
	 */
	protected $_model = null;

	/**
	 *
	 * @param string $name
	 * @param array $entries
	 * @param string $type
	 * @return boolean
	 */
	public function buildModel($name, $entries, $type = 'Pa')
	{
		if($type == 'Pa')
		{
			$this->_model = new Pa($name);
		}
		else if($type == 'NaiveBayes')
		{
			$this->_model = new NaiveBayes($name, 6);
		}
		else
		{
			trigger_error(__('Classifier not supported', true), E_USER_ERROR);

			return false;
		}

		$trainingSet = array(
			'attributes' => array(),
			'entries' => array()
		);

		// identifica os atributos para cada entrada
		foreach($entries as $t => $entry)
		{
			$attributes = $this->__identifyAttributes($entry['content']);
			
			$trainingSet['entries'][$t]['attributes'] = $attributes;
			$trainingSet['entries'][$t][$this->_model->classField] = $entry['class'];

			foreach($attributes as $attr => $freq)
			{
				// verifica se o atributo já foi identificado em outra instância
				if(!in_array($attr, $trainingSet['attributes']))
				{
					$trainingSet['attributes'][] = $attr;
				}
			}
		}
		
		// adiciona no vetor de entradas, os atributos inexistentes no exemplo
		foreach($trainingSet['attributes'] as $attr)
		{
			foreach($trainingSet['entries'] as $k => $entry)
			{
				if(!in_array($attr, array_keys($entry['attributes'])))
				{	
					$trainingSet['entries'][$k]['attributes'][$attr] = 0;
				}
			}
		}

		$this->_model->modelGenerate($trainingSet);
		
		$this->_model->optimize();

		// guarda modelo de forma persistente (no bd)
		return $this->save(array('model' => serialize($this->_model)));
	}

	/**
	 * Método responsável por carregar dados do modelo
	 * para classificação
	 *
	 * @return bool $succcess
	 */
	public function loadModel($id = null)
	{
		if($id == null)
		{
			trigger_error(__('Classifier model can not be loaded', true), E_USER_ERROR);

			return false;
		}

		$_model = $this->find('first', array('conditions' => array('Classifier.id' => $id)));

		$this->_model = unserialize($_model[$this->name]['model']);

		if(empty($this->_model))
		{
			trigger_error(__('Model classifier not loaded', true), E_USER_ERROR);

			return false;
		}

		$this->id = $id;

		return $this->_model;
	}

	/**
	 * Método que classifica e retorna a classe
	 * de um conjunto de instâncias
	 * 
	 * @param array $entries
	 */
	public function classify($entries)
	{
		$toClassify = array();

		// identifica os atributos para cada entrada
		foreach($entries as $t => $entry)
		{
			$attributes = $this->__identifyAttributes($entry['content']);
			$toClassify[$t] = $attributes;

			foreach($attributes as $attr => $freq)
			{
				// verifica se o atributo faz parte dos atributos observados (parte do modelo)
				if(!in_array($attr, $this->_model->attributes))
				{
					// caso não faça, remove-o da lista
					unset($toClassify[$t][$attr]);
				}
			}
		}

		// adiciona no vetor de entradas, os atributos inexistentes no exemplo
		foreach($this->_model->attributes as $attr)
		{
			foreach($toClassify as $k => $entry)
			{
				if(!isset($entry[$attr]))
				{
					$toClassify[$k][$attr] = 0;
				}
			}
		}

		return $this->_model->classify($toClassify);
	}

	/**
	 * Realiza atualização do classificador baseado em um
	 * exemplo com classe conhecida
	 * 
	 * @param string $content
	 * @param string $class
	 * @param array $options
	 */
	public function update($content, $class, $options = array())
	{
		$entry = $this->__identifyAttributes($content);

		foreach($entry as $attr => $freq)
		{
			// verifica se o atributo faz parte dos atributos observados (parte do modelo)
			if(!isset($this->_model->attributes[$attr]))
			{
				// caso não faça, remove-o da lista
				unset($entry[$attr]);
			}
		}

		// adiciona no vetor de entradas, os atributos inexistentes no exemplo
		foreach($his->_model->attributes as $attr)
		{
			if(!isset($entry[$attr]))
			{
				$entry[$attr] = 0;
			}
		}

		if(isset($options['t']))
		{
			$t = $options['t'];
		}
		else
		{
			$t = null;
		}

		$this->_model->update($entry, $class, $t);
	}

	/**
	 * Separa a string de entrada em Tokens
	 *
	 * @param string $entry Conteúdo de entrada
	 *
	 * @return array $out Array de tokens identificados
	 */
	protected function __identifyAttributes($entry)
	{
		$tokens = preg_split($this->_tokenSeparator, $entry, null, PREG_SPLIT_NO_EMPTY);

		$out = array('links_count' => 0);

		foreach($tokens as $token)
		{
			$t = $this->__unifyString($token);

			if( mb_strlen($t) < 3 )
				continue;

			// contagem de links
			if(preg_match('/^www_/', $t))
			{
				$out['links_count']++;
			}
			
			if(isset($out[$t]))
			{
				$out[$t]++;
			}
			else
			{
				$out[$t] = 1;
			}
		}
		
		// remove os tokens com pouca presença
		foreach($out as $token => $freq)
		{
			if($freq < 3)
			{
				unset($out[$token]);
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
	private function __unifyString($s)
	{
		$out = mb_strtolower($s, 'UTF-8');
		$out = Inflector::slug($out);

		return $out;
	}

	/**
	 * 'Printa' o classificador
	 * 
	 * @return void
	 */
	public function printModel()
	{
		$this->_model->printModel(true);
	}

	/**
	 * Gera um relatório sobre o classificador e o
	 * retorna
	 * 
	 * @return array relatório
	 */
	public function modelReport()
	{
		$report = array();

		$report['type'] = get_class($this->_model);
		$report['id'] = $this->id;
		$report['number_of_instances'] = $this->_model->statistics['total'];
		$report['number_of_attributes'] = count($this->_model->attributes);
		$report['number_of_asserts'] = $this->_model->statistics['asserts'];

		$report['assertion_ratio'] = $this->_model->statistics['assertion_ratio'];
		$report['devianation'] = $this->_model->statistics['devianation'];

		if($report['type'] == 'Pa')
		{
			$report['extra']['w'] = $this->_model->getConfig();
		}
		else
		{
			$report['extra']['probabilities'] = $this->_model->getConfig();
		}

		return $report;
	}

	/**
	 * Imprime as estatísticas do classificador
	 * 
	 * @return void
	 */
	public function printStats()
	{
		$this->_model->printStats();
	}
}
?>