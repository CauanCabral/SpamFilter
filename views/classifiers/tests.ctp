<div class="comments index">
	<h2><?php __('Comments');?></h2>
<?php
if(isset($stats))
{
	pr($stats);
}

if(isset($classifieds))
{
	pr($classifieds);
}
?>
</div>
<div class="actions">
	<h3><?php __('Ações'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('Gerar classificador (PA)', true), array('action' => 'tests', 'pa', 'build')); ?></li>
		<li><?php echo $this->Html->link(__('Gerar classificador (NaiveBayes)', true), array('action' => 'tests', 'naive_bayes', 'build')); ?></li>
		<li><?php echo $this->Html->link(__('Classificar mensagem (PA)', true), array('action' => 'tests', 'pa', 'classify', 1)); ?></li>
		<li><?php echo $this->Html->link(__('Classificar mensagem (NaiveBayes)', true), array('action' => 'tests', 'naive_bayes', 'classify', 2)); ?></li>
		<li><?php echo $this->Html->link(__('Estatísticas do classificador (PA)', true), array('action' => 'tests', 'pa', 'info')); ?></li>
		<li><?php echo $this->Html->link(__('Estatísticas do classificador (NaiveBayes)', true), array('action' => 'tests', 'naive_bayes', 'info')); ?></li>
		<li><?php echo $this->Html->link(__('Adicionar comentário', true), array('controller' => 'comments', 'action' => 'add')); ?></li>
		<li><?php echo $this->Html->link(__('Estatísticas dos comentários', true), array('controller' => 'comments', 'action' => 'statistics')); ?></li>
	</ul>
</div>
