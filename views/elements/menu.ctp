<?php
echo $this->Html->scriptStart();

echo '$(document).ready(function() {$("#menu li a").button();});';

echo $this->Html->scriptEnd();
?>
<ul id="menu">
	<li><?php echo $this->Html->link(__('Gerar classificador (PA)', true), array('controller' => 'classifiers', 'action' => 'tests', 'build', 'pa')); ?></li>
	<li><?php echo $this->Html->link(__('Gerar classificador (NaiveBayes)', true), array('controller' => 'classifiers', 'action' => 'tests', 'build', 'naive_bayes')); ?></li>
	<li><?php echo $this->Html->link(__('Classificar novo comentário', true), array('controller' => 'classifiers', 'action' => 'classify')); ?></li>
	<li><?php echo $this->Html->link(__('Classificar mensagem (PA)', true), array('controller' => 'classifiers', 'action' => 'tests', 'classify', 'pa', 1, 2)); ?></li>
	<li><?php echo $this->Html->link(__('Classificar mensagem (NaiveBayes)', true), array('controller' => 'classifiers', 'action' => 'tests', 'classify', 'naive_bayes', 2, 2)); ?></li>
	<li><?php echo $this->Html->link(__('Estatísticas do classificador (PA)', true), array('controller' => 'classifiers', 'action' => 'tests', 'info', 'pa', 1)); ?></li>
	<li><?php echo $this->Html->link(__('Estatísticas do classificador (NaiveBayes)', true), array('controller' => 'classifiers', 'action' => 'tests', 'info', 'naive_bayes', 2)); ?></li>
	<li><?php echo $this->Html->link(__('Adicionar comentário', true), array('controller' => 'comments', 'action' => 'add')); ?></li>
	<li><?php echo $this->Html->link(__('Estatísticas dos comentários', true), array('controller' => 'classifiers', 'action' => 'tests', 'stats')); ?></li>
	<li><?php echo $this->Html->link(__('Exportar comentários (Arff e tab)', true), array('controller' => 'classifiers', 'action' => 'export')); ?></li>
</ul>