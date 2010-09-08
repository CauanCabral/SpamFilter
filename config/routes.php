<?php
/**
 * Cria rota padrão
 */
Router::connect('/', array('controller' => 'classifiers', 'action' => 'tests'));

/**
 * Conecta páginas estáticas (sem model)
 */
Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));

Router::mapResources('classifiers');
Router::parseExtensions();