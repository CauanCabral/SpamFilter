<?php
App::import('Sanitize');

App::import('Lib', 'Folder');

class Import
{
	protected $_tokenSeparator = '/\s|\[|\]|<|>|\?|;|\"|\'|\=|\/|\(|\)|!|&/';
	
	/**
	 * Importa dados
	 */
	public function run()
	{
		$path = '..' . DS . 'spamcorpus' . DS;
		
		$f = new Folder($path);
		
		$corpus = $f->findRecursive('[0-9a-zA-Z]+\.xml$');
		
		$classes = explode("\n", file_get_contents('../blog-spam-assessments.txt'));
		$indexed = array();
		
		unset($classes[0]);
		
		foreach($classes as $k => &$file)
		{
			if(empty($file))
				continue;
			
			$aux = preg_split('/\s/', $file, 0, PREG_SPLIT_NO_EMPTY);
			
			preg_match('/[0-9a-zA-Z]+\.xml$/', $aux[0], $filenames);
			
			$filename = $filenames[0];
			
			$indexed[$filename][$aux[1]] = $aux[2];
		}
		
		$formatted = array();
		
		foreach($corpus as $filepath)
		{
			preg_match('/[0-9a-zA-Z]+\.xml$/', $filepath, $filenames);
			
			$filename = $filenames[0];
			
			$x = simplexml_load_file($filepath);
			
			foreach($x->comment as $node)
			{
				$content = str_replace('\n', '', Sanitize::clean((string)$node));
				$post_author = (string)$node->attributes()->post;
				$author = $this->__extractAuthor($post_author);
				$author_url = $this->__extractUrl($post_author);
				$spam =$indexed[$filename][(int)$node->attributes()->id];
				
				$formatted[] = array(
					'Comment' => array(
						'content' => $content,
						'author_url' => $author_url,
						'author' => $author
					),
					'Knowledge' => array(
						'content' => serialize($this->__identifyAttributes($content)),
						'spam' => $spam 
					)
				);
			}
		}
		
		App::import('Model', 'Knowledge');
		
		$Knowlodge = new Knowledge();
		
		foreach($formatted as $entry)
		{
			$Knowledge->Comment->create();
			
			if($saved = $Knowledge->Comment->save($entry['Comment']))
			{
				$entry['Knowledge']['comment_id'] = $Knowledge->Comment->id;
				
				$Knowledge->create();
				
				if(!$Knowledge->save($entry['Knowledge']))
					pr('Erro ao salvar knowledge');
			}
			else
			{
				pr('Erro ao salvar comment');
			}
		}
		
		pr('Salvo com sucesso');
	}
	
	protected function __extractAuthor($post)
	{
		$s = strip_tags($post);
		
		$init_author = 11;
		
		$end_author = strpos($s, " at ") - $init_author;
		
		if($end_author < 0)
			$end_author = strpos($s, " on ") - $init_author;
		
		return substr($s, $init_author, $end_author);
	}
	
	protected function __extractUrl($link)
	{
		$init = strpos($link, 'href="') + 6;
		$url = '';
		
		for($i = $init, $c = strlen($link); $i < $c; $i++)
		{
			if($link[$i] == '"')
				break;
			
			$url .= $link[$i];
		}
		
		return $url;
	}
	
	protected function __identifyAttributes($entry)
	{
		$tokens = preg_split($this->_tokenSeparator, $entry, null, PREG_SPLIT_NO_EMPTY);

		$out = array('links_count' => 0);
		
		// frequencia mínima é igual a 10% do total de tokens
		$min_freq = ceil(count($tokens) * 0.1);

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
			if($freq < $min_freq)
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