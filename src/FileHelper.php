<?php

namespace WebUsUp\ElevenLabsForWp;

class FileHelper {
	public static function make_global_filesystem_object() {
		// https://developer.wordpress.org/apis/filesystem/
		// First, include the necessary WordPress files
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/template.php';

		// Check if the filesystem API is available
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Initialize the WordPress filesystem
		global $wp_filesystem;
		if (!$wp_filesystem instanceof \WP_Filesystem_Base) {
			WP_Filesystem();
		}
	}

	/**
	 * @throws FilesystemException
	 */
	public static function init_file_dir($file_dir) {
		global $is_apache;
		global $wp_filesystem;
//		var_dump(function_exists('get_filesystem_method'));
//		var_dump($wp_filesystem);
		if (!$wp_filesystem instanceof \WP_Filesystem_Base) {
			throw new FilesystemException('Global $wp_filesystem not found. Please use FileHelper only after the \'init\' action.');
		}
		// Create cache folder if not exist.
		if ( ! $wp_filesystem->is_dir( $file_dir ) ) {
			self::mkdir_p( $file_dir );
		}

		if ( ! $wp_filesystem->is_file( $file_dir . '/index.php' ) ) {
			$wp_filesystem->touch( $file_dir . '/index.php' );
		}

		if ( $is_apache ) {
			$htaccess_path = $file_dir . '.htaccess';

			if ( ! $wp_filesystem->is_file( $htaccess_path ) ) {
				$wp_filesystem->touch( $htaccess_path );
				self::put_content( $htaccess_path, "<IfModule mod_autoindex.c>\nOptions -Indexes\n</IfModule>" );
			}
		}
	}

	/**
	 * Directory creation based on WordPress Filesystem
	 *
	 * @param string $dir The path of directory will be created.
	 *
	 * @return bool
	 * @throws FilesystemException
	 * @since 1.3.4
	 */
	private static function mkdir( string $dir ): bool {
		global $wp_filesystem;
		if (!$wp_filesystem instanceof \WP_Filesystem_Base) {
			throw new FilesystemException('Global $wp_filesystem not found. Please use FileHelper only after the \'init\' action.');
		}
		$chmod = defined( 'FS_CHMOD_DIR' ) ? FS_CHMOD_DIR : ( fileperms( WP_CONTENT_DIR ) & 0777 | 0755 );

		return $wp_filesystem->mkdir( $dir, $chmod );
	}

	/**
	 * File creation based on WordPress Filesystem
	 *
	 * @param string $file The path of file will be created.
	 * @param string $content The content that will be printed in advanced-cache.php.
	 *
	 * @return bool
	 * @throws FilesystemException
	 * @since 1.3.5
	 */
	public static function put_content( string $file, string $content ): bool {
		global $wp_filesystem;
		if (!$wp_filesystem instanceof \WP_Filesystem_Base) {
			throw new FilesystemException('Global $wp_filesystem not found. Please use FileHelper only after the \'init\' action.');
		}
		// TODO: This will need to accept binary content
		$chmod = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;

		return $wp_filesystem->put_contents( $file, $content, $chmod );
	}

	/**
	 * Recursive directory creation based on full path.
	 *
	 * @param string $target path to the directory we want to create.
	 *
	 * @return bool True if directory is created/exists, false otherwise
	 * @since  1.3.4
	 *
	 * @source wp_mkdir_p() in /wp-includes/functions.php
	 */
	private static function mkdir_p( string $target ): bool {
		global $wp_filesystem;
		if (!$wp_filesystem instanceof \WP_Filesystem_Base) {
			throw new FilesystemException('Global $wp_filesystem not found. Please use FileHelper only after the \'init\' action.');
		}
		// from php.net/mkdir user contributed notes.
		$target = str_replace( '//', '/', $target );

		// safe mode fails with a trailing slash under certain PHP versions.
		$target = untrailingslashit( $target );
		if ( empty( $target ) ) {
			$target = '/';
		}

		if ( $wp_filesystem->exists( $target ) ) {
			return $wp_filesystem->is_dir( $target );
		}

		// Attempting to create the directory may clutter up our display.
		if ( self::mkdir( $target ) ) {
			return true;
		} elseif ( $wp_filesystem->is_dir( dirname( $target ) ) ) {
			return false;
		}

		// If the above failed, attempt to create the parent node, then try again.
		if ( ( '/' !== $target ) && ( self::mkdir_p( dirname( $target ) ) ) ) {
			return self::mkdir_p( $target );
		}

		return false;
	}

	/**
	 * Remove a single file or a folder recursively
	 *
	 * @param string $dir File/Directory to delete.
	 * @param array $dirs_to_preserve (default: array()) Dirs that should not be deleted.
	 *
	 * @return void
	 * @throws FilesystemException
	 * @since 1.0
	 */
	public function rrmdir( string $dir, array $dirs_to_preserve = array() ) {
		global $wp_filesystem;
		if (!$wp_filesystem instanceof \WP_Filesystem_Base) {
			throw new FilesystemException('Global $wp_filesystem not found. Please use FileHelper only after the \'init\' action.');
		}
		$dir = untrailingslashit( $dir );

		if ( ! $wp_filesystem->is_dir( $dir ) ) {
			$wp_filesystem->delete( $dir );

			return;
		}

		$dirs = glob( $dir . '/*', GLOB_NOSORT );
		if ( $dirs ) {
			$keys = array();
			foreach ( $dirs_to_preserve as $dir_to_preserve ) {
				$matches = preg_grep( "#^$dir_to_preserve$#", $dirs );
				$keys[]  = reset( $matches );
			}

			$dirs = array_diff( $dirs, array_filter( $keys ) );
			foreach ( $dirs as $dir ) {
				if ( $wp_filesystem->is_dir( $dir ) ) {
					$this->rrmdir( $dir, $dirs_to_preserve );
				} else {
					$wp_filesystem->delete( $dir );
				}
			}
		}

		$wp_filesystem->delete( $dir );
	}

	/**
	 * Remove all files in the folder
	 *
	 * @return void
	 * @throws FilesystemException
	 * @since 2.6.8
	 */

	public function clean_file_dir($file_dir) {
		// Delete all caching files.
		$dirs = glob( $file_dir . '*', GLOB_NOSORT );
		if ( $dirs ) {
			foreach ( $dirs as $dir ) {
				self::rrmdir( $dir );
			}
		}
	}
}