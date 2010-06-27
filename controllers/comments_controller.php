<?php
class CommentsController extends AppController {

	public $name = 'Comments';
	
	public $helpers = array('Number');

	public function index()
	{
		$this->Comment->recursive = 0;
		$this->set('comments', $this->paginate());
	}

	public function view($id = null)
	{
		if (!$id)
		{
			$this->Session->setFlash(__('Invalid comment', true));
			$this->redirect(array('action' => 'index'));
		}
		
		$this->set('comment', $this->Comment->read(null, $id));
	}

	public function add()
	{
		if (!empty($this->data))
		{
			$this->Comment->create();
			
			if ($this->Comment->save($this->data))
			{
				$this->Session->setFlash(__('The comment has been saved', true));
				$this->redirect(array('action' => 'index'));
			}
			else
			{
				$this->Session->setFlash(__('The comment could not be saved. Please, try again.', true));
			}
		}
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(__('Invalid comment', true));
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->Comment->save($this->data)) {
				$this->Session->setFlash(__('The comment has been saved', true));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The comment could not be saved. Please, try again.', true));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->Comment->read(null, $id);
		}
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid id for comment', true));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->Comment->delete($id)) {
			$this->Session->setFlash(__('Comment deleted', true));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(__('Comment was not deleted', true));
		$this->redirect(array('action' => 'index'));
	}
	
	/**
	 * 
	 */
	public function statistics()
	{
		$this->set('stats', $this->Comment->getStats());
	}
	
	public function testFilter($id)
	{
		$this->autoRender = false;
		
		App::import('Core', 'HttpSocket');
		
		$comment = $this->Comment->read(null, $id);
		
		$socket = new HttpSocket();
		$url = 'http://spamfilter.dottibook/classifiers/isSpam.json';
		
		$class = $socket->get($url, array('message' => $comment['Comment']['content']));
		
		pr($class);
	}
	
	/**
	 * Gera um arquivo Arff com os dados de comentários
	 * armazenados no banco
	 * 
	 * @return void
	 */
	public function exportArff()
	{
		$comments = $this->Comment->find('all');
	}
}