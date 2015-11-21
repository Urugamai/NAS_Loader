var app = angular.module('NASLoader', []);

var reactions = app.controller('Reactions', [ '$scope', '$interval',
    function($scope, $interval) {
        $scope.format = 'd/M/yy h:mm:ss a';
        $scope.statusLine = 'Initialising...';
        var statusUpdate; // The promise

        // Define functions for STATUS management
        $scope.setStatusLine = function() {
            $scope.statusLine = 'We have started...';       // Call the server php function in future... (use $http?)
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

        $scope.$on('$destroy', function() {
                // Make sure that the interval is destroyed too
                $scope.stopStatusLine();
            }
        );

        $scope.updateStatusLine();

        // Define functions for Source Methodology

        $scope.generateSourceMethods = function() {
            $http.get(
                "../PHP/generateSourceMethods.php"
            ).success( function(response) {
                    $scope.srcMethods = response.records;
                }
            );
        };

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