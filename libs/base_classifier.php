<?php
class BaseClassifier
{
	public $name;

	public $attributes;

	public $entries;

	public $classField = 'class';

	public $classes = array(
		'spam' => 1,
		'not_spam' => -1
	);

	public $statistics;

	public function __construct($name, $precision = 4)
	{
		$this->name = $name;

		bcscale($precision);
		
		$this->__init();
	}
	
	/**
	 * 
	 */
	protected function __init()
	{
		$this->attributes = array();
		
		$this->entries = array();
		
		$this->statistics = array(
			'asserts' => 0,
			'total' => 0,
			'assertion_ratio' => 0,
			'devianation' => 0
		);
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

		$this->attributes = $trainingSet['attributes'];
		$this->entries = $trainingSet['entries'];

		$this->statistics['total'] = count($this->entries);

		return false;
	}

	/**
	 *
	 */
	public function modelUpdate()
	{
		return false;
	}

	/**
	 * Classifica um conjunto de entradas
	 *
	 * @param array $entries
	 */
	public function classify($entries)
	{
		return false;
	}

	/**
	 * Implementação da validação cruzada
	 *
	 * @param int $num_folds
	 * @param bool balanced
	 */
	public function crossValidation($num_folds = 10, $balanced = true)
	{
		// calcula o número de instâncias em cada fold
		$parts_size = ceil($this->statistics['total']/$num_folds);

		if($balanced)
		{
			// calcula o balanço que será usado nos folds
			$balance = $this->classesBalance();

			// inicializa array com folds
			$folds = array_fill(0, $num_folds, array('entries' => array(), 'total' => 0, 'counter' => array_fill_keys(array_keys($balance), 0)));
		}
		else
		{
			// inicializa array com folds
			$folds = array_fill(0, $num_folds, array('entries' => array(), 'total' => 0));
		}

		// gera os folds
		foreach($this->entries as $j => $entry)
		{
			foreach($folds as $t => &$fold)
			{
				// caso fold já esteja cheio, pula para próximo fold
				if($fold['total'] == $parts_size)
					continue;

				// lógica usada para folds balanceados
				if($balanced)
				{
					$b = bcdiv($fold['counter'][$entry['class']], $parts_size, 5);

					// caso a classe do exemplo atual não esteja 'saturado' no fold, adiciona ele ao fold
					if($b <= $balance[$entry['class']])
					{
						$fold['entries'][] = $entry;

						$fold['counter'][$entry['class']]++;

						$fold['total']++;
						
						break;
					}
				}
				// lógica usada em folds desbalanceados
				else
				{
					$fold['entries'][] = $entry;

					$fold['total']++;
				}
			}
		}
		
		// guarda as estatísticas de cada model
		$stats = array();

		// efetuar a validação cruzda em sí
		for($i = 0; $i < $num_folds; $i++)
		{
			for($j = 0; $j < $num_folds; $j++)
			{
				// pula o fold que será usado para teste
				if($j == $i)
					continue;
				
				$this->training($folds[$j]);
			}
			
			$toClassify = array();
			$classes = array();
			
			foreach($folds[$i]['entries'] as $t => $entry)
			{
				$toClassify[] = $entry['attributes'];
				$classes[] = $this->classes[$entry[$this->classField]];
			}
			
			$result = $this->classify($toClassify);
			
			foreach($result as $key => $info)
			{
				$result[$key]['correct'] = $classes[$key];
			}
			
			$stats[] = $result;
		}
		
		// cálculo da taxa de acerto
		$sd = array();
		
		foreach($stats as $tests)
		{
			$result = array('asserts' => 0, 'total' => 0);
			
			foreach($tests as $k => $info)
			{
				if($info['class'] == $info['correct'])
				{
					$result['asserts']++;
					$this->statistics['asserts']++;
				}
				
				$result['total']++;
			}
			
			$sd[] = ($result['asserts'] / $result['total']);
		}
		
		$this->statistics['assertion_ratio'] = $this->statistics['asserts']/$this->statistics['total'];
		
		// cálculo do desvio padrão (fonte: http://pt.wikipedia.org/wiki/Desvio_padrão )
		foreach($sd as $info)
		{
			$this->statistics['devianation'] += pow($info - $this->statistics['assertion_ratio'], 2);
		}
		
		$this->statistics['devianation'] /= ($num_folds - 1);
		
		$this->statistics['devianation'] = sqrt($this->statistics['devianation']);
	}
	
	/**
	 * Deve ser implementado em cada subclasse
	 * Efetua o treinamento do modelo
	 * 
	 * @param array $trainingSet
	 */
	protected function training($trainingSet)
	{
		return false;
	}

	/**
	 * Identifica a proporção das classes no modelo
	 * atual
	 *
	 * @return array
	 */
	protected function classesBalance()
	{
		if(empty($this->entries))
		{
			trigger_error(__('You need generate model first', true), E_USER_ERROR);

			return array();
		}

		$balance = array();
		$total = 0;

		foreach($this->entries as $entry)
		{
			$total++;
			
			if(isset($balance[$entry[$this->classField]]))
			{
				$balance[$entry[$this->classField]]++;
			}
			else
			{
				$balance[$entry[$this->classField]] = 1;
			}
		}

		foreach($balance as $cls => $freq)
		{
			$balance[$cls] = bcdiv($freq, $total, 5);
		}

		return $balance;
	}

	public function printStats()
	{
		echo 'Total de instâncias: ', $this->statistics['total'], "\n Número de acertos: ", $this->statistics['asserts'];
	}

}