<?php

require_once('base_classifier.php');

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
		if(!is_array($trainingSet) || empty($trainingSet))
		{
			trigger_error(__('Training set must be a not empty array', true), E_USER_ERROR);
		}

		$this->attributes = $trainingSet['attributes'];
		$this->entries = $trainingSet['entries'];

		// inicializa W com zero para todos os atributos, na primeira rodada
		$this->w[] = array_fill_keys($this->attributes, 0);

		// para cada instância
		foreach($this->entries as $t => $x)
		{
			// atualiza classificador
			$this->modelUpdate($t, $x);
		}

		return true;
	}

	/**
	 *
	 * @param int $t Rodada do classificador
	 * @param array $x Exemplo
	 */
	public function modelUpdate($t, $x)
	{
		// calcula produto interno dos pesos atuais no classificador e do novo exemplo
		$dot = $this->__innerProduct($this->w[$t], $x['attributes']);

		$dotNorm = $this->__norm($dot);

		$norm = $this->__norm($x['attributes']);

		// atribui uma classe para o novo exemplo
		$y = $this->__sign( $dotNorm );

		// recupera, do exemplo, a classe correta
		$correctClass = $x['class'] == 'spam' ? 1 : -1;

		// cálcula e guarda a margem de precisão da predição
		$l = $this->__sufferLoss($y, $dotNorm);

		// cálcula o parâmetro τ utilizado na atualização do classificador
		$tau = $this->__tau($l, $norm);

		// atualiza o classificador para o próximo exemplo
		foreach($x['attributes'] as $k => $v)
		{
			$aux = $l*$tau*$v;

			$this->w[$t+1][$k] = $this->w[$t][$k] + $aux;
		}

		pr(array('correta' => $correctClass, 'predito' => $y, 'suffer_loss' => $l));
	}

	/**
	 * Classifica um conjunto de entradas
	 *
	 * @param array $entries
	 */
	public function classify($entries)
	{
		
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
			return 0;

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
	 * @return <type>
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
}