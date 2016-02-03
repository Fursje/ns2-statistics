<?php
/**
 * Sample layout
 */

use Helpers\Assets;
use Helpers\Url;
use Helpers\Hooks;

//initialise hooks
$hooks = Hooks::get();
?>

<!DOCTYPE html>
<html lang="<?php echo LANGUAGE_CODE; ?>">
  <head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<?php
	if (isset($data['description'])) {
		$description =  $data['description'];
	} else {
		$description = "Natural Selection 2 - Game and Server Performance Statistics";
	}
?>
	<meta name="description" content="<?php echo $description; ?>">
	<meta name="keywords" content="ns2,natural selection 2, natural selection II, ns2servers, statistics, performance, server browser">
	<meta name="author" content="ns2servers.net">
	<link rel="icon" href="/favicon.ico">
	<?php
	$hooks->run('meta');
	?>
	<title><?php echo $data['title'].' - '.SITETITLE; //SITETITLE defined in app/Core/Config.php ?></title>

	<!-- CSS -->
	<?php
	Assets::css(array(
		Url::templatePath() . 'css/bootstrap.min.css"',
		Url::templatePath() . 'css/style.css',
		Url::templatePath() . 'css/flags.css',
	));

	//hook for plugging in css
	$hooks->run('css');
	?>

<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
  ga('create', 'UA-71840049-1', 'auto');
  ga('send', 'pageview');
</script>
	</head>

	<body>
	<?php
		//hook for running code after body tag
		$hooks->run('afterBody');
	?>

	<div class="container">
		<nav class="navbar navbar-light bg-faded">
			<button class="navbar-toggler hidden-sm-up" type="button" data-toggle="collapse" data-target="#navbar-header" aria-controls="navbar-header">
		  		&#9776;
			</button>
			<div class="collapse navbar-toggleable-xs" id="navbar-header">
		  		<a class="navbar-brand" href="#">NS2</a>
				<ul class="nav navbar-nav">
					<li class="nav-item active">
						<a class="nav-link" href="/">Home <span class="sr-only">(current)</span></a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="/grafana/dashboard/db/natural-selection-2?theme=light" target="_new">Statistics</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="#">Server Setup</a>
					</li>

					<li class="nav-item">
						<a class="nav-link" href="#">About</a>
					</li>
		  		</ul>
		  		<form class="form-inline pull-xs-right">
					<input id="filter" class="form-control" type="text" placeholder="Filter..">
		  		</form>
			</div>
		</nav> 
	<!-- /navbar -->

