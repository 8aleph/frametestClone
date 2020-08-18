'use strict';

// Set default
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Send an ajax request and do something with the response.
 * 
 * @param {String}        url      url to send request to
 * @param {Object|null}   data     optional data to send with the request
 * @param {Function|null} callback optional callback to run with the response
 * 
 * @return {void}
 */
function sendPost(url, data = null, callback = null) {
    let returnValue;

    if (!data) {
        data = {};
    }

    axios.post(url, data)
        .then(r => {
            if (callback) {
                callback(r.data);
            }
        })
        .catch(e => handleRequestException(e));
}

/**
 * Send an ajax request and do something with the response.
 * 
 * @param {String}        url      url to send request to
 * @param {Object|null}   data     optional data to send with the request
 * @param {Function|null} callback optional callback to run with the response
 * 
 * @return {void}
 */
function sendGet(url, data = null, callback = null) {
    let returnValue;

    if (!data) {
        data = {};
    }

    axios.get(url, {params: data})
        .then(r => {
            if (callback) {
                callback(r.data);
            }
        })
        .catch(e => handleRequestException(e));
}

/**
 * Handle a request exception.
 * 
 * @param {Object} e exception
 * 
 * @return {void}
 */
function handleRequestException(e) {
    let message = null;

    if (e.response) {
        // Server responded within non 2xx code, grab the message
        message = e.response.data.message;
    } else if (e.request) {
        // The request was made but no response was received
        message = e.request;
    } else {
        message = e.message;
    }

    alert(message);
    console.error(message);
}
