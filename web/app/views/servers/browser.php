<?php
/**
 * Server Browser Layout
 */

use Core\Language;
use Helpers\Url;

?>
	  <!-- Main component for a primary marketing message or call to action -->
	<div>
		<p>
			<iframe src="/grafana/dashboard-solo/db/natural-selection-2?theme=light&panelId=2&fullscreen" style="width: 100%;" height="300" frameborder="0" scrolling="no"></iframe>
		</p>
		<div ng-app="ns2list" ng-controller="ns2servers">
			<p style="text-align:right; font-size: 12px;"><b>Last update</b>: {{ servers.last_update }} &nbsp; <button class="btn btn-success btn-xsm" ng-click="refresh()">Reload</button></p>
			<table class="table table-condensed table-hover">
				<caption>Server List</caption>
				<thead>
					<tr>
						<th>Status</th>
						<th>Address</th>
						<th>Server Name</th>
						<th>Map</th>
						<th>Players</th>
						<th>Version</th>
						<th>Details</th>
					</tr>
				</thead>
				<tbody class="searchable">
					<tr ng-repeat="x in servers.servers | orderObjectBy:'numberOfPlayers':true">
						<td><button type="button" class="btn btn-success btn-xsm">Online</button></td>
						<td style="white-space:nowrap;"> 
							<img src="<?php echo Url::templatePath()."blank.gif"; ?>" class="flag flag-{{ x.country }}" /> 
							<a class="ip-href" href="/server/details/{{x.host}}">{{ x.host }}</a>:{{ x.serverPort }}
						</td>
						<td>{{ x.serverName }}</td>
						<td>{{ x.mapName }}</td>
						<td>{{ x.numberOfPlayers }} / {{ x.maxPlayers }}</td>
						<td>{{ x.version }}</td>
						<td><a class="btn btn-primary btn-xsm" href="/server/details/{{x.host}}/{{x.port}}" role="button">info</a></td>	
					</tr>

				</tbody>
			</table>
		</div>
	</div>
	

<script src="http://ajax.googleapis.com/ajax/libs/angularjs/1.4.8/angular.min.js"></script>
<script>
var app = angular.module('ns2list', []);
app.controller('ns2servers', function($scope, $http) {
	//$http.get("/site_data.json")
	//.then(function (response) {
	//	$scope.servers = response.data;
	//});
	$scope.refresh = function() {
		$http.get("/site_data.json")
		.then(function (response) {
                	$scope.servers = response.data;
        	});
	};

	$scope.refresh();
});
app.filter('orderObjectBy', function() {
  return function(items, field, reverse) {
    var filtered = [];
    angular.forEach(items, function(item) {
      filtered.push(item);
    });
    filtered.sort(function (a, b) {
      return (a[field] > b[field] ? 1 : -1);
    });
    if(reverse) filtered.reverse();
    return filtered;
  };
});

</script>
