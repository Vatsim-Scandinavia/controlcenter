/**
 * Imports
*/

import Chart from 'chart.js/auto';
import * as helpers from 'chart.js/helpers';
import 'chartjs-adapter-moment';


/**
 * Insert global variables
*/

Chart.helpers = helpers;
window.Chart = Chart;
