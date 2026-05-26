jQuery(document).ready(function($) {
    // 1. Inject the modal HTML if it doesn't exist
    if ($('#gcal-android-modal').length === 0) {
        var modalHtml = `
        <div id="gcal-android-modal" class="gcal-modal-overlay" style="display: none;">
            <div class="gcal-modal-content">
                <button class="gcal-modal-close" aria-label="Close modal">&times;</button>
                <div class="gcal-modal-header">
                    <svg class="gcal-modal-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="4" width="18" height="16" rx="2" stroke="#FFEB3B" stroke-width="2"/>
                        <path d="M16 2V6" stroke="#FFEB3B" stroke-width="2" stroke-linecap="round"/>
                        <path d="M8 2V6" stroke="#FFEB3B" stroke-width="2" stroke-linecap="round"/>
                        <path d="M3 10H21" stroke="#FFEB3B" stroke-width="2" stroke-linecap="round"/>
                        <path d="M7 14H9" stroke="#FFEB3B" stroke-width="2" stroke-linecap="round"/>
                        <path d="M11 14H13" stroke="#FFEB3B" stroke-width="2" stroke-linecap="round"/>
                        <path d="M15 14H17" stroke="#FFEB3B" stroke-width="2" stroke-linecap="round"/>
                        <path d="M7 17H9" stroke="#FFEB3B" stroke-width="2" stroke-linecap="round"/>
                        <path d="M11 17H13" stroke="#FFEB3B" stroke-width="2" stroke-linecap="round"/>
                        <path d="M15 17H17" stroke="#FFEB3B" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <h2>Subscribe on Android</h2>
                </div>
                <div class="gcal-modal-body">
                    <p>Android does not support direct Google Calendar subscriptions. We have copied the subscription link to your clipboard:</p>
                    <div class="gcal-modal-url-box">
                        <code id="gcal-modal-url-text"></code>
                        <span class="gcal-modal-copied-indicator">Copied to Clipboard!</span>
                    </div>
                    <h3>To complete subscription:</h3>
                    <ol>
                        <li>Open <a href="https://calendar.google.com" target="_blank" rel="noopener noreferrer">Google Calendar</a> on a desktop/web browser.</li>
                        <li>In the left sidebar, click the <strong>+</strong> icon next to <strong>"Other calendars"</strong>.</li>
                        <li>Choose <strong>"From URL"</strong>.</li>
                        <li>Paste the copied link and click <strong>"Add calendar"</strong>.</li>
                    </ol>
                </div>
                <div class="gcal-modal-footer">
                    <a href="https://calendar.google.com" target="_blank" rel="noopener noreferrer" class="gcal-btn gcal-btn-primary">Go to Google Calendar</a>
                    <button class="gcal-btn gcal-btn-secondary gcal-modal-close-btn">Close</button>
                </div>
            </div>
        </div>`;
        $('body').append(modalHtml);
    }

    // Function to close modal
    function closeModal() {
        var $modal = $('#gcal-android-modal');
        $modal.removeClass('gcal-show');
        setTimeout(function() {
            $modal.hide();
        }, 300);
    }

    // Bind close events
    $(document).on('click', '.gcal-modal-close, .gcal-modal-close-btn, .gcal-modal-overlay', function(e) {
        if ($(e.target).hasClass('gcal-modal-overlay') || $(e.target).closest('.gcal-modal-close, .gcal-modal-close-btn').length > 0) {
            closeModal();
        }
    });

    // Helper to copy text to clipboard
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        } else {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            try {
                var successful = document.execCommand('copy');
                document.body.removeChild(textarea);
                return successful ? Promise.resolve() : Promise.reject();
            } catch (err) {
                document.body.removeChild(textarea);
                return Promise.reject(err);
            }
        }
    }

    // Intercept Google Calendar subscription clicks
    $(document).on('click', '.tribe-events-c-subscribe-dropdown__list-item--gcal a, .tribe-events-gcal a', function(e) {
        // Detect if it is Android
        var isAndroid = /Android/i.test(navigator.userAgent);

        // We run this custom behavior specifically on Android devices
        if (isAndroid) {
            e.preventDefault();
            e.stopPropagation();

            var href = $(this).attr('href');
            var feedUrl = '';

            if (href) {
                // Try to parse 'cid' parameter
                var urlParams = new URLSearchParams(href.split('?')[1]);
                var cid = urlParams.get('cid');
                if (cid) {
                    feedUrl = decodeURIComponent(cid);
                    feedUrl = feedUrl.replace('webcal://', 'https://');
                }
            }

            if (!feedUrl) {
                // Fallback to standard ical feed URL
                feedUrl = window.location.origin + window.location.pathname;
                if (!feedUrl.endsWith('/')) {
                    feedUrl += '/';
                }
                feedUrl += '?ical=1';
            }

            // Copy to clipboard and show modal
            copyToClipboard(feedUrl).then(function() {
                $('#gcal-modal-url-text').text(feedUrl);
                var $modal = $('#gcal-android-modal');
                $modal.show();
                // trigger redraw for CSS transitions
                $modal[0].offsetHeight; 
                $modal.addClass('gcal-show');
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                // Even if copy fails, show the modal so user can copy manually
                $('#gcal-modal-url-text').text(feedUrl);
                var $modal = $('#gcal-android-modal');
                $modal.show();
                $modal[0].offsetHeight;
                $modal.addClass('gcal-show');
            });
        }
    });
});
