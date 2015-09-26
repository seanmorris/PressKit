<?php
namespace SeanMorris\PressKit\Theme\Austere;
class Html extends \SeanMorris\Theme\View
{

}
__halt_compiler();
?>
<html>
	<head>
	<?php if(isset($head)){ echo $head; }?>

	</head>
	<body>
		<?php if(isset($body)){ echo $body; }?>
	</body>
</html>
<?php if(isset($comment)): ?>
<!--
<?php echo $comment;?>
-->
<?php endif; ?>