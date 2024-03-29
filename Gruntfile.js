/* jshint node:true */
/* global module */
module.exports = function( grunt ) {
	var jsonFile = grunt.file.readJSON( 'package.json' );
	Object.keys( jsonFile.devDependencies ).forEach( function( plugin ) {
		if ( plugin.match( /grunt-.*/ ) ) {
			grunt.loadNpmTasks( plugin );
		}
	} );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),
		jshint: {
			options: grunt.file.readJSON( '.jshintrc' ),
			grunt: {
				src: ['Gruntfile.js']
			},
			all: ['Gruntfile.js', 'js/*.js']
		},
		checktextdomain: {
			options: {
				correct_domain: false,
				text_domain: ['mediatheque','default'],
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'_n:1,2,4d',
					'_ex:1,2c,3d',
					'_nx:1,2,4c,5d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src: ['**/*.php', '!**/node_modules/**', '!**/vendor/**'],
				expand: true
			}
		},
		clean: {
			all: ['assets/*.min.css', 'js/*.min.js']
		},
		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					exclude: ['/node_modules', '/vendor'],
					mainFile: 'mediatheque.php',
					potFilename: 'mediatheque.pot',
					processPot: function( pot ) {
						pot.headers['last-translator']      = 'imath <contact@imathi.eu>';
						pot.headers['language-team']        = 'FRENCH <contact@imathi.eu>';
						pot.headers['report-msgid-bugs-to'] = 'https://github.com/imath/mediatheque/issues';
						return pot;
					},
					type: 'wp-plugin'
				}
			}
		},
		uglify: {
			minify: {
				extDot: 'last',
				expand: true,
				ext: '.min.js',
				src: ['js/*.js', '!*.min.js']
			},
			options: {
				banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - ' +
				'<%= grunt.template.today("UTC:yyyy-mm-dd h:MM:ss TT Z") %> - ' +
				'https://imathi.eu/tag/mediatheque */\n'
			}
		},
		cssmin: {
			minify: {
				extDot: 'last',
				expand: true,
				ext: '.min.css',
				src: ['assets/*.css', '!*.min.css'],
				options: {
					banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - ' +
					'<%= grunt.template.today("UTC:yyyy-mm-dd h:MM:ss TT Z") %> - ' +
					'https://imathi.eu/tag/mediatheque */'
				}
			}
		},
		jsvalidate:{
			src: ['js/*.js'],
			options:{
				globals: {},
				esprimaOptions:{},
				verbose: false
			}
		},
		'git-archive': {
			archive: {
				options: {
					'format'  : 'zip',
					'output'  : '<%= pkg.name %>.zip',
					'tree-ish': 'HEAD@{0}'
				}
			}
		},
		exec: {
			test_php: {
				command: 'composer test',
				stdout: true,
				stderr: true
			},
			test_php_multisite: {
				command: 'composer test_multisite',
				stdout: true,
				stderr: true
			}
		}
	} );

	grunt.registerTask( 'phpunit', ['exec:test_php', 'exec:test_php_multisite'] );

	grunt.registerTask( 'commit', ['checktextdomain', 'phpunit'] );

	grunt.registerTask( 'jstest', ['jsvalidate', 'jshint'] );

	grunt.registerTask( 'shrink', ['cssmin', 'uglify'] );

	grunt.registerTask( 'compress', ['git-archive'] );

	grunt.registerTask( 'translate', ['checktextdomain', 'makepot'] );

	grunt.registerTask( 'release', ['translate', 'clean', 'jstest', 'shrink'] );

	// Test.
	grunt.registerTask( 'test', ['jstest', 'checktextdomain', 'phpunit'] );

	// Default task.
	grunt.registerTask( 'default', ['commit'] );
};
