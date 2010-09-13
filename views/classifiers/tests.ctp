
<?php
echo $this->Html->script('jquery');
?>
<script type="text/javascript" >
$(document).ready(function() {
	$('div.collapse').hide();
	
	$('a.collapse').click(function(e) {
		e.preventDefault();
		$('div.collapse').toggle();
	});
});
</script>
<div class="comments index">
	<h2><?php __('Testes');?></h2>
<?php
if(isset($stats)):
?>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Classificador'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $stats['type'] ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('ID do Classificador'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $stats['id'] ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Instâncias'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $stats['number_of_instances']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Número de Atributos'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $stats['number_of_attributes']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Número de Acertos'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $stats['number_of_asserts']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Taxa de Acerto'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $stats['assertion_ratio']; ?>
			&nbsp;
		</dd>
	</dl>
	<br />
	<a class="collapse" href="#">Ver detalhes do classificador</a>
	<div class="collapse"><?php pr($stats['extra']); ?></div>
<?php
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
	echo '<br /><br /><b>Testar com mensagem: </b>';
	foreach($ids as $id):
		echo $this->Html->link($id, array($this->params['pass'][0], $this->params['pass'][1], $this->params['pass'][2], $id)), ' ';
	endforeach;
endif;

if(isset($classifiers)):
	echo '<br /><br /><b>Outros classificadores: </b>';
	foreach($classifiers as $id):
		echo $this->Html->link($id, array($this->params['pass'][0], $this->params['pass'][1], $id)), ' ';
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
