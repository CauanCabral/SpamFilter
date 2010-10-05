<?php
class NaiveBayes extends BaseClassifier
{
	protected $probabilities;

	/**
	 * Gera um model para o classificador
	 *
	 * @param array $trainingSet
	 */
	public function modelGenerate($trainingSet)
	{
		parent::modelGenerate($trainingSet);
		
		// valida a geração de modelos através de folds
		$stats = $this->crossValidation();
		
		// gera o modelo final, baseado em todos os exemplos
		$this->training($trainingSet);

		return true;
	}

	/**
	 * @TODO implementar método para atualização do classificador
	 *
	 */
	public function modelUpdate()
	{
		
	}

	/**
	 * Classifica um conjunto de entradas
	 *
	 * @param array $entries
	 */
	public function classify($entries)
	{
		$classes = array();

		foreach($entries as $t => $entry)
		{
			$isSpam = 0;
			$notSpam = 0;
			
			foreach($entry as $attr => $freq)
			{
				if($freq > 0)
				{
					$isSpam += $this->__p($attr, $freq, 'spam');
					$notSpam += $this->__p($attr, $freq, 'not_spam');
				}
			}

			$classes[$t]['class'] = $isSpam > $notSpam ? 1 : -1;
			$classes[$t]['p'] = $isSpam;
		}

		return $classes;
	}
	
	/**
	 * Faz treinamento do modelo
	 * 
	 * @param array $trainingSet
	 */
	protected function training($trainingSet)
	{
		$this->probabilities = array_fill_keys(array_keys($this->classes), array_fill_keys($this->attributes, 0));

		$classFreq = array_fill_keys(array_keys($this->classes), 0);

		$pr = array();

		// para cada instância
		foreach($trainingSet['entries'] as $t => $x)
		{
			// recupera, do exemplo, a classe correta
			$correctClass = $this->classes[$x['class']];

			// conta a ocorrencia de instâncias com a classe dentro do domínio
			$classFreq[$x['class']]++;

			// cálcula probabilidade de cada atributo
			foreach($x['attributes'] as $attr => $freq)
			{
				if(!isset($pr[$attr][$x['class']]))
					$pr[$attr][$x['class']] = 0;
				
				$pr[$attr][$x['class']] += $freq;
			}
		}

		$pr['spam'] = $classFreq['spam'] / $this->statistics['total'];
		$pr['not_spam'] = $classFreq['not_spam'] / $this->statistics['total'];

		// atualiza probabilidade dos atributos
		foreach($pr as $attr => $freq)
		{
			if(array_key_exists($attr, $this->classes))
				continue;
			
			$this->probabilities['spam'][$attr] = $freq['spam'] / $this->statistics['total'];
			$this->probabilities['not_spam'][$attr] = $freq['not_spam'] / $this->statistics['total'];
		}
		
		return $pr;
	}

	/**
	 * Cálcula a probabilidade do exemplo ser classificado como $class
	 * dado o atributo $attr
	 *
	 * @param array $attr
	 * @param string $class
	 */
	protected function __p($attr, $freq, $class)
	{
		return log($freq) + log($this->probabilities[$class][$attr]);
	}

	public function getConfig()
	{
		return $this->probabilities;
	}
	
	public function optimize($historyLength = 10)
	{
		unset($this->entries);
	}
}
