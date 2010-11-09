<?php
/**
 * Cria rota padrão
 */
Router::connect('/', array('controller' => 'classifiers', 'action' => 'tests'));

/*
 * Configuração para webservice
 */
Router::mapResources('classifiers');
Router::parseExtensions();