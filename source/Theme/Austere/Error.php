<?php
namespace SeanMorris\PressKit\Theme\Austere;
class Error extends \SeanMorris\Theme\View
{

}
__halt_compiler();
?>
<h1>HTTP <?= $object->getCode(); ?></h1>
<p><?= $object->getMessage(); ?></p>
<?php if(\SeanMorris\Ids\Settings::read('devmode')): ?> 
	<pre><?= $object->indentedTrace(); ?></pre>
<?php endif; ?>