/*jshint node:true */
module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-jsonlint' );
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
		jsonlint: {
			all: [
				'**/*.json',
				'!node_modules/**',
				'!vendor/**'
			]
		},
		jscs: {
			src: [ 'Gruntfile.js', 'static/js/*.js' ]
		}
	} );

	grunt.registerTask( 'lint', [ 'jsonlint', 'banana', 'jscs', 'jshint' ] );
	grunt.registerTask( 'test', 'lint' );
	grunt.registerTask( 'default', 'test' );
};
