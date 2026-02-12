
    //  ..........................................................
    //  .......... Number to words conversion concept ............
    //  ..........................................................


	function numberToWords(amount) {

		amount = Math.floor(amount);

		const words = [
			'', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
			'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
			'Seventeen', 'Eighteen', 'Nineteen'
		];

		const tens = [
			'', '', 'Twenty', 'Thirty', 'Forty', 'Fifty',
			'Sixty', 'Seventy', 'Eighty', 'Ninety'
		];

		if (amount === 0) return 'Zero Rupees Only';

		function convert(n) {
			if (n < 20) return words[n];
			if (n < 100)
				return tens[Math.floor(n / 10)] + (n % 10 ? ' ' + words[n % 10] : '');
			if (n < 1000)
				return words[Math.floor(n / 100)] + ' Hundred ' + convert(n % 100);
			if (n < 100000)
				return convert(Math.floor(n / 1000)) + ' Thousand ' + convert(n % 1000);
			if (n < 10000000)
				return convert(Math.floor(n / 100000)) + ' Lakh ' + convert(n % 100000);
			return convert(Math.floor(n / 10000000)) + ' Crore ' + convert(n % 10000000);
		}

		return convert(amount) + ' Rupees Only';
	}
