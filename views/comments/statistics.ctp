<script type="text/javascript">
$(document).ready(function() {
	$('a.button').button();
});
</script>

<div class="comments">
<h2><?php  __('Statistic');?></h2>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Total comments'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $stats['total']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Spam percent'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Number->toPercentage( ($stats['spam'] / $stats['total'])*100 ); ?>
			&nbsp;
		</dd>
	</dl>
</div>