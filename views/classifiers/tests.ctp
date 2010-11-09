<script type="text/javascript">
$(document).ready(function() {
	$('a.button').button();

	$('div.collapse').hide();

	$('a.collapse').click(function(e) {
		e.preventDefault();
		$(this).next('div.collapse').toggle();
	});

	$('a.classify').click(function(e) {
		e.preventDefault();
		$(this).next('div.collapse').toggle();
	});
});
</script>

<div class="comments">
	<h2><?php __('Testes');?></h2>
<?php
if(isset($info)):
?>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Classificador'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $info['type'] ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('ID do Classificador'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $info['id'] ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Instâncias'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $info['number_of_instances']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Número de Atributos'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $info['number_of_attributes']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Número de Acertos'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $info['number_of_asserts']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Taxa de Acerto'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $info['assertion_ratio']; ?>
			&nbsp;
		</dd>
		<?php
		if(isset($info['devianation'])):
		?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Desvio Padrão'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $info['devianation']; ?>
			&nbsp;
		</dd>
		<?php
		endif;
		?>
	</dl>
	<br />
	<a class="collapse button" href="#">Ver detalhes do classificador</a>
	<div class="collapse">
		<?php
		foreach($info['extra'] as $sec => $data):
			echo '<br /> >> <a href="#'.$sec.'" class="collapse button">', $sec, '</a>';
		
			echo '<div class="collapse">';
			
			echo '<table>', "\n";
			
			$header = array_merge(array('attribute'), array_keys($data));
			
			echo "<tr>\n";
			
			foreach($header as $h):
				echo '<th>', $h, '</th>', "\n";
			endforeach;
			
			echo "<tr>\n";
			
			$body = array();
			
			foreach($data as $anchor => $entry):
				foreach($entry as $key => $value):
					$body[$key][] = $value;
				endforeach;
			endforeach;
			
			foreach($body as $key => $entries):
				echo '<tr><td>', $key, '</td>';
				
				foreach($entries as $freq):
					echo '<td>', $freq, '</td>';
				endforeach;
				
				echo "</tr>\n";
			endforeach;
			
			echo '</table>';
			
			echo '</div>';
		endforeach;
		?>
	</div>
<?php
elseif(isset($stats)):
?>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Número de comentários'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $stats['total']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Total de SPAM'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $stats['spam']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Percentual de SPAM'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Number->toPercentage( ($stats['spam'] / $stats['total'])*100 ); ?>
			&nbsp;
		</dd>
	</dl>
<?php 
elseif(isset($classifieds)):
	foreach($classifieds as $classify):
?>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<?php if(isset($classify['correct'])): ?>
			<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Classe Correta'); ?></dt>
			<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php $classify['correct'] == 1 ? __('Spam') : __('Não Spam'); ?>
				&nbsp;
			</dd>
		<?php endif; ?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Classificado como'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php $classify['class'] == 1 ? __('Spam') : __('Não Spam'); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php $type == 'pa' ? __('Suffer Loss') : __('Probabilidade'); ?></dt>
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
	<br />
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
		echo $this->Html->link($id, array($this->params['pass'][0], 'default', $id)), ' ';
	endforeach;
endif;
?>
</div>

<div id="modal"></div>
