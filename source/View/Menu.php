<?php
namespace SeanMorris\PressKit\View;
class Menu extends \SeanMorris\Theme\View
{
	public function preprocess(&$vars)
	{
		if(!isset($this->vars['menu']))
		{
			$this->vars['menu'] = \SeanMorris\PressKit\Menu::get('main');
		}

		if(isset($this->vars['subMenu']))
		{
			$this->vars['menu'] = $this->vars['subMenu'];
		}

		$menu = $this->vars['menu'];
		$menuItems = $menu->children();
		$this->vars['menuItemsData'] = [];

		foreach($menuItems as $menuItem)
		{
			$link = NULL;

			if(is_array($menuItem))
			{
				$text = key($menuItem);
				$link = '/' . implode(
					'/', array_filter(
						[$menu->path(), current($menuItem)]
					)
				);

				$this->vars['menuItemsData'][] = [
					'single' => true
					, 'text' => $text
					, 'link' => $link
				];
			}
			else
			{
				$link = $menuItem->link();
				$text = $menuItem->name();

				if($link !== NULL)
				{
					$link = '/' . implode(
						'/', array_filter(
							[$menuItem->path(), $link]
						)
					);
				}

				if(!$link && !$menuItem->children())
				{
					continue;
				}

				$subMenu = ( new static([
					'subMenu' => $menuItem
				]))->render();

				$this->vars['menuItemsData'][] = [
					'single' => false
					, 'subMenu' => trim($subMenu)
					, 'link' => $link
					, 'text' => $text
				];
			}
		}
	}
}
__halt_compiler();
?>
<?php if(is_array($menuItemsData) && $menuItemsData): ?>
<ul class = "menu">
<?php foreach($menuItemsData as $menuItem):
	if($menuItem['single']): ?>
		<li>
			<div class = "outer">
				<div class = "inner">
					<a href = "<?=$menuItem['link'];?>">
						<?=$menuItem['text'];?>
					</a>
				</div>
			</div>
		</li>

	<?php else: ?>

	<li>
		<div class = "outer">
		<div class = "inner">
			<a <?php if($menuItem['link']):
				?>href = "<?=$menuItem['link'];?>"<?php
			endif; ?>><?=$menuItem['text'];?></a>
		</div>
		</div>
		<?=$menuItem['subMenu']?>
	</li>
<?php endif; endforeach;?>
</ul>
<?php endif; ?>
