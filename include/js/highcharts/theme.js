/**
 * Grid theme for Highcharts JS
 * @author Torstein H¿nsi
 */

Highcharts.theme = {
	colors: ['#006699', '#990000', '#CC8800', '#CCCC00', '#008000', '#0000B3', '#660099', '#990066', '#990000', '#994C00', '#CCCC00', '#008000'
		/*
		'hsla(200,100%,30%,1)', // theme blue
		//'hsla(240,100%,30%,1)', // dark blue
		//'hsla(280,100%,30%,1)', // purple
		//'hsla(320,100%,30%,1)', // dark pink
		'hsla(  0,100%,30%,1)', // red
		'hsla( 30,100%,30%,1)', // orange
		'hsla( 60,100%,30%,1)', // yellow
		'hsla(120,100%,30%,1)', // green
		'hsla(180,100%,30%,1)', // green-blue
		'hsla(240,100%,30%,1)', // dark blue
		'hsla(280,100%,30%,1)', // purple
		'hsla(320,100%,30%,1)', // dark pink
		'hsla(  0,100%,30%,1)', // red
		'hsla( 30,100%,30%,1)', // orange
		'hsla( 60,100%,30%,1)', // yellow
		'hsla(120,100%,30%,1)', // green
		'hsla(180,100%,30%,1)', // green-blue
		*/
	],
	chart: {
		backgroundColor: 'rgba(255, 255, 255, 0)',
		//borderColor: 'hsla(200, 100%, 30%, 1)',
		borderWidth: 0,
		//plotBackgroundColor: 'rgba(255, 255, 255, 0)',
		shadow: false,
		plotBorderWidth: 0,
		borderRadius: 4,
		style: {
			fontFamily: '"Open Sans", "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", "Lucida", "Trebuchet MS", verdana, helvetica, arial, sans-serif',
			fontSize: '100%',
		}
	},
	title: {
		style: { 
			color: '#000',
			fontSize: '150%'
		}
	},
	subtitle: {
		style: { 
			color: '#666666',
			fontSize: '125%',
		}
	},
	xAxis: {
		gridLineWidth: 0,
		lineColor: '#000',
		tickColor: '#000',
		labels: {
			style: {
				color: '#000',
				fontSize: '90%'
			}
		},
		title: {
			style: {
				color: '#333',
				fontWeight: 'bold',
				fontSize: '12pt',
			}				
		}
	},
	yAxis: {
		minorTickInterval: 'auto',
		lineColor: '#000',
		lineWidth: 1,
		tickWidth: 1,
		tickColor: '#000',
		startOnTick: true,
		endOnTick: true,
		gridLineWidth: 0,
		minorGridLineWidth: 0,
		labels: {
			style: {
				color: '#000',
				fontSize: '90%',
			}
		},
		title: {
			style: {
				color: '#333',
				fontWeight: 'bold',
				fontSize: '100%',
			}				
		}
	},
	legend: {
		x: 100,
		y: 0,
		borderWidth: 0,
		floating: true,
		verticalAlign: 'top',
		align: 'left',
		layout: 'vertical',
		itemStyle: {			
			fontSize: '100%',
			color: 'black'

		},
		itemHoverStyle: {
			color: '#039'
		},
		itemHiddenStyle: {
			color: 'gray'
		}
	},
	labels: {
		style: {
			color: '#99b'
		}
	},
	credits: {
        enabled: false
    },
};

// Apply the theme
var highchartsOptions = Highcharts.setOptions(Highcharts.theme);
	
