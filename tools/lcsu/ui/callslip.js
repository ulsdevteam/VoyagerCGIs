/* 
 * Callslips()
 * 
 * Sets up the JS interaction for the LCSU Callslip Printing tool.
 * 
 */


/*
 * This is a closure, in possibly the simplest style.
 * 
 * The idea is that we write a function that returns a function; when we execute
 * callslips() and store the returned value in a variable, this embeds
 * everything inside the nested function in its own lexical scope, thus insulating
 * it from the rest of the outside "world". The functions inside are effectively
 * encapsulated and unavailable to other scripts, but within their own scope
 * they can see and call each other.
 * 
 * This encapsulation is good practice; it significantly reduces the surface area
 * other scripts have access to, which should reduce side-effects and collisions--
 * not just from other modifications we might make later, but also from browser
 * extensions, plugins, and user JS.
 */
function callslips() {
	return function() {
		
		/*
		 * loadCallslips()
		 * 
		 * Creates and runs an XML HTTP Request against the callslips API.
		 * 
		 * @param none
		 * @returns none
		 * Side effects: 
		 */
		function loadCallslips() {
			setLoading();
			var xhr = new XMLHttpRequest();
			xhr.onload = function () {
				// Process our return data
				if (xhr.status >= 200 && xhr.status < 300) {
					// Success
					var data = JSON.parse(xhr.responseText);
					console.log(data);
					writeCallslipsToScreen(data);
					console.log("Here!");
				} else {
					// Failure
				}
				// Always runs
			};
			xhr.open('GET', 'http://voystaff.library.pitt.edu/tools/lcsu/callslips/');
			xhr.send();
		}
		
		/*
		 * writeCallslipsToScreen(callslipData)
		 * 
		 * @param {JSON Object} callslipData. Object containing multiple rows of callslip data.
		 * @returns none
		 * Side effects: loads data from callslipData into a table.
		 */
		function writeCallslipsToScreen(callslipData) {
			var tbody = document.querySelector('div.results tbody');
			tbody.innerHTML = "";
			var count = 0;
			callslipData.forEach(function(callslip) {
				count++;
				tbody.innerHTML += `
						<tr>
							<td class="label-count">${count}</td>
							<td class="call-slip-id">${callslip.callslip_id}</td>
							<td class="i-barcode">${callslip.item_barcode}</td>
							<td class="call-number">${callslip.call_number}</td>
							<td class="p-barcode">${callslip.patron_barcode}</td>
							<td class="tray-address">${callslip.tray_no_date}</td>
							<td class="title-brief">${callslip.title_brief}</td>
						</tr>`
				console.log(callslip);
			});
			writeToMessage(callslipData.length + " Callslips Found");
		}
		
		/*
		 * function setLoading()
		 * 
		 * Clears content and replaces it with a loading message.
		 * 
		 * @returns none
		 * Side effects: replaces the tbody content with a loading message.
		 */
		function setLoading() {
			var tbody = document.querySelector('div.results tbody');
			if(tbody) {
				tbody.innerHTML = `
							<td colspan="7" class="table-message">
								Loading content...
							</td>`
			}
		}
		
		/*
		 * writeCallslipError(error)
		 * 
		 * Writes an error to the screen.
		 * 
		 * @param {string} error
		 * @returns none
		 * Side Effects: writes error to screen
		 */
		
		function writeCallslipError(error) {
			var tbody = document.querySelector('div.results tbody');
			if(tbody) {
				tbody.innerHTML = `
							<td colspan="7" class="table-message">
								Error loading callslips!
							</td>`
			}
		}
		
		/*
		 * writeToMessage(message)
		 * 
		 * Writes a string message to the message fields.
		 * 
		 * @param {string} message
		 * @returns none
		 * Side effects: writes a message to screen.
		 */
		function writeToMessage(message) {
			console.log("And also here!");
			var fields = document.querySelectorAll('.message'), i;
			console.log(fields);
			
			for (i = 0; i < fields.length; i++) {
				var field = fields[i];
				field.innerHTML = message;
			}
		}
		
		/*
		 * printCallslips()
		 * 
		 * Asks the API to print the callslips it currently has available.
		 * 
		 * @returns {undefined}
		 * Side effects: calls the API to print callslips.
		 */
		function printCallslips() {
			console.log("Printing callslips!");
		}
		
		/*
		 * Initialize()
		 * 
		 * Sets up the basic event listeners and calls the loadCallslips() function.
		 * 
		 * @returns none
		 * Side effects: creates an event listener on the document and loads callslips.
		 */
		function Initialize() {
			document.addEventListener('click', function (event) {
				if(event.target.matches('.print-labels > button')) {
					printCallslips();
				} else if (event.target.matches('.reload > button')) {
					loadCallslips();
				} else {
					return;
				}
			}, false);
			loadCallslips();
		}
		
		// Let's actually call Initialize inside the closure, so it executes
		Initialize();
	}
}

var callslipStartFunction = callslips();

/*
 * Basic, modern approach to vanilla JS run onload.
 * 
 * Targeting the DOMContentLoaded event is ideal, but it only triggers once.
 * If it's already been triggered by the time we try loading the function, the
 * function won't run. So we should check to see if the readyState has been
 * triggered first, and if it has we'll directly call the initialize function.
 */

if (
    document.readyState === "complete" ||
    (document.readyState !== "loading" && !document.documentElement.doScroll)
) {
  callslipStartFunction();
} else {
  document.addEventListener("DOMContentLoaded", callslipStartFunction());
}

