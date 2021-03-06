<?php
class MP_Addons
{
	const option_name = 'MailPress_add-ons';
	const folder      = 'add-ons';

	public static function get_addon_data( $addon_file ) 
	{
		$default_headers = array( 	'Name' 		=> 'Plugin Name', 
							'PluginURI' 	=> 'Plugin URI', 
							'Version' 		=> 'Version', 
							'Description' 	=> 'Description', 
							'Author' 		=> 'Author', 
							'AuthorURI' 	=> 'Author URI', 
							'TextDomain' 	=> 'Text Domain', 
							'DomainPath' 	=> 'Domain Path'
		);

		$addon_data = self::get_file_data( $addon_file, $default_headers, 'mp_addon' );

		//For backward compatibility by default Title is the same as Name.
		$addon_data['Title'] = $addon_data['Name'];

		return $addon_data;
	}

	public static function get_file_data( $file, $default_headers, $context = '' ) 
	{
		// We don't need to write to the file, so just open for reading.
		$fp = fopen( $file, 'r' );

		// Pull only the first 8kiB of the file in.
		$file_data = fread( $fp, 8192 );

		// PHP will close file handle, but we are good citizens.
		fclose( $fp );

		if ( $context != '' ) 
		{
			$extra_headers = apply_filters("extra_$context".'_headers', array() );

			$extra_headers = array_flip( $extra_headers );
			foreach( $extra_headers as $key=>$value ) $extra_headers[$key] = $key;
			$all_headers = array_merge($extra_headers, $default_headers);
		} 
		else 	$all_headers = $default_headers;
	
		foreach ( $all_headers as $field => $regex ) 
		{
			preg_match( '/' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, ${$field});
			${$field} = ( !empty( ${$field} ) ) ? self::_cleanup_header_comment( ${$field}[1] ) : '';
		}

		$file_data = compact( array_keys( $all_headers ) );
		return $file_data;
	}

	public static function _cleanup_header_comment($str) 
	{
		return trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $str));
	}

	public static function get_all($addon_folder = '')
	{
		if ( ! $cache_addons = wp_cache_get('mp_addons', 'mp_addons') ) $cache_addons = array();
		if ( isset($cache_addons[ $addon_folder ]) ) return $cache_addons[ $addon_folder ];

		$mp_addons = array ();
		$addon_root = MP_CONTENT_DIR . self::folder;
		if( !empty($addon_folder) ) $addon_root .= $addon_folder;

		// Files in addons directory
		$addon_files = array();

		$addons_dir = @opendir( $addon_root);
		if ( $addons_dir ) 
		{
			while (($file = readdir( $addons_dir ) ) !== false ) 
			{
				if ( substr($file, 0, 1) == '.' ) continue;
				if ( is_dir( "$addon_root/$file" ) ) 
				{
					$addons_subdir = @opendir( "$addon_root/$file" );
					if ( $addons_subdir ) 
					{
						while (($subfile = readdir( $addons_subdir ) ) !== false ) 
						{
							if ( substr($subfile, 0, 1) == '.' ) continue;
							if ( substr($subfile, -4) == '.php' ) $addon_files[] = plugin_basename("$addon_root/$file/$subfile");
						}
						@closedir( $addons_subdir );
					}
				} 
				else 
				{
					if ( substr($file, -4) == '.php' ) $addon_files[] = plugin_basename("$addon_root/$file");
				}
			}
			@closedir( $addons_dir );
		}
		if ( empty($addon_files) ) return $mp_addons;

		$active = get_option(self::option_name);
		if (!is_array($active)) $active = array();

		foreach ( $addon_files as $addon_file ) 
		{
			$file = ABSPATH . PLUGINDIR . "/$addon_file";
			if ( !is_readable( $file ) ) continue;
			$addon_data = self::get_addon_data( $file, false, false ); //Do not apply markup/translate as it'll be cached.
			if ( empty ( $addon_data['Name'] ) ) continue;

			$addon_data['file'] = plugin_basename( $file ) ;
			$addon_data['active'] = (isset($active[plugin_basename( $file )])) ;

			$mp_addons[plugin_basename( $file )] = $addon_data;
		}
		uasort( $mp_addons, create_function( '$a, $b', 'return strnatcasecmp( $a["Name"], $b["Name"] );' ));
		$cache_addons[ $addon_folder ] = $mp_addons;
		wp_cache_set('mp_addons', $cache_addons, 'mp_addons');

		return $mp_addons;
	}

	public static function load_all()
	{
		$new = array();
		$old = get_option(self::option_name);

		foreach (self::get_all() as $mp_addon)
		{
			if (!$mp_addon['active']) 		continue;
			if (!self::load($mp_addon['file'])) continue;

			$new[$mp_addon['file']] = $mp_addon['file'];
		}

		if ($new != $old) update_option(self::option_name, $new);

		if (!empty($new)) do_action('MailPress_add-ons_loaded');
	}

	public static function load($file)
	{
		$file = ABSPATH . PLUGINDIR . "/$file";

		if (!is_file($file)) return false;
		require_once($file);
		return true;
	}
}
MP_Addons::load_all();
