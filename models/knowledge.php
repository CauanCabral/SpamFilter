<?php
class Knowledge extends AppModel
{
	public $name = 'Knowledge';
	
	public $validate = array(
		'spam' => array(
			'boolean' => array(
				'rule' => array('boolean')
			),
		),
		'comment_id' => array(
			'numeric' => array(
				'rule' => array('numeric')
			),
		),
	);

	public $belongsTo = array(
		'Comment' => array(
			'className' => 'Comment',
			'foreignKey' => 'comment_id'
		)
	);
	
	/**
	 * ER responsável pela quebra das mensagens em tokens
	 * 
	 * @var $_tokenSeparator string
	 */
	protected $_tokenSeparator = '/\s|\[|\]|<|>|\?|;|\"|\'|\=|\/|\(|\)|!|&/';
	
	/**
	 * Retorna um ou todos as entradas na tabela de conhecimento
	 * 
	 * @param int $id optional - ID da entrada a ser carregada, caso não seja passada
	 * o método retorna todas as entradas
	 * 
	 * @return array $knowledges
	 */
	public function get($id = null)
	{
		if($id == null)
		{
			$knowledges = $this->find('all');
		}
		else
		{
			$knowledges = $this->find('all', array('conditions' => array('Knowledge.id' => $id)));
		}
		
		foreach($knowledges as &$k)
		{
			$k['Knowledge']['content'] = unserialize($k['Knowledge']['content']);
		}
		
		return $knowledges;
	}
	
	/**
	 * Salva uma nova entrada na tabela de 'conhecimento'
	 * 
	 * @param array $data Dados que serão salvos
	 * @param bool $onlyConvert Caso true, apenas converte o texto
	 * em array de atributos
	 * 
	 * @return array|bool Em caso de sucesso, é retornado o array
	 * contendo os dados salvos, caso haja falha, o booleano false é
	 * retornado.
	 */
	public function add($data, $onlyConvert = false)
	{
		$attributes = $this->__identifyAttributes($data['content']);
		
		if(!$onlyConvert)
		{
			$data['content'] = serialize($attributes);
			
			if(!$this->save($data))
			{
				return false;
			}
		}
		
		return $attributes;
	}
	
	/**
	 * Método que retorna uma estatística simples em cima
	 * da base de conhecimento
	 * 
	 * @return array $stats
	 */
	public function getStats()
	{
		$data = $this->find('all', array('fields' => array('spam')));
		
		$stats = array(
			'spam' => 0,
			'total' => 0
		);
		
		foreach($data as $c)
		{
			$stats['total']++;
			
			$stats['spam'] += $c[$this->name]['spam'];
		}
		
		return $stats;
	}
	
	/**
	 * Separa a string de entrada em Tokens
	 *
	 * @param string $entry Conteúdo de entrada
	 *
	 * @return array $out Array de tokens identificados
	 */
	protected function __identifyAttributes($entry)
	{
		$tokens = preg_split($this->_tokenSeparator, $entry, null, PREG_SPLIT_NO_EMPTY);

		$out = array('links_count' => 0);

		foreach($tokens as $token)
		{
			$t = $this->__unifyString($token);

			if( mb_strlen($t) < 3 )
				continue;

			// contagem de links
			if(preg_match('/^www_/', $t) || preg_match('/^http:/', $t))
			{
				$out['links_count']++;
			}
			
			if(isset($out[$t]))
			{
				$out[$t]++;
			}
			else
			{
				$out[$t] = 1;
			}
		}
		
		// remove os tokens com pouca presença
		foreach($out as $token => $freq)
		{
			if($freq < 3)
			{
				unset($out[$token]);
			}
		}

		return $out;
	}

	/**
	 * Transforma a string de entrada em lower-case e substitui caracteres especiais
	 * (incluindo acentos) em correspondentes ASCII
	 *
	 * @param string $s String de entrada
	 *
	 * @return string $out String de entrada com caracteres especiais substituidos por
	 * correspondentes ASCII
	 */
	private function __unifyString($s)
	{
		$out = mb_strtolower($s, 'UTF-8');
		$out = Inflector::slug($out);

		return $out;
	}
}
?>