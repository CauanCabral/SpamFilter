<?php
if(isset($classifieds)):
?>
<h2>Resultado do classificador</h2>
<?php
	foreach($classifieds as $classify):
?>
<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt <?php if ($i % 2 == 0) echo $class;?>><?php __('Classificado como'); ?></dt>
	<dd <?php if ($i++ % 2 == 0) echo $class;?>>
			<?php $classify['class'] == 1 ? __('Spam') : __('Não Spam'); ?>
			&nbsp;
		</dd>
	<dt <?php if ($i % 2 == 0) echo $class;?>><?php __('"Certeza"/"Margem de perda"'); ?></dt>
	<dd <?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $classify['p']; ?>
			&nbsp;
		</dd>
	<dt <?php if ($i % 2 == 0) echo $class;?>><?php __('Mensagem'); ?></dt>
	<dd <?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $classify['content']; ?>
			&nbsp;
		</dd>
</dl>
<br />
<?php
	endforeach;
?>
<hr />
<br />
<?php
endif;
?>
<div class="comment">
<?php
echo $this->Form->create('Classifier', array('url' => array('action' => 'classify')));
echo $this->Form->input('Classifier.content', array('type' => 'textarea', 'label' => 'Comentário'));
echo $this->Form->input('Classifier.spam', array('type' => 'radio', 'legend' => 'É SPAM?', 'options' => array(1 => 'Sim', 0 => 'Não')));
echo $this->Form->input('Classifier.classifier', array('type' => 'select', 'label' => 'Classificador', 'options' => $classifiers));
echo $this->Form->submit('Classificar');
?>
</div>