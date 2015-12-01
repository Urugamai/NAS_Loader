var app = angular.module('NASLoader', []);

var reactions = app.controller('Reactions', [ '$scope', '$interval', '$http', '$filter',
    function($scope, $interval, $http, $filter) {
        $scope.format = 'd-MMM-yyyy HH:mm:ss';
        $scope.statusLine = 'Initialising...';
        $scope.srcMethods = [];

        var statusUpdate; // The promise

        // Define functions for STATUS management
        $scope.setStatusLine = function() {
            $scope.statusLine = $filter('date')(new Date(), $scope.format) + ': Awaiting your command...';       // Call the server php function in future... (use $http?)
        };

        $scope.updateStatusLine = function() {
            if (angular.isDefined(statusUpdate) ) return;	// dont double start timer

            statusUpdate = $interval( $scope.setStatusLine , 1000);
        };

        $scope.stopStatusLine = function() {
            if (angular.isDefined(statusUpdate) ) {
                $interval.cancel(statusUpdate);
                statusUpdate = undefined;
            }
        };

        // Define functions for Source Methodology

        $scope.generateSourceMethods = function() {
            $scope.srcMethods = '';
            var request = $http( {
                method: 'get'
                , url: 'http://localhost:63342/NAS_Loader/PHP/generateSourceMethods.php'
//                , data: { junk: 'nuthin' }
                , headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                }
            ).then(
                function successCallback(response) {
                    //angular.forEach(response.records, function(value,key){$scope.srcMethods.push(key, value);} );
                    $scope.srcMethods = response;
                    //$scope.srcMethods.push("Name","Worked");
                },
                function errorCallback(response) {
					//$scope.srcMethods.push("Name","Error");
					$scope.srcMethods = 'Error out';
                }
            );
        };

        $scope.$on('$destroy', function() {
                // Make sure that the interval is destroyed too
                $scope.stopStatusLine();
            }
        );

        $scope.updateStatusLine();

        $scope.generateSourceMethods();

        // next...

    }
]);

/* - running clock - from examples, we are not using this...
reactions.directive('myCurrentTime', ['$interval', 'dateFilter',
    function($interval, dateFilter) {
        // return the directive link function. (compile function not needed)
        return function(scope, element, attrs) {
            var format,  // date format
            stopStatus; // so that we can cancel the status updates

            // used to update the UI
            function updateTime() {
                element.text(dateFilter(new Date(), format));
            }

            // watch the expression, and update the UI on change.
            scope.$watch(attrs.myCurrentTime, function(value) {
                format = value;
                updateTime();
            });

            stopTime = $interval(updateTime, 1000);

            // listen on DOM destroy (removal) event, and cancel the next UI update
            // to prevent updating time after the DOM element was removed.
            element.on('$destroy', function() {
                $interval.cancel(stopTime);
            });
        }
    }
]);
// */