<?php
/**
 * Classe que implementa algorítimo PA(Passive-Agressive) para classificação
 * de documentos de texto.
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
class Pa extends BaseClassifier
{
	/**
	 *
	 * @var pesos para os parâmetros 
	 */
	protected $w = array();

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
	 * @see libs/base_classifier.php#
	 * 
	 * @param array $trainingSet
	 */
	protected function training($trainingSet)
	{
		parent::training($trainingSet);
		
		// inicializa W com zero para todos os atributos, na primeira rodada
		$this->w[0] = array_fill_keys($this->attributes, 0);

		// para cada instância
		foreach($trainingSet['entries'] as $t => $x)
		{
			// recupera, do exemplo, a classe correta
			$correctClass = $this->classes[$x[$this->classField]];

			// atualiza classificador
			$this->modelUpdate($x['attributes'], $correctClass, $t);
		}
	}

	/**
	 * Método que implementa a atualização do classificador PA
	 * 
	 * @param $x Instâcia
	 * @param $correctClass Classe correta para a instância $x
	 * @param $t Posição no 'tempo' em que o classificador deve ser atualizado
	 */
	public function modelUpdate($x, $correctClass = null, $t = null)
	{
		// se $t não é passado
		if($t == null)
		{
			// assume que foi a última rodada salva
			$t = count($this->w) - 1;
		}
		// caso contrário, remove todos os índices maiores que $t e 'recomeça' o classificador do ponto $t
		else
		{
			for($i = count($this->w) - 1; $i > $t; --$i)
			{
				// remove posição $i do classificador
				unset($this->w[$i]);
			}
		}

		// calcula produto interno dos pesos atuais no classificador e do novo exemplo
		$dot = $this->__innerProduct($this->w[$t], $x);

		$dotNorm = $this->__norm($dot);

		$norm = $this->__norm($x);

		// prediz uma classe para o novo exemplo
		$y = $this->__sign( $dotNorm );

		// se não for passada a classe correta, considera a classe predita como correta
		if($correctClass == null)
		{
			$correctClass = $y;
		}

		// cálcula e guarda a margem de precisão da predição
		$l = $this->__sufferLoss($correctClass, $dotNorm);

		// cálcula o parâmetro τ utilizado na atualização do classificador
		$tau = $this->__tau($l, $norm);

		// atualiza o classificador para o próximo exemplo
		foreach($x as $k => $v)
		{
			$aux = $l*$tau*$v;

			$this->w[$t+1][$k] = $this->w[$t][$k] + $aux;
		}

		/*
		 * @FIXME teste meu
		 */
		//if($l > 0.6) $y = -$y;

		return array($this->classField => $y, 'p' => $l);
	}

	/**
	 * Classifica um conjunto de entradas
	 *
	 * @param array $entries
	 */
	public function classify($entries)
	{
		$classes = array();

		// para cada instância
		foreach($entries as $t => $x)
		{
			// atualiza classificador
			$classes[$t] = $this->modelUpdate($x);
		}

		return $classes;
	}

	/**
	 * Verifica o sinal de $value e retona:
	 *  1 caso positivo
	 *  0 caso seja zero
	 *  -1 caso negativo
	 *
	 * @param int $value
	 * @return int
	 */
	protected function __sign($value)
	{
		if($value > 0)
			return 1;

		if($value == 0)
			return 1;

		if($value < 0)
			return -1;
	}

	/**
	 * Método auxiliar que realiza o produto escalar entre
	 * dois vetores e retorna o vetor resultante.
	 *
	 * @param array $vetA
	 * @param array $vetB
	 * @return array
	 */
	protected function __innerProduct($vetA, $vetB)
	{
		if( !is_array($vetA) || !is_array($vetB) || count($vetA) != count($vetB) )
		{
			trigger_error(__('Inner product need receive two arrays of the same length', true), E_USER_ERROR);

			return array();
		}

		$product = array();
		
		foreach($vetA as $k => $v)
		{
			$product[$k] = $v * $vetB[$k];
		}

		return $product;
	}

	/**
	 * Cálcula a norma de um vetor
	 * 
	 * @param array $vector
	 * 
	 * @return float
	 */
	protected function __norm($vector)
	{
		if( !is_array($vector) || empty($vector))
		{
			trigger_error(__('Norm can be calculated only on non empty array', true), E_USER_ERROR);

			return null;
		}

		$norm = 0;

		foreach($vector as $value)
		{
			$norm += $value*$value;
		}

		return sqrt($norm);
	}

	/**
	 * Calcula e retorna a margin de precisão para classe predita
	 *
	 * @param int $y
	 * @param float $dotProduct
	 * 
	 * @return float
	 */
	protected function __sufferLoss($y, $dotProduct)
	{
		$margin = $y*$dotProduct;

		if($margin >= 1)
			return 0;
		else
			return (1 - $margin);
	}

	/**
	 * Cálcula o valor de τ para função de atualização
	 * 
	 * @param float $l - Perda calculada
	 * @param float $x - Norma correspondente ao exemplo
	 * @param int $type - Tipo do PA: 0, 1 e 2. 0 é o valor default
	 * @param float $C - Deve ser positivo (necessário apenas para os tipos 1 e 2 do PA)
	 * 
	 * @return float $t - o valor 'tau'
	 */
	protected function __tau($l, $x, $type = 0, $C = 1)
	{
		if($x == 0)
		 $x = 1;

		switch ($type)
		{
			case 1: // PA-I
				$t = $l / pow($x, 2);
				
				$t = ($t < $C) ? $t : $C; // $t recebe o que for menor: $C ou $t
				break;
			
			case 2: // PA-II
				$t = $l / ( pow($x, 2) + 1 / 2*$C );
				break;

			default: // PA
				$t = $l / pow($x, 2);
				break;
		}

		return $t;
	}

	/**
	 * Método auxiliar para impressão do modelo
	 * 
	 * @param $all - parâmetro que define se serão impressas
	 * todas as entradas do modelo ou apenas a última
	 * 
	 * @return void
	 */
	public function printModel($all = false)
	{
		if($all)
		{
			foreach($this->w as $x)
			{
				echo implode(';', $x), "\n";
			}
		}
		else
		{
			echo implode(';', $this->w[count($this->w) - 1]);
		}
	}

	/**
	 * Retorna o modelo
	 * 
	 * @return array 
	 */
	public function getConfig()
	{
		$formatted = array();

		foreach($this->w as $t => $x)
		{
			$formatted[$t] = $x;
		}

		return $this->w;
	}
	
	/**
	 * Remove dados não essenciais do classificador afim
	 * de otimizar seu processo de serialização, persistência
	 * e recuperação
	 * 
	 * @param int $historyLength
	 */
	public function optimize($historyLength = 10)
	{
		$total = count($this->w);
		
		if($total > $historyLength)
		{
			$begin = $total - $historyLength;
			$this->w = array_slice($this->w, $begin);
		}
		
		unset($this->entries);
	}
}