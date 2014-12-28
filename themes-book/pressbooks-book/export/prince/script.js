var roman = ["M", "CM", "D", "CD", "C", "XC", "L", "XL", "X", "IX", "V", "IV", "I"];
var decimal = [1000, 900, 500, 400, 100, 90, 50, 40, 10, 9, 5, 4, 1];


function decimalToRoman(value) {
	if (value <= 0 || value >= 4000) return value;
	var romanNumeral = "";
	for (var i = 0; i < roman.length; i++) {
		while (value >= decimal[i]) {
			value -= decimal[i];
			romanNumeral += roman[i];
		}
	}
	return romanNumeral;
}


function romanizePartNumbers() {
	var list = document.getElementsByTagName("h3");
	for (var i = 0; i < list.length; i++) {
		if (list[i].className == "part-number") {
			list[i].innerHTML = decimalToRoman(parseInt(list[i].innerHTML));
		}
	}
}

function main() {
	romanizePartNumbers();
}


window.onload = main;