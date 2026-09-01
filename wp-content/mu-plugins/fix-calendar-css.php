<?php
/*
Plugin Name: Fix Calendar CSS
Description: Injects high-priority CSS to fix Elementor/TEC conflicts.
*/

add_action('wp_head', function() {
    echo '<style>
    /* Brute Force Calendar Fixes */
    body .tribe-events .tribe-events-c-top-bar__today-button,
    body .tribe-events .tribe-events-calendar-month__calendar-event-title,
    body .tribe-events .tribe-events-calendar-month__calendar-event-title-link,
    body .tribe-events .tribe-events-calendar-month__calendar-event-title a,
    body .tribe-events .tribe-events-calendar-month-mobile-events__mobile-event-title,
    body .tribe-events .tribe-events-calendar-month-mobile-events__mobile-event-title-link,
    body .tribe-events .tribe-events-c-messages__message a {
        color: #111111 !important;
    }
    
    /* Day Names (S, M, T... and SAT, SUN...) */
    body .tribe-events .tribe-events-calendar-month__header-column-title,
    body .tribe-events .tribe-events-calendar-list__event-date-tag-weekday,
    body .tribe-events .tribe-common-b3 {
        color: #0056b3 !important; 
        font-weight: 800 !important;
        font-size: 1.15rem !important;
        text-transform: uppercase !important;
    }

    /* Day Numbers (1, 2, 3...) in Month View */
    body .tribe-events .tribe-events-calendar-month__day-date,
    body .tribe-events .tribe-events-calendar-month__day-date-link,
    body .tribe-events .tribe-events-calendar-month__day-date-daynum {
        color: #555555 !important;
    }

    /* "This Month" Button */
    body .tribe-events .tribe-events-c-top-bar__today-button {
        background-color: transparent !important;
        border-color: #cccccc !important;
    }

    /* Month/Year Datepicker Button (Blue with White Text) */
    body .tribe-events .tribe-events-c-top-bar__datepicker-button {
        background-color: #0056b3 !important; /* Professional Blue */
        border: 1px solid #004494 !important;
        border-radius: 4px !important;
    }
    body .tribe-events .tribe-events-c-top-bar__datepicker-time,
    body .tribe-events .tribe-events-c-top-bar__datepicker-time span {
        color: #ffffff !important;
        background-color: transparent !important;
    }
    body .tribe-events .tribe-events-c-top-bar__datepicker-button svg {
        fill: #ffffff !important;
    }

    /* Search Bar Styling */
    body .tribe-events .tribe-events-c-search__input,
    body .tribe-events .tribe-events-c-search__input-control {
        background-color: #ffffff !important;
        color: #111111 !important;
        border-color: #cccccc !important;
    }
    body .tribe-events .tribe-events-c-search__input::placeholder {
        color: #666666 !important;
    }
    body .tribe-events .tribe-events-c-search__input-control-icon-svg {
        fill: #333333 !important;
    }

    /* Fix List View Colors */
    body .tribe-events .tribe-events-calendar-list {
        background-color: #ffffff !important;
    }
    body .tribe-events .tribe-events-calendar-list__month-separator-text {
        color: #0056b3 !important; 
        font-weight: bold !important;
    }
    body .tribe-events .tribe-events-calendar-list__event-title,
    body .tribe-events .tribe-events-calendar-list__event-title-link,
    body .tribe-events .tribe-events-calendar-list__event-title a {
        color: #111111 !important;
    }
    body .tribe-events .tribe-events-calendar-list__event-date-tag-daynum,
    body .tribe-events .tribe-events-calendar-list__event-date-tag-datetime,
    body .tribe-events .tribe-events-calendar-list__event-venue-title,
    body .tribe-events .tribe-events-calendar-list__event-datetime-wrapper {
        color: #333333 !important; 
    }
    body .tribe-events .tribe-events-calendar-list__event-row {
        background-color: transparent !important;
        border-bottom: 1px solid #eeeeee !important;
    }

    /* Fix Elementor Social Media Icons disappearing (Ad-blocker evasion) */
    body .elementor-widget-stealth-links,
    body .elementor-widget-stealth-links .elementor-widget-container,
    body .elementor-widget-stealth-links .elementor-social-icons-wrapper {
        display: flex !important;
        opacity: 1 !important;
        visibility: visible !important;
        transform: none !important;
        animation: none !important;
    }
    body .elementor-social-icon {
        display: inline-flex !important;
        opacity: 1 !important;
        visibility: visible !important;
        transform: none !important;
        animation: none !important;
    }
    body .elementor-social-icon svg, 
    body .elementor-social-icon i {
        opacity: 1 !important;
        visibility: visible !important;
        display: inline-block !important;
        width: 1em !important;
        height: 1em !important;
        transform: none !important;
    }
    
    /* Re-map FontAwesome for stealth classes */
    body .stealth-fb:before { content: "\f09a" !important; }
    body .stealth-ig:before { content: "\f16d" !important; }
    body .stealth-yt:before { content: "\f167" !important; }
    </style>';
}, 999);

// Defeat Brave Browser/Ad-blockers by intercepting HTML and removing the targeted classes
add_action('template_redirect', function() {
    ob_start(function($html) {
        $html = str_replace('elementor-widget-social-icons', 'elementor-widget-stealth-links', $html);
        $html = str_replace('fa-facebook', 'stealth-fb', $html);
        $html = str_replace('fa-instagram', 'stealth-ig', $html);
        $html = str_replace('fa-youtube', 'stealth-yt', $html);
        return $html;
    });
});
