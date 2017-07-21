module.exports = function( grunt ) {

	grunt.initConfig({

		uglify: {
			dist: {
				options: {
					report: 'none',
					sourceMap: true
				},
				files: {
					'assets/js/bundle.min.js': [ 'assets/js/peepsomoods.js' ]
				}
			}
		}

	});

	grunt.loadNpmTasks( 'grunt-contrib-uglify' );

	grunt.registerTask( 'default', [
		'uglify'
	]);

};
