/**
 * Init all project's JavaScript dependecies
 */

require('./bootstrap');
import moment from 'moment';
import Vue from 'vue';

window.moment = moment;
window.Vue = Vue;

/**
 * Register Vue components
 */

Vue.component('example-component', require('./components/ExampleComponent.vue').default);


/**
 * Create a fresh Vue application instance
 */

const app = new Vue({
    el: '#app',
});


/**
 * Other global javascript functionality
 */

(function ($) {
    "use strict";

    // Toggle the side navigation
    $("#sidebarToggle, #sidebarToggleTop").on('click', function (e) {
        $("body").toggleClass("sidebar-toggled");
        $(".sidebar").toggleClass("toggled");
        if ($(".sidebar").hasClass("toggled")) {
            $('.sidebar .collapse').collapse('hide');
        };
    });

    var lastWindowWidth = $(window).width();

    // Close any open menu accordions when window is resized below 768px
    $(window).resize(function () {
        if ($(window).width() < 768) {
            $('.sidebar .collapse').collapse('hide');
        };

        // Toggle the side navigation when window is resized below 480px
        if ($(window).width() < 480 && !$(".sidebar").hasClass("toggled") && $(window).width() > lastWindowWidth+20) {
            $("body").addClass("sidebar-toggled");
            $(".sidebar").addClass("toggled");
            $('.sidebar .collapse').collapse('hide');
        };

        lastWindowWidth = $(window).width();
    });

    if ($(window).width() < 480) {
        $("body").addClass("sidebar-toggled");
        $(".sidebar").addClass("toggled");
        $('.sidebar .collapse').collapse('hide');
    }

    // Prevent the content wrapper from scrolling when the fixed side navigation hovered over
    $('body.fixed-nav .sidebar').on('mousewheel DOMMouseScroll wheel', function (e) {
        if ($(window).width() > 768) {
            var e0 = e.originalEvent,
                delta = e0.wheelDelta || -e0.detail;
            this.scrollTop += (delta < 0 ? 1 : -1) * 30;
            e.preventDefault();
        }
    });

})(jQuery);
