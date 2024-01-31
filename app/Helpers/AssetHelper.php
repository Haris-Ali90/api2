<?php

/**
 * AssetHelper
 *
 */

/**
 * Return's admin assets directory
 *
 * CALLING PROCEDURE
 *
 * In controller call it like this:
 * $adminAssetsDirectory = adminAssetsDir() . $site_settings->site_logo;
 *
 * In View call it like this:
 * {{ asset(adminAssetsDir() . $site_settings->site_logo) }}
 *
 * @param string $role
 *
 * @return bool
 */


/**
 * Set Backend URL
 */
function backend_url($uri='/')
{
    return call_user_func_array( 'url', ['admin/' . ltrim($uri,'/')] + func_get_args() );
}

/**
 * Set Backend View
 */
function backend_view($file)
{
    return call_user_func_array( 'view', ['admin/' . $file] + func_get_args() );
}

/**
 * Set User Image
 */
function backendUserFile()
{
    return 'backends/users/';
}


/**
 * Set User Image URL
 */
function backendUserUrl($file = '')
{
    return $file != '' && file_exists('backends/users/' . $file) ? url('backends/users') . '/' . $file : '';
}

/**
 * Set User Image
 */
function backendJoeyDocumentFile()
{
    return 'backends/joeyDocument/';
}


/**
 * Set User Image URL
 */
function backendJoeyDocumentUrl($file = '')
{
    return $file != '' && file_exists('backends/joeyDocument/' . $file) ? url('backends/joeyDocument') . '/' . $file : '';
}

/**
 * Set Training Image
 */
function backendTrainingFile()
{
    return 'backends/training/';
}

/**
 * Set Joey document
 */
function backendJoeyDocumentVerificationFile()
{
    return 'backends/training/';
}

/**
 * Set Training imaGE url
 */
function backendTrainingUrl($file = '')
{
    return $file != '' && file_exists('backends/training/' . $file) ? url('backends/training') . '/' . $file : '';
}
/**
 * Set constant
 */

function constants($key)
{
    return config( 'constants.' . $key );
}

function adminHasAssets($image)
{
    if (!empty($image) && file_exists(uploadsDir() . $image)) {
        return true;
    } else {
        return false;
    }
}

function defaultStoreCoverUrl()
{
	return 'assets/front/images/store-cover.png';
}

function getFileExtension($filename, $offset = 3)
{
    return substr(strtolower($filename), strlen($filename)-$offset, $offset);
}
