<?php
namespace SeanMorris\PressKit\Theme\Austere;
class Grid extends \SeanMorris\Theme\View
{
	protected function preprocess(&$vars)
	{
		$this->vars['rows'] = isset($this->vars['rows'])
			? $this->vars['rows']
			: (
				isset($this->vars['content'])
					? $this->vars['content']
					: []
			)
		;

		if(!isset($this->vars['columns']))
		{
			$row = current($this->vars['rows']);
			$this->vars['columns'] = array_keys((array)$row);
		}

		if(!isset($this->vars['buttons']))
		{
			$this->vars['buttons'] = null;
		}

		if(!isset($this->vars['actions']))
		{
			$this->vars['actions'] = null;
		}
	}
}
__halt_compiler();
?>
<table class = "PressKitList">
	<tr class = "buttons">
		<td colspan="<?=count($columns);?>"><div class = "actions"><?=$actions;?></div></td>
	<tr>
	<tr class = "buttons">
		<td colspan="<?=count($columns);?>"><div class = "buttons"><?=$buttons;?></div></td>
	<tr>
		<?php foreach($columns as $key => $column): ?>
		<td class = "<?php
		echo is_numeric($key) ? NULL : $key;
		if(isset($columnClasses[$column]))
		{
			echo " ";
			echo $columnClasses[$column];
		}
		?>"><?=$column;?></td>
		<?php endforeach; ?>
	</tr>
<?php foreach($rows as $row): ?>
	<tr>
		<?php foreach($columns as $key => $column):?>
		<td class = "<?php
		echo $column ? $column : $key ;
		if(isset($columnClasses[$column]))
		{
			echo " ";
			echo $columnClasses[$column];
		}
		?>">
			<?php
			if(is_numeric($key))
			{
				$key = $column;
			}
			echo is_object($row)
				? $row->{$key}
				: $row[$key];
			?>
		</td>
		<?php endforeach; ?>
	</tr>
<?php endforeach; ?>
	<tr class = "buttons">
	<td colspan="<?=count($columns);?>"><div class = "buttons"><?=$buttons;?></div></td>
	</tr>
</table>
