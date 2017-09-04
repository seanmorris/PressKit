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
			$this->vars['columns'] = array_keys((array)($row->unconsume()));
		}

		if(isset($this->vars['skipColumns']))
		{
			foreach($this->vars['skipColumns'] as $skipColumn)
			{
				if(FALSE !== ($index = array_search($skipColumn, $this->vars['columns'])))
				{
					unset($this->vars['columns'][$index]);
				}
			}
		}

		if(!isset($this->vars['buttons']))
		{
			$this->vars['buttons'] = null;
		}

		if(!isset($this->vars['actions']))
		{
			$this->vars['actions'] = null;
		}

		$vars['pagerLinks'] = [];

		if(isset($vars['pager']) && is_array($vars['pager']))
		{
			$query = $vars['query'];

			foreach($vars['pager'] as $label => $page)
			{
				$query['page'] = $page;

				if($label === $vars['page'])
				{
					$vars['pagerLinks'][] = $label + 1;
					continue;
				}

				$path = $vars['currentPath'];
				$vars['pagerLinks'][] = sprintf(
					'<a href = "/%s">%s</a>'
					, $path . '?' . http_build_query($query)
					, is_numeric($label) ? ($label+1) : $label
				);
			}
		}

		if(count($vars['pagerLinks']) <= 1)
		{
			$vars['pagerLinks'] = [];
		}
	}
}
__halt_compiler();
?>
<table class = "PressKitList">
	<tbody>
		<tr class = "buttons">
			<td colspan="<?=count($columns);?>">
				<div class = "actions"><?=$actions;?></div>
			</td>
		</tr>
		<tr class = "buttons">
			<td colspan="<?=count($columns);?>">
				<div class = "buttons"><?=$buttons;?></div>
			</td>
		</tr>
		<colgroup>
		<?php foreach($columns as $key => $column): ?>
			<col span="1" class = "<?php
			echo $column ? $column : $key ;
			if(isset($columnClasses[$column]))
			{
				echo " ";
				echo $columnClasses[$column];
			}
			?>" />
		<?php endforeach; ?>
		</colgroup>
	</tbody>
	<tbody>
	<tr>
		<?php foreach($columns as $key => $column): ?>
		<td class = "<?php
		echo $column ? $column : $key ;
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
			$val = is_object($row)
				? $row->{$key}
				: $row[$key];
			echo is_scalar($val) && !is_null($val) ? $val : sprintf('<span title = "%s">[]</span>', print_r($val, 1));
			?>
		</td>
		<?php endforeach; ?>
	</tr>
	<?php endforeach; ?>
	</tbody>
	<tr class = "buttons">
	<td colspan="<?=count($columns);?>">
		<div class = "buttons"><?php echo implode(' ', $pagerLinks); ?><br /><?=$buttons;?></div>
	</td>
	</tr>
</table>
