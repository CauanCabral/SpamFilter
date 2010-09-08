<div class="comments index">
	<h2><?php __('Testes');?></h2>
<?php
if(isset($stats)):
	pr($stats);
endif;

if(isset($classifieds)):
	foreach($classifieds as $classify):
?>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Classificado como'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php $classify['class'] == 1 ? __('Spam') : __('Não Spam'); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('"Certeza"/"Margem de perda"'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $classify['p']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Mensagem'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $classify['content']; ?>
			&nbsp;
		</dd>
	</dl>
<?php
	endforeach;
endif;

if(isset($ids)):
	echo '<br /><br />Testar com mensagem: ';
	foreach($ids as $id):
		echo $this->Html->link($id, array($this->params['pass'][0], $this->params['pass'][1], $this->params['pass'][2], $id)), ' ';
	endforeach;
endif;
?>
</div>
<div class="actions">
	<h3><?php __('Ações'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('Gerar classificador (PA)', true), array('action' => 'tests', 'pa', 'build')); ?></li>
		<li><?php echo $this->Html->link(__('Gerar classificador (NaiveBayes)', true), array('action' => 'tests', 'naive_bayes', 'build')); ?></li>
		<li><?php echo $this->Html->link(__('Classificar mensagem (PA)', true), array('action' => 'tests', 'pa', 'classify', 1, 2)); ?></li>
		<li><?php echo $this->Html->link(__('Classificar mensagem (NaiveBayes)', true), array('action' => 'tests', 'naive_bayes', 'classify', 2, 2)); ?></li>
		<li><?php echo $this->Html->link(__('Estatísticas do classificador (PA)', true), array('action' => 'tests', 'pa', 'info', 1)); ?></li>
		<li><?php echo $this->Html->link(__('Estatísticas do classificador (NaiveBayes)', true), array('action' => 'tests', 'naive_bayes', 'info', 2)); ?></li>
		<li><?php echo $this->Html->link(__('Adicionar comentário', true), array('controller' => 'comments', 'action' => 'add')); ?></li>
		<li><?php echo $this->Html->link(__('Estatísticas dos comentários', true), array('controller' => 'comments', 'action' => 'statistics')); ?></li>
	</ul>
</div>
