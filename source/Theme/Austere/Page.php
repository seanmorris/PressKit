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

		$this->vars['breadcrumbs'] = implode(' &raquo; ', $renderedBreadcrumbs);

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
		<title><?php if(isset($title) && $title){?><?=$title;?> - <?php } ?>NewNinja.dev</title>
<?php foreach($js as $j): ?>
		<script type = "text/javascript" src = "<?=$j?>"></script>
<?php endforeach; ?>
<?php foreach($css as $c):?>
		<link href="<?=$c?>" rel="stylesheet">
<?php endforeach; ?>
	</head>
	<body>
		<div class = wrap>
			<div class="navbar navbar-inverse navbar-fixed-top">
				<div class="navbar-inner">
					<a class="brand" href="/">NewNinja.dev</a>
					<div class = "menu rightAlign">
						<?php echo $menu; ?>
					</div>
				</div>
			</div>
			<div class = "main container">
				<div class = "postBoardWrapper">
					<div class = "breadcrumbs"><?=$breadcrumbs?></div>
					<?php if(isset($title) && $title){?> <h1><?=$title;?></h1><?php } ?>
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
			  <p>Copyright 2015 Sean Morris</p>
			</div>
		</div>
		<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		ga('create', 'UA-41157199-1', 'sean-morris.com');
		ga('send', 'pageview');
		</script>
	</body>
</html>
<?php if(isset($comment)): ?>
<!--
<?php echo $comment;?>
-->
<?php endif; ?>