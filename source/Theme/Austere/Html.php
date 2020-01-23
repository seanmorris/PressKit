<?php
namespace SeanMorris\PressKit\Theme\Austere;
class Html extends \SeanMorris\Theme\View
{

}
__halt_compiler();
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
		<title><?php if(isset($title) && $title){?><?=$title;?> - <?php } ?></title>
<?php foreach($js as $j): ?>
		<script type = "text/javascript" src = "<?=$j?>"></script>
<?php endforeach; ?>
<?php foreach($css as $c):?>
		<link href="<?=$c?>" rel="stylesheet">
<?php endforeach; ?>
	<link rel="icon" type="image/png" href="/favicon.png" />
	</head>
	<body>
		<?php foreach($body as $segment){ echo $segment; echo PHP_EOL; } ?>
	</body>
</html>
<?php if(isset($comment)): ?>
<!--
<?php echo $comment;?>
-->
<?php endif; ?>