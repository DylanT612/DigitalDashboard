/**
 * Handles if the state select element should be displayed.
 * @param {String} target - Country code of the currently selected country.
 */
export function eventOptAmerica(target) {
    //If selected country is USA country code
    if (target === '840') {
        //Set session value to true and display state select element
        sessionStorage.setItem('isAmerica',true);
        displayStates(true);
    }
    else { //Else
        //Set session value to false
        sessionStorage.setItem('isAmerica',false);
        //Ensure state select element is hidden
        displayStates(false);
        //Ensure current selected state is reset
        document.getElementById('optState').value = "";
    }
}

/**
 * Displays or hides state select element.
 * @param {Boolean} show - Decides if the function should display or hide state select element.
 */
export function displayStates(show) {
    const optState = document.getElementById('optState'); //State select element

    //If show is true, remove hidden attribute from state select element, else ensure it's hidden
    if (show) {
        optState.removeAttribute('hidden');
        optState.setAttribute('required', true);
    }
    else {
        optState.setAttribute('hidden', true);
        optState.removeAttribute('required');
    }    
}