<?php
class CommentsController extends AppController
{

	public $name = 'Comments';
	
	/**
	 * 
	 */
	public function add()
	{
		if(!empty($this->data))
		{
			$this->Comment->create();
			
			if($this->Comment->save($this->data))
			{
				$knowledge = array(
					'content' => $this->data['Comment']['content'],
					'spam' => $this->data['Knowledge']['spam'],
					'comment_id' => $this->Comment->id
				);
				
				if($this->Comment->Knowledge->add($knowledge) !== false)
				{
					$this->Session->setFlash(__('Comentário salvo', true));
					$this->redirect(array('controller' => 'classifiers', 'action' => 'tests', 'stats'));
				}
			}
			
			$this->Session->setFlash(__('The comment could not be saved. Please, try again.', true));
		}
	}
}