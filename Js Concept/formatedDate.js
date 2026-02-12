	
    
    //  .................................
    //  ..... Date Format Concept .......
    //  .................................

    function formatPosDate(dateString) {
		let date = new Date(dateString);

		let dayName = date.toLocaleDateString('en-US', { weekday: 'long' });
		let day = date.getDate();

		let suffix =
			day > 3 && day < 21 ? 'th' :
			day % 10 === 1 ? 'st' :
			day % 10 === 2 ? 'nd' :
			day % 10 === 3 ? 'rd' : 'th';

		let monthName = date.toLocaleDateString('en-US', { month: 'long' });
		let year = date.getFullYear();

		let time = date.toLocaleTimeString('en-US', {
			hour: '2-digit',
			minute: '2-digit',
			second: '2-digit',
			hour12: true
		});

		return `${dayName} ${day}${suffix} of ${monthName} ${year} ${time}`;
	}

 