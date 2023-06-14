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