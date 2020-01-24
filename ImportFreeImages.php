<?php
/**
 * Provides a way of importing properly licensed photos from flickr
 *
 * @file
 * @ingroup Extensions
 * @version 2.1
 * @author Bryan Tong Minh <bryan.tongminh@gmail.com>
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 * @link https://www.mediawiki.org/wiki/Extension:ImportFreeImages Documentation
 */

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'ImportFreeImages' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['ImportFreeImages'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['ImportFreeImagesAlias'] = __DIR__ . '/ImportFreeImages.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for the ImportFreeImages extension. ' .
		'Please use wfLoadExtension() instead, ' .
		'see https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the ImportFreeImages extension requires MediaWiki 1.29+' );
}
