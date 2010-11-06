<?php
/**
 * Classe base para definição de classificadores.
 * 
 * Implementa interfaces e métodos genéricos úteis para uma grande
 * gama de classificadores.
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
class BaseClassifier
{
	/**
	 * Atributo contendo o nome classificador
	 * Utilizado para diferenciar classificadores persistidos no
	 * banco de dados
	 * 
	 * @var string
	 */
	public $name;

	/**
	 * Atributo contendo todos os atributos verificados
	 * pelo algorítimo ao classificar uma instância
	 * 
	 * @var array
	 */
	public $attributes;

	/**
	 * Atributo contendo todos os atributos utilizados
	 * pelo classificador ao gerar seu modelo
	 * 
	 * @var array
	 */
	public $entries;

	/**
	 * Atributo com o campo analisado e usado pelo
	 * classificador para treinar e atribuir classes
	 * às instâncias
	 * 
	 * @var string
	 */
	public $classField = 'class:';

	/**
	 * Atributo com as classes utilizadas
	 * no classificador, onde o índice é sua representação
	 * nominal, enquanto o valor é sua representação numérica
	 * 
	 * @var array
	 */
	public $classes = array(
		'spam' => 1,
		'not_spam' => -1
	);
	
	/**
	 * Atributo que define o valor da
	 * classe padrão, utilizada quando não há grande
	 * certeza ao atribuir uma classe à uma instância
	 * 
	 * Seu valor deve ser igual a um dos possíveis valores
	 * do atributo $classes
	 * 
	 * @var string
	 */
	public $defaultClass = 'not_spam';
	
	/**
	 * Atributo contendo estatísticas relacionadas
	 * ao classificador
	 * 
	 * @var array
	 */
	public $statistics;

	/**
	 * Construtor do classificador
	 * 
	 * @param string $name
	 * @param int $precision
	 */
	public function __construct($name, $precision = 4)
	{
		$this->name = $name;

		bcscale($precision);
		
		$this->__init();
	}
	
	/**
	 * Método responsável pela inicialização dos atributos do
	 * classificador
	 * 
	 * @return void
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
	 * @param bool success
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
	 * Atualiza o classificador
	 * 
	 * Parâmetros dependem da classe que extende está (do algorítimo utilizado)
	 * 
	 * @return bool success
	 */
	public function modelUpdate()
	{
		return false;
	}

	/**
	 * Classifica um conjunto de entradas e retorna
	 * um array com as classes aplicadas.
	 * Precisa ser implementado em cada classe filha
	 *
	 * @param array $entries
	 * @param bool $useDefault
	 * 
	 * @return array $classes
	 */
	public function classify($entries, $useDefault = false)
	{
		return false;
	}

	/**
	 * Implementação da validação cruzada, de forma genérica
	 *
	 * @param int $num_folds define a quantidade de folds que serão criados
	 * @param bool balanced define se a validação cruzada será "stratified" ou convencional,
	 * isto é, se a proporção de classes será observada na criação dos folds.
	 */
	public function crossValidation($num_folds = 10, $balanced = true)
	{
		// calcula o número de instâncias em cada fold
		$parts_size = floor($this->statistics['total']/$num_folds);

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
					$b = bcdiv($fold['counter'][$entry[$this->classField]], $parts_size, 5);

					// caso a classe do exemplo atual não esteja 'saturado' no fold, adiciona ele ao fold
					if($b <= $balance[$entry[$this->classField]])
					{
						$fold['entries'][] = $entry;

						$fold['counter'][$entry[$this->classField]]++;

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

		// efetuar a validação cruzada em sí
		for($i = 0, $j = 1; $i < $num_folds; $i++)
		{
			$this->training($folds[$j]);
			
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
			
			$j = ($j + 1) % $num_folds;
		}
		
		// cálculo da taxa de acerto
		$sd = array();
		
		foreach($stats as $tests)
		{
			$result = array('asserts' => 0, 'total' => 0);
			
			foreach($tests as $k => $info)
			{
				if($info[$this->classField] == $info['correct'])
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
	 * Efetua o treinamento do modelo, de acordo com o algorítimo
	 * da classe (NaiveBayes, PA, Perceptron...) 
	 * 
	 * @param array $trainingSet
	 * @return void
	 */
	protected function training($trainingSet) {}

	/**
	 * Identifica a proporção das classes no modelo
	 * atual e retorna um array com o percentual de cada
	 * uma
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
	
	/**
	 * Método auxliar para escrita de arquivos
	 * 
	 * @param string $name
	 * @param array $data
	 * 
	 * @return bool success
	 */
	private function __writeFile($name, $data)
	{
		$file = new SplFileObject($name, "w");
		$written = $file->fwrite($data);
		chmod($name, 0777);
		
		return ($written !== null);
	}

	/**
	 * Printa informações sobre o classificador
	 * 
	 * @return void
	 */
	public function printStats()
	{
		echo 'Total de instâncias: ', $this->statistics['total'], "\n Número de acertos: ", $this->statistics['asserts'], "\n Desvio Padrão: ", $this->statistics['devianation'];
	}

}