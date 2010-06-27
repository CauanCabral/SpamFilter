<?php
/**
 * Cria rota padrão
 */
Router::connect('/', array('controller' => 'comments', 'action' => 'index'));

/**
 * Conecta páginas estáticas (sem model)
 */
Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));

Router::mapResources('classifiers');
Router::parseExtensions();