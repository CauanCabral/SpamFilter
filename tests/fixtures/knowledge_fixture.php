<?php
/* Knowledge Fixture generated on: 2010-11-06 15:11:05 : 1289067725 */
class KnowledgeFixture extends CakeTestFixture {
	var $name = 'Knowledge';

	var $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
		'content' => array('type' => 'text', 'null' => false, 'default' => NULL, 'collate' => 'utf8_unicode_ci', 'comment' => 'conteudo do comentario processado e serializado', 'charset' => 'utf8'),
		'spam' => array('type' => 'boolean', 'null' => false, 'default' => '1'),
		'comment_id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'unique'),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'comment_id_UNIQUE' => array('column' => 'comment_id', 'unique' => 1)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB')
	);

	var $records = array(
		array(
			'id' => 1,
			'content' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'spam' => 1,
			'comment_id' => 1
		),
	);
}
?>