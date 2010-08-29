<?php
class NaiveBayes extends BaseClassifier
{
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

		/**
		 * @TODO implementar a inferencia do modelo baseado no NaiveBayes
		 */

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
	private function __writeFile($name, $data)
	{
		$file = new SplFileObject($name, "w");
		$written = $file->fwrite($data);
		chmod($name, 0777);

		return ($written !== null);
	}
}
