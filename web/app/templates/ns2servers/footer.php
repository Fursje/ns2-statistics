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

</div>

<!-- JS -->
<?php
Assets::js(array(
	Url::templatePath() . 'js/jquery.min.js',
	Url::templatePath() . 'js/bootstrap.min.js',
	Url::templatePath() . 'js/main.js',
	Url::templatePath() . 'js/ie10-viewport-bug-workaround.js'

));

//hook for plugging in javascript
$hooks->run('js');

//hook for plugging in code into the footer
$hooks->run('footer');
?>

</body>
</html>
