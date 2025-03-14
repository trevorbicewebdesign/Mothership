/*
document.addEventListener('DOMContentLoaded', () => {
    const clientField = document.querySelector('#jform_client_id');
    const accountContainer = document.querySelector('.account-container');
    const spinner = document.querySelector('.account-loading-spinner');
    const accountWrapper = document.querySelector('.account_id_wrapper');
    const accountField = document.querySelector('#jform_account_id');
    const MIN_SPINNER_TIME = 500;
    let previousClientId = clientField.value;

    function init() {
        const clientId = clientField.value;
        const selectedAccountId = accountField.value;

        if (clientId && clientId !== '0') {
            accountContainer.style.display = 'block';
            accountContainer.style.height = 'auto';
            if (selectedAccountId && selectedAccountId !== '0') {
                accountWrapper.style.display = 'block';
                accountWrapper.classList.add('show');
            } else {
                accountWrapper.style.display = 'none';
                accountWrapper.classList.remove('show');
            }
        } else {
            accountContainer.style.display = 'none';
            spinner.style.display = 'none';
            accountWrapper.style.display = 'none';
            accountWrapper.classList.remove('show');
        }
    }

    function toggleAccountField() {
        const clientId = clientField.value;

        if (!clientId || clientId === '0') {
            hideAccountField();
            previousClientId = clientId;
            return;
        }

        if (previousClientId !== clientId) {
            previousClientId = clientId;
            showSpinner();

            if (accountContainer.style.display !== 'block') {
                slideOpen(accountContainer, () => fetchAndShowAccounts(clientId));
            } else {
                fadeOut(accountWrapper, () => fetchAndShowAccounts(clientId));
            }
        }
    }

    function fetchAndShowAccounts(clientId) {
        const startTime = Date.now();
        fetch(`index.php?option=com_mothership&task=invoice.getAccountsForClient&format=json&client_id=${clientId}`)
            .then(res => res.json())
            .then(data => {
                accountField.innerHTML = '<option value="">Select Account</option>';
                if (data.data) {
                    data.data.forEach(account => {
                        accountField.innerHTML += `<option value="${account.id}">${account.name}</option>`;
                    });
                }
            })
            .catch(console.error)
            .finally(() => {
                const elapsed = Date.now() - startTime;
                setTimeout(() => {
                    hideSpinner();
                    fadeIn(accountWrapper);
                }, Math.max(0, MIN_SPINNER_TIME - elapsed));
            });
    }

    function showSpinner() {
        fadeOut(accountWrapper);
        spinner.style.display = 'block';
        requestAnimationFrame(() => spinner.classList.add('show'));
    }

    function hideSpinner() {
        spinner.classList.remove('show');
        spinner.addEventListener('transitionend', () => spinner.style.display = 'none', { once: true });
    }

    function hideAccountField() {
        fadeOut(accountWrapper);
        slideClose(accountContainer);
        accountField.innerHTML = '<option value="">Select Account</option>';
    }

    function slideOpen(el, callback) {
        el.style.display = 'block';
        el.style.height = '0';
        requestAnimationFrame(() => {
            el.style.height = `${el.scrollHeight}px`;
        });
        el.addEventListener('transitionend', () => {
            el.style.height = '';
            if (callback) callback();
        }, { once: true });
    }

    function slideClose(el) {
        el.style.height = `${el.scrollHeight}px`;
        requestAnimationFrame(() => el.style.height = '0');
        el.addEventListener('transitionend', () => {
            el.style.display = 'none';
            el.style.height = '';
        }, { once: true });
    }

    function fadeIn(el) {
        el.style.display = 'block';
        requestAnimationFrame(() => el.classList.add('show'));
    }

    function fadeOut(el, callback) {
        el.classList.remove('show');
        el.addEventListener('transitionend', () => {
            el.style.display = 'none';
            if (callback) callback();
        }, { once: true });
    }

    init();
    clientField.addEventListener('change', toggleAccountField);
});

*/