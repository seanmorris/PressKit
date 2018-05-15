<?php
namespace SeanMorris\PressKit\Theme\Austere;
class Page extends \SeanMorris\Theme\View
{
	public function preprocess(&$vars)
	{
		$renderedBreadcrumbs = [];
		if(isset($this->vars['breadcrumbs'])
			&& is_array($this->vars['breadcrumbs'])
		){
			foreach($this->vars['breadcrumbs'] as $crumb)
			{
				$renderedBreadcrumbs[] = sprintf(
					'<a class = "breadcrumbLink" href = "%s">%s</a>'
					, $crumb['url']
					, $crumb['text']
				);
			}
		}

		$this->vars['breadcrumbs'] = $renderedBreadcrumbs;

		$body =& $this->vars['body'];

		$body = [new Stack(['body' => $body])];
	}
}
__halt_compiler();
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
		<title><?php if(isset($title) && $title){?><?=$title;?> - <?php } ?>seanmorr.is</title>
<?php foreach($js as $j): ?>
		<script type = "text/javascript" src = "<?=$j?>"></script>
<?php endforeach; ?>
<?php foreach($css as $c):?>
		<link href="<?=$c?>" rel="stylesheet">
<?php endforeach; ?>
	<link rel="icon" type="image/png" href="/favicon.ico" />
	</head>
	<body>
		<div class = wrap>
			<div class="navbar navbar-inverse navbar-fixed-top">
				<div class="navbar-inner">
					<a class="brand" href="/">seanmorr.is</a>
					<div class = "menu rightAlign">
						<?php echo $menu ?? NULL; ?>
					</div>
				</div>
			</div>
			<div class = "main container">
				<div class = "postBoardWrapper">
					<div class = "header">
						<div class = "breadcrumbs"><?=implode(' &raquo; ', $breadcrumbs)?></div>
						<?php if(isset($title) && $title){?> <h1><?=$title;?></h1><?php } ?>
					</div>
					<div class = "body">
						<?=$messages?>
						<?php foreach($body as $segment){ echo $segment; echo PHP_EOL; } ?>
						<br />
						<?php /*<!--
						<div class = "menu"><?php echo $menu; ?></div>
						<div class = "list"><?php echo $menu; ?></div>
						-->*/ ?>
					</div>
				</div>
			</div>
			<div class = "smFooter footer">
			  <p>&copy; 2016-<?=date('y');?> Sean Morris</p>
			</div>
		</div>
		<?php /*
		<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		ga('create', 'UA-41157199-1', 'sean-morris.com');
		ga('send', 'pageview');
		</script>
		*/ ?>
	</body>
</html>
<?php if(isset($comment)): ?>
<!--
<?php echo $comment;?>
-->
<?php endif; ?>
