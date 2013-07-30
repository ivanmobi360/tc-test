function initialize(){};
function codeAddress(){};

module("Basic Tests");

test("truthy", function(){
	ok(true, "true is truthy");
	equal(1, true, "1 is thruthy");
	notEqual(0, true, "0 is NOT thruthy");
});