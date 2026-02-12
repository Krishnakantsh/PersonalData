   //  ..........................................................
    //  ..... Any formatted random number generate Concept .......
    //  ..........................................................

	function generateSaleNo(orderId, dateString) {
		let d = new Date(dateString);
		let month = d.toLocaleDateString('en-US', { month: 'short' });
		let day = d.getDate();

		return `${month}-${day}-${orderId}`;
	}
