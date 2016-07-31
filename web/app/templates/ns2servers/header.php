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
		<div id="custom-bootstrap-menu" class="navbar navbar-default" role="navigation">
		    <div class="container-fluid">
			<div class="navbar-header"><a href="/"><img src="<?php print Url::templatePath(); ?>images/ns2logo.png" height="50px" width="50px"></a>
			    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-menubuilder"><span class="sr-only">Toggle navigation</span><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
			    </button>
			</div>
			<div class="collapse navbar-collapse navbar-menubuilder">
			    <ul class="nav navbar-nav navbar-left">

				<li class="dropdown">
				  <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Statistics <span class="caret"></span></a>
				  <ul class="dropdown-menu">
				    <li><a href="/grafana/dashboard/db/natural-selection-2?theme=light">Generic</a></li>
				    <li><a href="/grafana/dashboard/db/natural-selection-2-gametypes?theme=light">Game modes</a></li>
				    <li><a href="/grafana/dashboard/db/natural-selection-2-patch-impact?theme=light">Patch Impact</a></li>
				  </ul>
				</li>
				<li><a href="/workshop">Workshop Backup</a>
				</li>
				<li><a href="/smokeping">Smokeping</a>
				</li>
				<li><a href="/contact">Contact</a>
				</li>
			    </ul>
			      <form class="navbar-form navbar-right" role="search">
				<div class="form-group">
				  <input id="filter" type="text" class="form-control" placeholder="Search">
				</div>
			      </form>
			</div>
		    </div>
		</div>
	<!-- /navbar -->

