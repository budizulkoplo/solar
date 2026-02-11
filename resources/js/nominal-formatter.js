// nominal-formatter.js

// Utility functions for formatting nominal inputs with Indonesian rupiah format

/**
 * Format a number to Indonesian Rupiah format.
 * @param {number} number - The number to format.
 * @returns {string} - The formatted currency string.
 */
function formatToRupiah(number) {
    const rupiah = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
    }).format(number);
    return rupiah;
}

/**
 * Auto-replace default 0 value.
 * @param {HTMLInputElement} input - The input element to modify.
 */
function replaceDefaultZero(input) {
    if (input.value === '0') {
        input.value = '';
    }
}

/**
 * Handle form submission.
 * @param {HTMLFormElement} form - The form element.
 * @returns {void}
 */
function handleFormSubmission(form) {
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const amountField = form.querySelector('input[name="amount"]');
        const amount = amountField.value;
        if (amount) {
            const formattedAmount = formatToRupiah(parseFloat(amount));
            console.log('Formatted Amount:', formattedAmount);
            // Proceed with form submission (e.g. via AJAX)
        }
    });
}