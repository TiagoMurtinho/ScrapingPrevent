window.onload = function() {
    const messageClasses = ['success-message', 'error-message'];
    messageClasses.forEach(className => {
        const messages = document.getElementsByClassName(className);
        if (messages.length > 0) {
            const message = messages[0];
            setTimeout(function() {
                message.style.opacity = '0';
                setTimeout(function() {
                    message.style.display = 'none';
                }, 500);
            }, 2000);
        }
    });
};

const jsEnabledFields = document.getElementsByClassName('js-enabled-field');
if (jsEnabledFields.length > 0) {
    jsEnabledFields[0].value = 'true';
}
