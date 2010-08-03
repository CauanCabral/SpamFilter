<?php
App::import('Sanitize');

/**
 * Classe/serviço RESTful para classificação de mensagens
 * utilizando algoritimo Naive Bayes.
 * 
 * @author Cauan Cabral
 *
 */
class ClassifiersController extends AppController {
	
	public $name = 'Classifiers';
	public $uses = array();
	
	/**
	 * Componente que faz a ligação do controller
	 * com um classificador NaiveBayes externo.
	 * 
	 * @var NaiveBayes
	 */
	public $components = array('NaiveBayes');
	
	/**
	 * Ação para classificação de uma mensaagem (POST)
	 * usando o algoritimo Naive Bayes.
	 *  
	 * Caso receba o header GET com uma mensagem, retorna
	 * a classe para esta mensagem.
	 * Caso rerceba o header PUT com uma mensagem e uma classe,
	 * então salva registro para futura atualização do classificador.
	 * 
	 * @param message Mensagem que deve ser classificada
	 * 
	 * @return classe da mensagem ou true em caso de atualização do filtro
	 * bem sucedidade.
	 */
	public function isSpam()
	{
		/*
		 * Tratamento de ação PUT (update)
		 */
		if($this->RequestHandler->isPost())
		{
			if(empty($this->data))
			{
				$response['errors'][] = __('Você precisa passar uma mensagem como parâmetro', 1);
			}
			else
			{
				$entry = json_decode($this->data);
				
				if(!isset($entry->message))
				{
					trigger_error(__('Parâmetro de classificação está mal-formatado.', 1), E_USER_ERROR);
				}
				
				$response = $this->NaiveBayes->classify(array(array('class' => '?', 'content' => $entry->message)));
			}
		}
		
		$this->set(compact('response'));
		
		$this->layoutPath = '/';
		$this->layout = 'blank';
		
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