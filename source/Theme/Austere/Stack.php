<?php
namespace SeanMorris\PressKit\Theme\Austere;
class Stack extends \SeanMorris\Theme\View
{
	public function preprocess(&$vars)
	{
		$body =& $this->vars['body'];

		if(!is_array($body))
		{
			$body = [$body];
		}
	}
}
__halt_compiler();
?>
<?php foreach($body as $key => $segment): ?>
	<!--<?=$key;?>-->
	<?=$segment;?>
	<!--<?=$key;?>-->
<?php endforeach; ?>