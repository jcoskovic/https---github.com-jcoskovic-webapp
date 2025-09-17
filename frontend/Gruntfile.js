module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    
    // Copy Angular built files
    copy: {
      main: {
        files: [
          {
            expand: true,
            cwd: 'dist/',
            src: '**',
            dest: 'build/'
          }
        ]
      }
    },
    
    // Clean build directory
    clean: {
      build: ['build/'],
      dist: ['dist/']
    },
    
    // Concatenate JS files if needed
    concat: {
      options: {
        separator: ';'
      },
      dist: {
        src: ['dist/assets/**/*.js'],
        dest: 'build/assets/app.js'
      }
    },
    
    // Minify JS files
    uglify: {
      options: {
        banner: '/*! <%= pkg.name %> <%= grunt.template.today("dd-mm-yyyy") %> */\n'
      },
      dist: {
        files: {
          'build/assets/app.min.js': ['build/assets/app.js']
        }
      }
    },
    
    // Minify CSS files
    cssmin: {
      dist: {
        files: [{
          expand: true,
          cwd: 'dist/',
          src: ['**/*.css'],
          dest: 'build/',
          ext: '.min.css'
        }]
      }
    }
  });

  // Load plugins
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-cssmin');

  // Register tasks
  grunt.registerTask('default', ['clean:build', 'copy']);
  grunt.registerTask('build', ['clean:build', 'copy', 'concat', 'uglify', 'cssmin']);
  grunt.registerTask('clean-all', ['clean']);
};
