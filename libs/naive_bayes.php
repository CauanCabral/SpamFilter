<?php
/**
 * Classe que implementa algorítimo NaiveBayes para classificação de 
 * documentos de texto.
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
class NaiveBayes extends BaseClassifier
{
	protected $priors;
	
	protected $likelihoods;

	/**
	 * Gera um model para o classificador
	 *
	 * @param array $trainingSet
	 */
	public function modelGenerate($trainingSet)
	{
		parent::modelGenerate($trainingSet);
		
		// valida a geração de modelos através de folds
		$this->crossValidation();
		
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
	 * @param bool $useDefault
	 * 
	 * @return array $classes
	 */
	public function classify($entries, $useDefault = false)
	{
		$classes = array();

		foreach($entries as $t => $entry)
		{
			$classes[$t][$this->classField] = 0;
			$classes[$t]['p'] = null;
			
			$p = $this->__p($entry);
			
			foreach($p as $className => $prob)
			{
				if($classes[$t]['p'] == null || $classes[$t]['p'] < $prob)
				{
					$classes[$t][$this->classField] = $this->classes[$className];
					$classes[$t]['p'] = $prob;
				}
			}
		}
		
		if($useDefault)
		{
			foreach($classes as $t => $entry)
			{
				if(abs($entry['p']) <= 1)
					$classes[$t][$this->classField] = $this->defaultClass;
			}
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
		parent::training($trainingSet);
		
		// probabilidade de ocorrência de cada classe (a priori)
		$this->priors = array_fill_keys(array_keys($this->classes), 0);
		
		// probabilidade de ocorrência de cada atributo em uma classe
		$this->likelihoods = array_fill_keys(array_keys($this->classes), array_fill_keys($this->attributes, 0));

		// probabilidade posteriori
		$this->probabilities = array_fill_keys($this->attributes, array_fill_keys(array_keys($this->classes), 0));

		// para cada instância
		foreach($trainingSet['entries'] as $t => $x)
		{
			// recupera, do exemplo, a classe correta
			$correctClass = $this->classes[$x[$this->classField]];

			// conta a ocorrencia de instâncias com a classe dentro do domínio
			$this->priors[$x[$this->classField]]++;

			// conta frequencia dos atributos para calcular likelihood
			foreach($x['attributes'] as $attr => $freq)
			{
				$this->likelihoods[$x[$this->classField]][$attr] += $freq;
			}
		}
		
		// calcula a probabilidade a priori
		foreach($this->classes as $className => $classCod)
		{
			$this->priors[$className] = $this->priors[$className] / $this->statistics['total'];
		}
		
		// calcula likelihood de cada atributo
		foreach($this->likelihoods as $className => $attributes)
		{
			foreach($attributes as $attrs => $freq)
			{
				$this->likelihoods[$className][$attr] = $freq / $this->priors[$className];
			}
		}
	}

	/**
	 * Cálcula a probabilidade do exemplo ser classificado como $class
	 * dado o atributo $attr
	 *
	 * @param array $attr
	 * @param string $class
	 */
	protected function __p($entry)
	{
		// inicializa probabilidade com termo neutro da soma
		$p = array_fill_keys(array_keys($this->classes), 1);
		
		foreach($entry as $attr => $freq)
		{
			if($freq > 0)
			{
				foreach($p as $className => $prob)
				{
					$tmp = ($freq * $this->likelihoods[$className][$attr] + 1) / ($this->priors[$className] + 1);
					
					$p[$className] *= $tmp;
				}
			}
		}
		return $p;
	}

	public function getConfig()
	{
		return $this->likelihoods;
	}
	
	public function optimize($historyLength = 10)
	{
		unset($this->entries);
	}
}
