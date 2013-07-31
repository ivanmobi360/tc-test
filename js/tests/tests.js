function initialize(){};
function codeAddress(){};

//These contants are rendered globally in the template
var event_id = 'aaa';
var feeF = 1.08;
var feeP = 2.5;
var ccfeeF = 0.7;
var ccfeeP = 3.6;
var tax = 14.8936;    
var _tax = 0;
var taxFee = 0;
var _taxFee = 14.975;
var taxes = {"168":14.8936,"13":5,"12":5,"11":5,"10":15.5,"9":13,"8":13,"7":15,"6":10,"2":14.975,"5":12,"4":5,"1":13,"3":12};    
var taxes_type = {"168":[{"name":"v","rate":"14.8936"}],"13":[{"name":"g","rate":"5"}],"12":[{"name":"g","rate":"5"}],"11":[{"name":"g","rate":"5"}],"10":[{"name":"g","rate":"5"},{"name":"p","rate":"10"}],"9":[{"name":"h","rate":"13"}],"8":[{"name":"h","rate":"13"}],"7":[{"name":"h","rate":"15"}],"6":[{"name":"g","rate":"5"},{"name":"p","rate":"5"}],"2":[{"name":"g","rate":"5"},{"name":"p","rate":"9.5"}],"5":[{"name":"g","rate":"5"},{"name":"p","rate":"7"}],"4":[{"name":"g","rate":"5"}],"1":[{"name":"h","rate":"13"}],"3":[{"name":"h","rate":"12"}]};
var feeMax = 9.95;

module("Basic Tests");

test("truthy", function(){
	ok(true, "true is truthy");
	equal(1, true, "1 is thruthy");
	notEqual(0, true, "0 is NOT thruthy");
});

module("Calculator test");
test('fee calculation', function(){
	var cat = new Category('open', 20, 0, 11, 100, 1, 1, 0);
	//console.log("derp");
	cat.doMath();
	//dump(cat);
	equal( cat._taxes.toFixed(2), '14.89');
	ok(true);
});	

function dump(a){
	var acc = [];
	$.each(a, function(index, value) {
	    acc.push(index + ': ' + value);
	});
	console.log(acc);
}