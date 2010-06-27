<?php
class ClassifiersController extends AppController {
	
	public $name = 'Classifiers';
	public $uses = array();
	public $components = array('NaiveBayes');
	
	/**
	 * RESTful action to classify a message
	 * using Navie Bayes algorithm
	 * 
	 * @return json formatted string with response
	 */
	public function isSpam($message = null)
	{	
		if(empty($message))
		{
			$response['errors'][] = __('Você precisa passar uma mensagem como parâmetro', 1);
		}
		else
		{
			$entry = json_decode($message);
			
			if(!isset($entry['message']))
			{
				trigger_error(__('Parâmetro de classificação está mal-formatado.', 1), E_USER_ERROR);
			}
			
			$response['class'] = $this->NaiveBayes->classify($entry['message']);
		}
		
		$this->set(compact('response'));
		
		$this->render('default');
	}
	
	/**
	 * 
	 * @param string $message json formatted
	 * @param enum{not spam, spam} $class
	 * 
	 * @return void or json formatted error message
	 */
	public function update($message = null, $class = 1)
	{	
		if(empty($message))
		{
			$response['errors'][] = __('Você precisa passar ao menos um parâmetro ao método', 1);
		}
		else
		{
			$response = '';
		}
		
		$this->set(compact('response'));
		
		$this->render('default');
	}
}