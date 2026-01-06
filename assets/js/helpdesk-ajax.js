/**
 * HelpDesk AJAX Helper Utility
 * Centralizovaná správa AJAX requestov s jednotnou security a error handling
 */

(function($) {
    'use strict';

    // Globálny objekt pre AJAX configuration
    window.HelpDeskAjax = {
        nonce: null,
        ajaxurl: null,
        requestCount: 0,
        lastRequestTime: 0,
        requestTimeout: 30000, // 30 sekúnd

        /**
         * Inicializuj AJAX helper s nonce a ajaxurl
         */
        init: function(nonce, ajaxurl) {
            this.nonce = nonce;
            this.ajaxurl = ajaxurl;
            console.log('HelpDeskAjax initialized');
        },

        /**
         * Spustí AJAX request s jednotným error handlingom
         * @param {Object} options - jQuery.ajax options
         * @param {string} options.action - AJAX action name
         * @param {string} options.method - POST/GET (default: POST)
         * @param {Object} options.data - Request data
         * @param {Function} options.success - Success callback
         * @param {Function} options.error - Error callback (optional)
         * @param {Function} options.complete - Complete callback (optional)
         */
        request: function(options) {
            const self = this;

            // Validuj povinné parametre
            if (!options.action) {
                console.error('HelpDeskAjax: action je povinný parameter');
                return;
            }

            if (!this.nonce) {
                console.error('HelpDeskAjax: Nonce nie je inicializovaný');
                if (options.error) options.error({}, 'nonce_error', 'Nonce not initialized');
                return;
            }

            // Príprava data
            const requestData = options.data || {};
            requestData.action = options.action;
            requestData._wpnonce = this.nonce;  // Jednotný nonce parameter

            // jQuery AJAX config
            const ajaxConfig = {
                type: options.method || 'POST',
                url: this.ajaxurl,
                data: requestData,
                dataType: 'json',
                timeout: this.requestTimeout,
                error: function(xhr, status, error) {
                    self._handleError(xhr, status, error, options);
                },
                success: function(response) {
                    if (options.success && typeof options.success === 'function') {
                        options.success(response);
                    }
                },
                complete: function(xhr, status) {
                    if (options.complete && typeof options.complete === 'function') {
                        options.complete(xhr, status);
                    }
                }
            };

            // Spusti request
            $.ajax(ajaxConfig);
        },

        /**
         * Centralizovaný error handler
         */
        _handleError: function(xhr, status, error, options) {
            const errorMsg = 'HelpDeskAjax Error: ';

            console.error('AJAX Error Details:', {
                status: xhr.status,
                statusText: xhr.statusText,
                error: error,
                responseText: xhr.responseText.substring(0, 200)
            });

            let userMessage = 'Chyba pri komunikácii so serverom';

            switch (xhr.status) {
                case 0:
                    userMessage = 'Chyba pri komunikácii - možno problém so sieťou alebo CORS';
                    break;
                case 400:
                    userMessage = 'Chybný request - neplatné dáta';
                    break;
                case 403:
                    userMessage = 'Nemáte oprávnenie na túto operáciu';
                    break;
                case 404:
                    userMessage = 'Action "' + options.action + '" nebol nájdený';
                    break;
                case 500:
                    userMessage = 'Chyba servera - skúste neskôr';
                    break;
                case 503:
                    userMessage = 'Server je dočasne nedostupný';
                    break;
                default:
                    if (status === 'timeout') {
                        userMessage = 'Request vypršal - server neodpovedá';
                    } else if (status === 'error') {
                        userMessage = 'Neznáma chyba AJAX';
                    } else if (status === 'parsererror') {
                        userMessage = 'Chyba pri spracovaní odpovede servera';
                    }
            }

            // Volaj custom error handler ak existuje
            if (options.error && typeof options.error === 'function') {
                options.error(xhr, status, error);
            } else {
                // Default error notification
                console.error(errorMsg + userMessage);
                // Možno sem pridať notifikáciu v UI
            }
        },

        /**
         * Rate limiting - kontrola či neprekvačuje počet requestov
         */
        isRateLimited: function(maxRequests = 10, windowMs = 60000) {
            const now = Date.now();
            const timeSinceLastRequest = now - this.lastRequestTime;

            if (timeSinceLastRequest < windowMs) {
                this.requestCount++;
            } else {
                this.requestCount = 1;
                this.lastRequestTime = now;
            }

            if (this.requestCount > maxRequests) {
                console.warn('HelpDeskAjax: Rate limit exceeded - prílíš veľa requestov');
                return true;
            }

            return false;
        },

        /**
         * Shortcut pre GET request
         */
        get: function(action, data, success, error) {
            this.request({
                action: action,
                method: 'GET',
                data: data,
                success: success,
                error: error
            });
        },

        /**
         * Shortcut pre POST request
         */
        post: function(action, data, success, error) {
            this.request({
                action: action,
                method: 'POST',
                data: data,
                success: success,
                error: error
            });
        }
    };

})(jQuery);
