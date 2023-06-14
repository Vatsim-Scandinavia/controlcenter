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

window.addEventListener('load', function(event) {
    var sidebar = document.getElementById('sidebar');
    var sidebarButton = document.getElementById('sidebar-button');
    var sidebarCloseButton = document.getElementById('sidebar-button-close');

    sidebarButton.onclick = function(event){
        event.preventDefault();
        sidebar.classList.toggle('sidebar-show');
    }

    sidebarCloseButton.onclick = function(event){
        event.preventDefault();
        sidebar.classList.toggle('sidebar-show');
    }
});