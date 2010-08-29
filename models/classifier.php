<?php
App::import('Lib', array('Pa', 'NaiveBayes'));

class Classifier extends AppModel
{
	public $name = 'Classifier';

	public $useTable = false;

	protected $_tokenSeparator = '/\s|\[|\]|<|>|\?|;|\"|\'|\=|\/|:|\(|\)|!|&/';

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
			$this->_model = new NaiveBayes($name);
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
			$trainingSet['entries'][$t]['class'] = $entry['class'];

			foreach($attributes as $attr => $freq)
			{
				// verifica se o atributo já foi identificado em outra instância
				if(!isset($trainingSet['attributes'][$attr]))
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
				if(!isset($entry['attributes'][$attr]))
				{
					$trainingSet['entries'][$k]['attributes'][$attr] = 0;
				}
			}
		}

		$this->_model->modelGenerate($trainingSet);
	}

	/**
	 * Método responsável por carregar dados do modelo
	 * para classificação
	 *
	 * @return bool $succcess
	 */
	public function loadModel($name = '')
	{
		if(empty($name))
		{
			trigger_error(__('Classifier model can not be loaded', true), E_USER_ERROR);

			return false;
		}

		$this->contain();

		$_model = $this->find('first', array('conditions' => array('Classifier.name' => $name)));

		$this->_model = unserialize($_model[$this->name]['model']);

		if(empty($this->_model))
		{
			trigger_error(__('Model classifier not loaded', true), E_USER_ERROR);

			return false;
		}

		return $this->_model;
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
				unset($out[$token]);
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
}
?>
