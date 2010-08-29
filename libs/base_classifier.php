<?php
class BaseClassifier
{
	public $name;

	public $attributes = array();

	public $entries = array();

	public $classField = 'class';

	public function  __construct($name)
	{
		$this->name = $name;
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
	 * @param int $folds
	 * @param bool balanced
	 */
	protected function crossValidation($folds = 10, $balanced = true)
	{

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
			$balance[$cls] = bc_div($freq, $total, 5);
		}

		return $balance;
	}

}