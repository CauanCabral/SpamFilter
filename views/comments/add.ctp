<script type="text/javascript">
$(document).ready(function() {
	$('a.button').button();
});
</script>

<div class="comments">
<?php echo $this->Form->create('Comment');?>
	<fieldset>
 		<legend><?php __('Adicionar Comentário'); ?></legend>
	<?php
		echo $this->Form->input('name', array('label' => __('Autor', 1)));
		echo $this->Form->input('content', array('label' => __('Conteúdo', 1)));
		echo $this->Form->input('spam', array('label' => __('É Spam?', 1)));
	?>
	</fieldset>
<?php echo $this->Form->end(__('Salvar', true));?>
</div>