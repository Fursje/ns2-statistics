<?php
/**
 * Routes - all standard routes are defined here.
 *
 * @author David Carr - dave@daveismyname.com
 * @version 2.2
 * @date updated Sept 19, 2015
 */

/** Create alias for Router. */
use Core\Router;
use Helpers\Hooks;

/** Define routes. */
Router::any('', 'Controllers\Servers@index');
Router::any('server/details/(:any)/(:num)', 'Controllers\Servers@details');
Router::any('server/details/(:any)', 'Controllers\Servers@details_ip');
Router::any('sitemap.xml', 'Controllers\Sitemap@index');
Router::any('workshop', 'Controllers\Servers@workshop');
Router::any('smokeping', 'Controllers\Smokeping@index');
Router::any('contact', 'Controllers\Servers@contact');

/** Module routes. */
$hooks = Hooks::get();
$hooks->run('routes');

/** If no route found. */
Router::error('Core\Error@index');

/** Turn on old style routing. */
Router::$fallback = false;

/** Execute matched routes. */
Router::dispatch();
