/*jshint node:true */
module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-jscs' );
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );

	grunt.initConfig( {
		jshint: {
			options: {
				jshintrc: true
			},
			files: [ 'Gruntfile.js', 'static/js/*.js' ]
		},
		banana: {
			all: 'i18n/'
		},
		jscs: {
			src: [ 'Gruntfile.js', 'static/js/*.js' ]
		}
	} );

	grunt.registerTask( 'lint', [ 'banana', 'jscs', 'jshint' ] );
	grunt.registerTask( 'test', 'lint' );
	grunt.registerTask( 'default', 'test' );
};
