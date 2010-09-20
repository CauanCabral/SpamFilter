<script type="text/javascript">
$(document).ready(function() {
	$('a.button').button();

	$('div.collapse').hide();

	$('a.collapse').click(function(e) {
		e.preventDefault();
		$(this).next('div.collapse').toggle();
	});
});
</script>

<div class="comments">
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
	<a class="collapse button" href="#">Ver detalhes do classificador</a>
	<div class="collapse">
		<?php
			foreach($stats['extra'] as $sec => $data):
				echo '<br /> >> <a href="#'.$sec.'" class="collapse button">', $sec, '</a>';
			
				echo '<div class="collapse accordion">';
				
				foreach($data as $anchor => $entry):
					echo '<br /> >>>> <a href="#" class="collapse button">', $anchor, '</a>';
					echo '<div class="collapse">';
					
					if(is_array($entry)):
						echo '<table>', "\n";

						foreach($entry as $key => $value):
							echo '<tr><td>', $key, '</td><td>', $value, '</td> </tr>', "\n";
						endforeach;

						echo '</table>';
					else:
						echo '<p>', $entry, '</p>';
					endif;

					echo '</div>';
				endforeach;

				echo '</div>';
			endforeach;
		?>
	</div>
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

<div id="modal"></div>
