<?php
App::import('Sanitize');

/**
 * Classe/serviço RESTful para classificação de mensagens
 * utilizando algoritimos de aprendizado de máquina, tais
 * como o Naive Bayes.
 * 
 * Projeto desenvolvido para o trabalho final de graduação em Bacharelado
 * em Ciência da Computação, acadêmico Cauan Cabral.
 * 
 * @author Cauan Cabral
 * @link http://cauancabral.net
 * @copyright Cauan Cabral @ 2010
 * @license MIT License
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
	public $components = array(
		'NaiveBayes' => array(
			'binaryPath' => '/usr/local/bin/'
		)
	);
	
	/**
	 * 
	 * @var array HelperCollection
	 */
	public $helpers = array('Number');
	
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
		$response = array();
		
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
	 * @param int $model_id
	 */
	public function classify()
	{
		if(!empty($this->data))
		{
			$entries = array();
	
			$entries[] = array(
				'class' => $this->data['Classifier']['spam'] ? 'spam' : 'not_spam',
				'content' => $this->data['Classifier']['content']
			);
	
			$this->Classifier->loadModel($this->data['Classifier']['classifier']);
			
			$this->set('classifieds', Set::merge($entries, $this->Classifier->classify($entries)));
		}
		
		$this->set('classifiers', $this->Classifier->find('list', array('fields' => array('id', 'alias'))));
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
		$response = array();
		
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

	/**
	 * 
	 * @param string $type
	 * @param string $action
	 * @param int $model_id
	 * @param all $comments
	 */
	public function tests($action = 'default', $type = 'pa', $model_id = 1, $comments = 'all')
	{
		$this->set('type', $type);
		$type = Inflector::camelize($type);

		switch($action)
		{
			case 'build':
				$entries = $this->__loadKnowledges();

				$this->Classifier->buildModel('Spam', $entries, $type, false);
				$this->set('info', $this->Classifier->modelReport());
				break;
				
			case 'classify':
				$entries = $this->__loadComments($comments);

				$this->Classifier->loadModel($model_id);
				
				$this->set('classifieds', Set::merge($entries, $this->Classifier->classify($entries)));
				$this->set('ids', $this->Comment->find('list', array('fields' => array('id'))));
				break;
				
			case 'info':
				$this->Classifier->loadModel($model_id);

				$this->set('info', $this->Classifier->modelReport());
				$this->set('classifiers', $this->Classifier->find('list', array('fields' => array('id'))));
				break;
				
			case 'stats':
				$this->loadModel('Knowledge');
				
				$this->set('stats', $this->Knowledge->getStats());
				
				break;
			default:
				$this->Session->setFlash('Clique em uma das opções do menu ao lado');
				break;
		}
	}
	
	/**
	 * Gera arquivos Arff e Tab para uso com WEKA  e implementações
	 * do Borgelt do NaiveBayes
	 * 
	 * @return void
	 */
	public function export()
	{
		$this->autoRender = false;
		
		$this->loadModel('Knowledge');
		
		$knowledges = $this->Knowledge->find('all');
		$entries = array();
		
		foreach($knowledges as $knowledge)
		{
			$entries[] = array(
				'class' => $knowledge['Knowledge']['spam'] ? 'spam' : 'not_spam',
				'content' => unserialize($knowledge['Knowledge']['content'])
			);
		}
		
		$filename = $this->NaiveBayes->export($entries);
		
		$this->view = 'Media';
		
		$params = array(
			'id' => $filename,
			'name' => 'arquivos.tar',
			'download' => true,
			'extension' => 'gz',
			'path' => './'
		);
		
		$this->set($params);
		
		$this->render();
	}
	
	/**
	 * Temp method
	 */
	public function import()
	{
		$this->autoRender = false;
		
		App::import('Lib', 'Import');
		
		$i = new Import();
		
		$i->run();
	}

	/**
	 * Método auxiliar para carregar base de comentários
	 * Apenas para testes
	 * 
	 * @param $param string|int id de comentário ou a string 'all'
	 * 
	 * @return void
	 */
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
				'content' => $comment['Comment']['content'] 
			);
		}

		return $entries;
	}
	
	/**
	 * Método auxiliar para carregar base de conhecimento
	 * 
	 * @return void
	 */
	private function __loadKnowledges()
	{
		$this->loadModel('Knowledge');
		
		$knowledges = $this->Knowledge->find('all');
		
		$entries = array();
		
		foreach($knowledges as $knowledge)
		{
			$entries[] = array(
				'class' => $knowledge['Knowledge']['spam'] ? 'spam' : 'not_spam',
				'content' => unserialize($knowledge['Knowledge']['content'])
			);
		}
		
		return $entries;
	}
}