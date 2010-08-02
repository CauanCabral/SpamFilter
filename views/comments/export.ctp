<div class="comments view">
<h2><?php  __('Export');?></h2>
<?php
?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('List Comments', true), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Comment', true), array('action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('Statistics', true), array('action' => 'statistics')); ?></li>
	</ul>
</div>
