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

	public function tests($type = 'pa', $action = 'default', $model_id = 1, $comments = 'all')
	{
		$type = Inflector::camelize($type);

		switch($action)
		{
			case 'build':
				$entries = $this->__loadComments($comments);

				$this->Classifier->buildModel('Spam', $entries, $type);
				$this->set('stats', $this->Classifier->modelReport());
				
				break;
			case 'classify':
				$entries = $this->__loadComments($comments, $comments);

				$this->Classifier->loadModel($model_id);
				$this->set('classifieds', Set::merge($entries, $this->Classifier->classify($entries)));
				$this->set('ids', $this->Comment->find('list', array('fields' => array('id'))));
				break;
			case 'info':
				$this->Classifier->loadModel($model_id);

				$this->set('stats', $this->Classifier->modelReport());
				break;
			default:
				$this->Session->setFlash('Clique em uma das opções do menu ao lado');
				break;
		}
	}

	private function __loadComments($param = 'all')
	{
		$this->loadModel('Comment');

		if(is_numeric($param))
		{
			$comments = $this->Comment->find('all', array('conditions' => array('Comment.id' => $param)));
		}
		else
		{
			$comments = $this->Comment->find('all');
		}

		$entries = array();

		foreach($comments as $comment)
		{
			$entries[] = array(
				'class' => $comment['Comment']['spam'] ? 'spam' : 'not_spam',
				'content' => $comment['Comment']['content']
			);
		}

		return $entries;
	}
}