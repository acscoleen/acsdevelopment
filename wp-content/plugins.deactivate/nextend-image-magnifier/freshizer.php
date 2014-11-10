<?php
/*******************************************************************************
 * FRESHIZER -> WP Image Resizer Script
 * =============================================================================
 * 
 * @license GNU version 2
 * @author freshace
 * @version 1.5
 * @link http://github.com/boobslover/freshizer
/*******************************************************************************
 * SETTINGS, PLEASE CHANGE ONLE THESE 3 CONSTANTS
 ******************************************************************************/
// NOTE
// ====
// please notice, that the time is in SECONDS. There are not allowed math
// operations in the definition. So instead of writing:
// = 60(sec) * 60(min) * 24(hr) * 7(days); you have to write:
// = 604800; // seconds in 7 days 


// CACHE TIME
// ==========
// When the new (cached) file is older than this time, script automatically
// checks, if the old file has been changed. If not, then ve serve cached file
// again. If yes, cached file is deleted and resized again.
//CONST CACHE_TIME = 604800;//604800; // (60 * 60 * 24 * 7); // 7 days

// CACHE DELETE FILES AFTER
// ========================
// Hard delete files ( not only compare if the original file has been changed,
// but hardly delete from caching folder ), every X seconds. Please fill a large
// number, because cached files runs much more speedely
//CONST CACHE_DELETE_FILES_AFTER = 0;

// CACHE DELETE FILES - check every X hits
// =======================================
// How often do we check if there are files which should be hard deleted ?
// Optimal is approx 400 - 500 hits
//CONST CACHE_DELETE_FILES_check_every_x_hits = 0;

class blFile {
	CONST POINTER_END = 'pend';
	
	var $_handle = null;
	var $_fileSize = null;
	var $_path = null;
	var $_writeBuffer = '';

/*----------------------------------------------------------------------------*/
/* FUNCTIONS PUBLIC
/*----------------------------------------------------------------------------*/

	function __construct( $handle, $path ) {
		$this->_setHandle( $handle );
		$this->_setPath( $path );
	}

	function readAll() {
		if( $this->getFileSize() > 0 )
			return (fread( $this->getHandle(), $this->getFileSize() ));
		else 
			return null;
	}

	function readAllAndClose() {
		$fileContent = $this->readAll();
		$this->closeFile();

		return $fileContent;
	}
	
	/**
	 * @param string $content
	 * @return blFile
	 */
	function write( $content ) {
		fwrite( $this->getHandle(), ( $content ) );
		return $this;
	}
	
	function writeBuffered( $content ) {
		$this->_setWriteBuffer( $content );
		return $this;
	}
	
	/*function __destruct() {
		var_dump($this);
		if( $this->_writeBuffer != '' ) {
			$this->write( $this->_writeBuffer ) ;
		}
		
		$this->closeFile();
	}*/
	
	/**
	 * @return blFile
	 */
	function truncate() {
		ftruncate( $this->getHandle(), 0);
		$this->pointerStart();
		return $this;
	}

	function closeFile() {
		if( $this->getHandle() !== null ) {
			fclose( $this->getHandle() );
			$this->_setHandle(null);
		}
	}
	
	/**
	 * @return blFile
	 */
	function pointerStart() {
		$this->_movePointer( 0 );
		return $this;
	}
	
	/**
	 * @return blFile
	 */
	function pointerEnd() {
		$this->_movePointer( self::POINTER_END );
		return $this;
	}
	
	/**
	 * @param int $where
	 * @return blFile
	 */
	function pointerTo( $where ) {
		return $this->_movePointer( $where );
		return $this;
	}

	
/*----------------------------------------------------------------------------*/
/* FUNCTIONS PRIVATE
/*----------------------------------------------------------------------------*/
	function _movePointer( $where ) { 
		if( $where == self::POINTER_END ) {
			fseek( $this->getHandle(), 0, SEEK_END);
		} else {
			fseek( $this->getHandle(), $where, SEEK_SET);
		}
	}
	
/*----------------------------------------------------------------------------*/
/* SETTERS AND GETTERS
/*----------------------------------------------------------------------------*/

	function _setWriteBuffer( $content ) {
		$this->_writeBuffer = $content;
	}
	
	function _getWriteBuffer() {
		return $this->_writeBuffer;
	}
	
	function getHandle() {
		return $this->_handle;
	}

	function _setHandle( $handle ) {
		$this->_handle = $handle;
	}

	function getPath() {
		return $this->_path;
	}

	function _setPath( $path ) {
		$this->_path = $path;
	}

	function getFileSize() {
		if( $this->_fileSize == null ) {
			$this->_fileSize = filesize( $this->getPath() );
		}

		return $this->_fileSize;
	}
}


class blFileSystem {
	var $_errors = array();
/*----------------------------------------------------------------------------*/
/* FUNCTIONS PUBLIC
/*----------------------------------------------------------------------------*/	
	
	/**
	 * Trying to open file. If neccessary, creates dir and file automatically 
	 * 
	 * @param string $path
	 * @param bool $writing
	 * @return blFile
	 */
	function openFile( $path, $writing = false ) {
	if( file_exists( $path ) ) {
			$mode = ( $writing ) ? 'r+' : 'r';
		} else {
			$mode = ( $writing ) ? 'c+' : 'c';
		}
		return $this->_openFile( $path, $mode );
	}
	
	/**
	 * Open file, if exists, truncate
	 * @param string $path
	 * @param bool $writing
	 * @return blFile
	 */
	function createFile( $path, $writing = false ) {
		$mode = ( $writing ) ? 'c+' : 'c';
		return $this->_openFile( $path, $mode );	
	}
	
	function deleteFile( $path ) {}
	
	function createDir( $path ) {
		if( mkdir( $path, 0777, true ) === false ) {
			$this->_addError( 'Unable to create DIR :'. $path );
		}
	}
	
	function saveImage( $image, $path ) {
		$pinfo = pathinfo( $path );
		$ext = $pinfo['extension'];
		$return = null;
	
		switch( $ext ) {
			case 'jpg':
				$return = imagejpeg($image, $path );
				break;
			case 'jpeg':
				$return = imagejpeg($image, $path );
				break;
			case 'png':
				$return = imagepng( $image, $path );
				break;
					
			case 'gif':
				$return = imagegif( $image, $path );
				break;
		}
	
		return $return;
	
	}	

/*----------------------------------------------------------------------------*/
/* FUNCTIONS PRIVATE
/*----------------------------------------------------------------------------*/	
	
	function _openFile( $path, $mode ) {
		$pathInfo = pathinfo( $path );
		$dirname = $pathInfo['dirname'];
		$file = null;
		
		if( !is_dir( $dirname ) ) {
			$this->createDir( $dirname );
		}
		
		$fileHandler = fopen( $path, $mode);
		$file = new blFile( $fileHandler, $path);
	
		return $file;
	}

/*----------------------------------------------------------------------------*/
/* SETTERS AND GETTERS
/*----------------------------------------------------------------------------*/
	function getErorrs() {
		return $this->_errors;
	}
	
	function _addError( $error ) {
		$this->_errors[] = $error;
	}
	
}

class blConnectionAdapteur {
	/**
	 * @var blIConnection
	 */
	var $_connectionMethod = null;
	
/*----------------------------------------------------------------------------*/
/* FUNCTIONS
/*----------------------------------------------------------------------------*/	
	
	function getContent( $url ) {
		return $this->_getConnectionMethod()->getContent($url);
	}
/*----------------------------------------------------------------------------*/
/* FUNCTIONS
/*----------------------------------------------------------------------------*/	
	
	function _createProperConnection() {
		if( ini_get('allow_url_fopen') ) {
			$this->_setConnectionMethod( new blConnectionFopen() );
		} else if( function_exists( 'curl_init') ) {
			$this->_setConnectionMethod( new blConnectionCurl() );	
		}
	}
	
/*----------------------------------------------------------------------------*/
/* GETTERS AND SETTERS
/*----------------------------------------------------------------------------*/
	
	function _setConnectionMethod( $connectionMethod ) {
		$this->_connectionMethod = $connectionMethod;
	}
	
	/**
	 * @return blIConnection
	 */
	function _getConnectionMethod() {
		if( $this->_connectionMethod == null ) {
			$this->_createProperConnection();
		}
		return $this->_connectionMethod;
	}
}


class blConnectionCurl {
	function getContent( $url ) {
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		
		return $data;		
	}
}


class blConnectionFopen {
	function getContent( $url ) {
		
		$handle = fopen( $url, 'rb' );
		$fileContent = '';
		if( $handle !== false ) {
			while (!feof($handle)) {
				$fileContent .= fread($handle, 8192);
			}
			fclose( $handle );
		}
		return $fileContent;
	}
}


class blDownloader {
	/**
	 * @var blIConnection
	 */
	var $_connectionMethod = null;
	
	function getContent( $url ) {
		return $this->_getConnectionMethod()->getContent($url);
	}
	
	function _setConnectionMethod( $connectionMethod ) {
		$this->_connectionMethod = $connectionMethod;
	}
	
	/**
	 * @return blIConnection
	 */
	function _getConnectionMethod() {
		if( $this->_connectionMethod == null ) {
			$this->_setConnectionMethod( new blConnectionAdapteur() );
		}
		return $this->_connectionMethod;
	}
}


class blImgCache {
	CONST CACHE_FILENAME = 'img_caching_info.frs';
	
	
	/**
	 * @var blFileSystem
	 */
	var $_fileSystem = null;
	
	/**
	 * @var blFile
	 */
	var $_cacheFile = null;
	
	var $_cacheFileUnparsed = null;
	
	var $_cacheFileParsed = null;
	
	var $_cacheFileDir = null;
	
	function __construct( blFileSystem $fileSystem, $cacheFileDir ) {
		
		$this->_setFileSystem($fileSystem);
		$this->_setCacheFileDir($cacheFileDir . self::CACHE_FILENAME );
		
		$this->_loadCacheFile();
		$this->_unparseCacheFile();
		
	}
	
	function addCachedFileRemote( $urlNew, $pathNew, $urlOld ) {
		$cacheFileUnparsed = $this->_getCacheFileUnparsed();
		
		if( $this->_cacheFileUnparsed == null ) {
			$this->_cacheFileUnparsed = new stdClass();
		}
		if( !isset( $cacheFileUnparsed->remoteDataHolder[ $urlOld ] ) ) {
			$cachedFile = new stdClass();
			$cachedFile->urlNew = $urlNew;
			$cachedFile->pathNew = $pathNew;
			$cachedFile->urlOld = $urlOld;
			$cachedFile->timestamp = time();
			
			$cacheFileUnparsed->remoteDataHolder[ $cachedFile->urlOld] = $cachedFile;
		}
	}
	
	function addCachedFile( $urlNew, $urlOld, $pathNew, $pathOld, $remote = false ) {
		$cacheFileUnparsed = $this->_getCacheFileUnparsed();
		
		if( $this->_cacheFileUnparsed == null ) {
			$this->_cacheFileUnparsed = new stdClass();
		}
		if( !isset( $cacheFileUnparsed->dataHolder[ $urlNew ] ) ) {
			$cachedFile = new stdClass();
			$cachedFile->urlNew = $urlNew;
			$cachedFile->urlOld = $urlOld;
			$cachedFile->pathNew = $pathNew;
			$cachedFile->pathOld = $pathOld;
			$cachedFile->remote = $remote;
			$cachedFile->timestamp = time();
			
			$cacheFileUnparsed->dataHolder[ $cachedFile->urlNew] = $cachedFile;
		}
		
		
	} 
	
	function deleteCacheInfo( $urlNew ) {
		
		
		unset( $this->_getCacheFileUnparsed()->dataHolder[ $urlNew ] );
	}
	
	function deleteRemoteCacheInfo( $url ) {
		unset( $this->_getCacheFileUnparsed()->remoteDataHolder[ $url ] );
	}
	
	function getCacheInfo( $urlNew ) {
		$cacheFileUnparsed = $this->_getCacheFileUnparsed();
		if( isset( $cacheFileUnparsed->dataHolder[ $urlNew ] ) ) {
			$cachedImageInfo = $cacheFileUnparsed->dataHolder[ $urlNew ];
			$cachedImageInfo->valid = $this->_checkExpiration($cachedImageInfo);	
			return $cacheFileUnparsed->dataHolder[ $urlNew ];
		} else {
			return null;
		}
	}
	
	function touchCachedFile( $urlNew ) {
		$cacheFileUnparsed = $this->_getCacheFileUnparsed();
		$cacheFileUnparsed->dataHolder[ $urlNew ]->valid = true;
		$cacheFileUnparsed->dataHolder[ $urlNew ]->timestamp = time();
	}
	
	function getRemoteCacheInfo( $urlOld ) {
		$cacheFileUnparsed = $this->_getCacheFileUnparsed();
		if( isset( $cacheFileUnparsed->remoteDataHolder[ $urlOld ] ) ) {
			$cachedImageInfo = $cacheFileUnparsed->remoteDataHolder[ $urlOld ];
			$cachedImageInfo->valid = $this->_checkExpiration($cachedImageInfo);
			return $cacheFileUnparsed->remoteDataHolder[ $urlOld ];
		} else {
			return null;
		}
	}
	
	function _checkExpiration( stdClass $cachedImageInfo ) {
		$currentTimestamp = time();
		$oldTimestamp = $cachedImageInfo->timestamp;
		
		if( ( $oldTimestamp + 604800 ) < $currentTimestamp ) {
			return false;
		} else {
			return true;
		}
	}
	
	/*function deleteCacheInfo( $urlNew ) {
		$cacheFileUnparsed = $this->_getCacheFileUnparsed();
		unset( $cacheFileUnparsed->dataHolder[ $urlNew ] );
		
	}*/
	
	function __destruct() {
		
		$this->saveCacheFile();
	}
	
	function saveCacheFile() {
		
		$this->_parseCacheFile();
		$this->_saveCacheFile();
	}
	
	function _parseCacheFile() {
		$cacheFileParsed = serialize( $this->_getCacheFileUnparsed() );
		$this->_setCacheFileParsed( $cacheFileParsed );
	}
	
	function _saveCacheFile() {
		if( $this->_getCacheFile()->getHandle() == null ) return ;
		$this->_getCacheFile()
				->truncate()
				->write( $this->_getCacheFileParsed() )
				->closeFile();
	}
	
	
	function _loadCacheFile() {
		$cacheFile = $this->_getFileSystem()->openFile( $this->_getCacheFileDir(), true);
		
		$this->_setCacheFile( $cacheFile );
		$this->_setCacheFileParsed( $cacheFile->readAll() );
		
	}
	
	function _unparseCacheFile() {
		if( $this->_getCacheFileParsed() != '') {
			$cacheFileContentUnparsed = unserialize( $this->_getCacheFileParsed() );
			$cacheFileContentUnparsed->hitsAfterLastDelete++;
			
			$this->_setCacheFileUnparsed( $cacheFileContentUnparsed );
		} else {
			$cacheFileContentUnparsed = new stdClass();
			$cacheFileContentUnparsed->hitsAfterLastDelete = 1;
			$this->_setCacheFileUnparsed( $cacheFileContentUnparsed );
		}
		
		if( $cacheFileContentUnparsed->hitsAfterLastDelete >= 400 ) {
			$this->_hardDeleteCache();
		}
	}
	
	function _hardDeleteCache() {
		//var_dump(400);
		$unsetArray = array();
		if( !empty( $this->_getCacheFileUnparsed()->dataHolder ) ) {
			foreach( $this->_getCacheFileUnparsed()->dataHolder as $url => $fileData ) {
				//pathNew, timestamp
				if( ( $fileData->timestamp  + 10000000 ) <= time() ) {
					$unsetArray[] = $url;
					unlink( $fileData->pathNew);
				}
			}
		}
		foreach( $unsetArray as $oneUrl ) {
			unset ($this->_getCacheFileUnparsed()->dataHolder[ $oneUrl ] );
		}
		
		$unsetArray = array();
		if( !empty( $this->_getCacheFileUnparsed()->remoteDataHolder ) ) {
			foreach( $this->_getCacheFileUnparsed()->remoteDataHolder as $url => $fileData ) {
				//pathNew, timestamp
				if( ( $fileData->timestamp  + 10000000 ) <= time() ) {
					$unsetArray[] = $url;
					unlink( $fileData->pathNew);
				}
			}
		}
		foreach( $unsetArray as $oneUrl ) {
			unset ($this->_getCacheFileUnparsed()->remoteData[ $oneUrl ] );
		}
		$this->_getCacheFileUnparsed()->hitsAfterLastDelete = 0;
	}
	
	
	function _setCacheFileParsed( $cacheFileParsed ) {
		$this->_cacheFileParsed = $cacheFileParsed;
	}
	
	function _getCacheFileParsed() {
		return $this->_cacheFileParsed;
	}
	
	function _setCacheFileUnparsed( $cacheFileUnparsed ) {
		$this->_cacheFileUnparsed = $cacheFileUnparsed;
	}
	
	function _getCacheFileUnparsed() {
		return $this->_cacheFileUnparsed;
	}
	
	function _setCacheFileDir( $cacheFileDir) {
		$this->_cacheFileDir = $cacheFileDir;
	}
	
	function _getCacheFileDir() {
		return $this->_cacheFileDir;
	}
	
	function _setCacheFile( blFile $cacheFile ) {
		$this->_cacheFile = $cacheFile;
	}
	
	/**
	 * @return blFile
	 */
	function _getCacheFile() {
		return $this->_cacheFile;
	}
	
	function _setFileSystem( blFileSystem $fileSystem ) {
		$this->_fileSystem = $fileSystem;
	}
	
	/**
	 * 
	 * @return blFileSystem
	 */
	function _getFileSystem() {
		return $this->_fileSystem;
	}
}


class blImgDownloader {
	/**
	 * @var blFileSystem
	 */
	var $_fileSystem = null;
	
	/**
	 * @var blInputStreamAdapteour
	 */
	var $_inputStream = null;
	
	function __construct( blFileSystem $fileSystem, blInputStreamAdapteour $inputStreamAdapter ) {
		$this->_setFileSystem( $fileSystem );
		$this->_setInputStream( $inputStreamAdapter );
	}
	
	function downloadImage( $originalPath, $newPath ) {
		$img = $this->_getInputStream()->open( $originalPath )->readAll();
		if( $img != null ) {
			$this->_getFileSystem()->createFile( $newPath, true)->write( $img )->closeFile();
		}
	}
	
	function _setInputStream( blInputStreamAdapteour $inputStreamAdapter ) {
		$this->_inputStream = $inputStreamAdapter;
	}
	
	/**
	 * @return blInputStreamAdapteour
	 */
	function _getInputStream() {
		return $this->_inputStream;
	}
	
	function _setFileSystem( blFileSystem $fileSystem ) {
		$this->_fileSystem = $fileSystem;
	}
	/**
	 * @return blFileSystem
	 */
	function _getFileSystem() {
		return $this->_fileSystem;
	}
}


interface blIInputStream {
	function open( $path );
	function readAll();
	
}


class blInputStreamFile implements blIInputStream {
	/**
	 * @var blFileSystem
	 */
	var $_fileSystem = null;
	
	/**
	 * @var blFile
	 */
	var $_openedFile = null;
	
	function __construct() {
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see blIInputStream::open()
	 */
	function open( $path ) {
		$file = $this->_getFileSystem()->openFile($path);
		$this->_setFile( $file );
		return $this;
	}
	
	function readAll() { 
		return $this->_getFile()->readAllAndClose();
	}
	
/*----------------------------------------------------------------------------*/
/* SETTERS AND GETTERS
/*----------------------------------------------------------------------------*/
	function _setFileSystem( blFileSystem $fileSystem ) {
		$this->_fileSystem = $fileSystem;
	}
	
	/**
	 * @return blFileSystem
	 */
	function _getFileSystem() {
		if( $this->_fileSystem == null ) {
			$this->_setFileSystem( new blFileSystem() );
		}
		
		return $this->_fileSystem;
	}
	
	function _setFile( blFile $file ) {
		$this->_openedFile = $file;
	}
	
	/**
	 * @return blFile
	 */
	function _getFile() {
		return $this->_openedFile;
	}
}


class blInputStreamHttp implements blIInputStream {
	/**
	 * @var blDownloader
	 */
	var $_downloader = null;
	var $_pageContent = '';
	
	function open( $path ) {
		$content = $this->_getDownloader()->getContent( $path );
		$this->_setPageContent( $content );
	}
	
	function readAll() {
		return $this->_getPageContent();
	}
	
	/**
	 * @return blDownloader
	 */
	function _getDownloader() {
		if( $this->_downloader == null ) {
			$this->_downloader = new blDownloader();
		}
		
		return $this->_downloader;
	}
	
	function _setPageContent( $pageContent ) {
		$this->_pageContent = $pageContent;
	}
	
	function _getPageContent() {
		return $this->_pageContent;
	}
}


class blInputStreamAdapteour implements blIInputStream {
	/**
	 * 
	 * @var blIInputStream
	 */
	var $_inputStream = null;
	
/*----------------------------------------------------------------------------*/
/* FUNCTIONS
/*----------------------------------------------------------------------------*/
		
	function open( $path ) {
		$this->_createInputStream( $path );
		$this->_getInputStream()->open($path);
		return $this;
	}
	
	function readAll() {
		return $this->_getInputStream()->readAll();
	}
	
/*----------------------------------------------------------------------------*/
/* FUNCTIONS
/*----------------------------------------------------------------------------*/	
	function _createInputStream( $path ) {
		if( strpos( $path, 'http://') !== false ) {
			$this->_setInputStream( new blInputStreamHttp() );
		} else {
			$this->_setInputStream( new blInputStreamFile() );
		}
	}
/*----------------------------------------------------------------------------*/
/* SETTERS AND GETTERS
/*----------------------------------------------------------------------------*/
	
	function _setInputStream(  blIInputStream $inputStream ) {
		$this->_inputStream = $inputStream;
	}
	
	function _getInputStream() {
		return $this->_inputStream;
	}
}


class fImgOneData {
	var $path = null;
	var $url = null;
	var $filename = null;
	var $width = null;
	var $height = null;
	var $timestamp = null;
	var $crop = null;
}

class fImgData {
	/**
	 * 
	 * @var fImgOneData
	 */
	var $new = null;
	
	/**
	 * 
	 * @var fImgOneData
	 */
	var $old = null;
	
	var $remote = false;
	var $ready = false;
	
	function __construct() {
		$this->new = new fImgOneData();
		$this->old = new fImgOneData();
	}
	
}




class fImgDeliverer {
	/**
	 * @var blFileSystem
	 */
	var $_fileSystem = null;
	
	
	/**
	 * 
	 * @var blInputStreamAdapteour
	 */
	var $_inputStream = null;
	
	/**
	 * @var blImgCache
	 */
	var $_imgCache = null;
	
	/**
	 * 
	 * @var fImgPathPredictor
	 */
	var $_imgPredictor = null;
	
	
	/**
	 * 
	 * @var blImgDownloader
	 */
	var $_imgDownloader = null;
	
	var $_uploadDir = null;
	
	var $_uploadUrl = null;
	
	/**
	 * 
	 * @var fImgNamer
	 */
	var $_imgNamer = null;
	
	function __construct( blFileSystem $fileSystem,  $inputStream, blImgCache $imgCache, $uploadDir, $uploadUrl ) {
		$this->_setFileSystem( $fileSystem );
		$this->_setInputStream( $inputStream );
		$this->_setImgCache( $imgCache);
		$this->_setUploadDir( $uploadDir );
		$this->_setUploadUrl( $uploadUrl );
	}
	
	/**
	 *
	 * @param fImgData $imgData
	 * @return  fImgData
	 */
	function deliveryImage( fImgData $imgData ) {
		$result = $this->_deliveryFromCache( $imgData );
		//$result = false;
		if( $result === false )
			$result = $this->_deliveryFromLocal( $imgData );
		if( $result === false )
			$result = $this->_deliveryFromRemote( $imgData );
		
		return $result;
	}
	
	function _deliveryFromCache( fImgData $imgData ) {
		
		$cacheInfo = $this->_getImgCache()->getCacheInfo( $imgData->new->url );
		
		if( $cacheInfo == null ) return false;
		
		$imgData->new->path = $this->_getUploadDirPath( $imgData->new->filename );
		$cacheValidity = $this->_checkCacheValidity( $imgData, $cacheInfo );
		if( $cacheValidity === false ) return false;
		
		$imgData->ready = true;
		
		return $imgData;
	}
	
	function _checkCacheValidity( fImgData $imgData, $cacheInfo ) {
		if( $cacheInfo->valid == true ) return true;
		
		if( !file_exists( $cacheInfo->pathOld) ) {
			$this->_getImgCache()->deleteCacheInfo( $imgData->new->url );
			$this->_getFileSystem()->deleteFile( $imgData->new->path );
			return false;
		}
		$newTS = $cacheInfo->timestamp;
		$oldTS = filemtime( $cacheInfo->pathOld );
		if( $newTS > $oldTS ) {
			$this->_getImgCache()->touchCachedFile( $imgData->new->url );
			return true;
		} else {
			$this->_getImgCache()->deleteCacheInfo( $imgData->new->url );
			$this->_getFileSystem()->deleteFile( $imgData->new->path );			
			return false;
		}
	} 
	
	function _deliveryFromLocal( fImgData $imgData ) {
		$path = $this->_getImgPredictor()->predictPath( $imgData->old->url );
		
		if( $path != null ) {
			$imgData->old->path = $path;
			$imgData->new->path = $this->_getUploadDirPath( $imgData->new->filename );
			$imgData->ready = false;
			
			return $imgData;
		} else {
			return false;
		}
	}	
	
	
	function _deliveryFromRemote( fImgData $imgData ) {

		$remoteFilename = $this->_getImgNamer()->getRemoteImageName( $imgData->old->url );
		$remotePath = $this->_getUploadDirPath('remote/' . $remoteFilename);
		$remoteUrl = $this->_getUploadUrlPath('remote/' . $remoteFilename);
		
		$remoteFileCacheInfo = $this->_getImgCache()
										->getRemoteCacheInfo($imgData->old->url);
										//->getCacheInfo( $remotePath);
	
		if( $remoteFileCacheInfo != null && $remoteCacheInfo->valid == false ) {
		
			$remoteFileCacheInfo = null;
			$this->_getImgCache()->deleteRemoteCacheInfo( $remoteUrl );
			$this->_getFileSystem()->deleteFile( $remotePath );
		}
			
		if( $remoteFileCacheInfo == null ) {
	
			$this->_getImgDownloader()->downloadImage($imgData->old->url, $remotePath );
			if( file_exists( $remotePath) ) {
				$this->_getImgCache()->addCachedFileRemote($remoteUrl, $remotePath, $imgData->old->url);
				//$this->_getImgCache()->addCachedFile($urlNew, $urlOld, $pathNew, $pathOld, $remote)
			}
		}
		$imgData->new->path = $this->_getUploadDirPath( $imgData->new->filename );
		$imgData->old->url = $remoteUrl;
		$imgData->old->path = $remotePath;
		$imgData->ready = false;
		$imgData->remote = true;

		return $imgData;
	}

	
	
/*----------------------------------------------------------------------------*/
/* SETTERS AND GETTERS
/*----------------------------------------------------------------------------*/
	function _getUploadDirPath( $path ) {
		return $this->_getUploadDir() . $path;
	}
	
	function _getUploadUrlPath ( $path ) {
		return $this->_getUploadUrl() .'/'. $path;
	}
		
	
	function _getUploadUrl() {
		return $this->_uploadUrl;
	}
	
	function _setUploadUrl( $uploadUrl ) { 
		$this->_uploadUrl = $uploadUrl;
	}
	function _getImgDownloader() {
		if( $this->_imgDownloader == null ) {
			$this->_imgDownloader = new blImgDownloader( $this->_getFileSystem(), $this->_getInputStream() );
		}
		
		return $this->_imgDownloader;
	}
	
	function _getImgNamer() { 
		if( $this->_imgNamer == null ) { 
			$this->_imgNamer = new fImgNamer();
		}
		
		return $this->_imgNamer;
	}
	
	function _setUploadDir( $uploadDir ) {
		$this->_uploadDir = $uploadDir;
	}
	
	function _getUploadDir() {
		return $this->_uploadDir;
	}
	
	/**
	 * @return fImgPathPredictor
	 */
	function _getImgPredictor() {
		if( $this->_imgPredictor == null ) {
			$this->_imgPredictor = new fImgPathPredictor();
		}
		
		return $this->_imgPredictor;
	}
	
	function _setImgCache( blImgCache $imgCache ) {
		$this->_imgCache = $imgCache;
	}
	
	/**
	 * 
	 * @return blImgCache
	 */
	function _getImgCache(){
		return $this->_imgCache;
	}
	function _setFileSystem( blFileSystem $fileSystem ) {
		$this->_fileSystem = $fileSystem;
	}
	
	/**
	 * @return blFileSystem
	 */
	function _getFileSystem() { 
		return $this->_fileSystem;
	}
	
	function _setInputStream( blInputStreamAdapteour $inputStream ) {
		$this->_inputStream = $inputStream;
	}
	
	/**
	 * @return blInputStreamAdapteour
	 */
	function _getInputStream() {
		return $this->_inputStream;
	}
}


class fImgNamer {
	var $_defaultUrl = null;
	var $_temporaryPathInfo = null;
	
	function __construct( $defaultUrl = null ) {
		$this->_setDefaultUrl( $defaultUrl ); 
	}
	function getNewImageName( $oldUrl, $width, $height = false, $crop = false, $remote = false) {
		$newUrl =  '';
		
		$partRemote = ( $remote ) ? 'remote/' : '';
		$partWidth = '-'.$width;
		$partHeight = ( $height ) ? '-'.$height : '';
		$partCrop =   ( $crop )   ? '-c' : '';
		
		//$newUrl .= $this->_getDefaultUrl() .'/';
		$newUrl .= $partRemote;
		$newUrl .= $this->_getUrlHash( $oldUrl ) . '_' ;
		$newUrl .= $this->_getImgName( $oldUrl );
		$newUrl .= $partWidth;
		$newUrl .= $partHeight;
		$newUrl .= $partCrop;
		$newUrl .= $this->_getImgExtension();
		
		return $newUrl;
	}
	
	function getNewImageUrl( $oldUrl, $width, $height = false, $crop = false, $remote = false ) {
		/**
		 * http://defaulturl(freshizer)/[remote]/$oldUrlHash_imgFilename-width[-height][-c(rop)].ext
		 */
		$newUrl =  '';
		
		$partRemote = ( $remote ) ? 'remote/' : '';
		$partWidth = '-'.$width;
		$partHeight = ( $height ) ? '-'.$height : '';
		$partCrop =   ( $crop )   ? '-c' : '';
		
		$newUrl .= $this->_getDefaultUrl() .'/';
		$newUrl .= $partRemote;
		$newUrl .= $this->_getUrlHash( $oldUrl ) . '_' ;
		$newUrl .= $this->_getImgName( $oldUrl );
		$newUrl .= $partWidth;
		$newUrl .= $partHeight;
		$newUrl .= $partCrop;
		$newUrl .= $this->_getImgExtension();
		
		
		return $newUrl;
 	}
 	
 	function getRemoteImageName( $url ) {
 		$pathInfo = pathinfo( $url );
 		$newName = '';
 		$newName .= $this->_getUrlHash( $url );
 		$newName .= '-'.$pathInfo['filename'].'.'.$pathInfo['extension'];
 		
 		return $newName;
 	}
 	
 	function _getImgExtension() {
 		$pathInfo = $this->_getTemporaryPathInfo();
 		return '.'.$pathInfo['extension'];
 	}
 	
 	function _getImgName( $oldUrl ) {
 		$pathInfo = pathinfo( $oldUrl );
 		$this->_setTemporaryPathInfo( $pathInfo );
 		return $pathInfo['filename'];
 	}
 	
 	function _setTemporaryPathInfo( $pathInfo ) {
 		$this->_temporaryPathInfo = $pathInfo;
 	}
 	
 	function _getTemporaryPathInfo() {
 		return $this->_temporaryPathInfo;
 	}
 	
 	function _getUrlHash( $url ) {
 		return md5($url);
 	}
	
	function _setDefaultUrl( $defaultUrl ) {
		$this->_defaultUrl = $defaultUrl;
	}
	
	function _getDefaultUrl() {
		return $this->_defaultUrl;
	}
}

class fImgPathPredictor {
	
	/**
	 * 
	 * @var fIImgPathPredictor
	 */
	var $_predictor = null;
	
	function predictPath( $url ) {
		return $this->_getPredictor()->predictPath( $url );
	}
	
	
	function _initializePredictor() {
		global $blog_id;
		
		if( is_multisite() && $blog_id != 1) { 
			$this->_setPredictor( new fImgPathPredictor_Multisite() );
		} else {
			$this->_setPredictor( new fImgPathPredictor_Single() );
		}
	}
	
	function _setPredictor( $predictor) {
		$this->_predictor = $predictor;
	}
	
	/**
	 * @return fIImgPathPredictor
	 */
	function _getPredictor() {
		if( $this->_predictor == null ) {
			$this->_initializePredictor();
		}
		
		return $this->_predictor;
	}
}
class fImgPathPredictor_Multisite {
	var $_imgUrl = null;
	var $_predictedPath = null;

	function predictPath( $url ) {
		$this->_setImgUrl( $url );
		$this->_predictionJunction();

		return $this->_getPredictedPath();
	}

	function _predictionJunction() {
		//echo $this->_getImgUrl().'xxxx';
		//return;
		$uploadDir = wp_upload_dir();

		if( strpos( $this->_getImgUrl(), $uploadDir['baseurl']) !== false ) {
			$this->_predictUploads();
		} else if ( strpos( $this->_getImgUrl(), 'wp-content/themes') !== false ) {
			$this->_predictThemes();
		} else if ( strpos( $this->_getImgUrl(), 'wp-content/themes') !== false ) {
			$this->_predictPlugins();
		}

	}

	function _predictUploads() {
		$uploadDir = wp_upload_dir();
		$uploadSubpath = str_replace( $uploadDir['baseurl'],'', $this->_getImgUrl());

		$newRelPath = $uploadDir['basedir'].$uploadSubpath;

		if( file_exists( $newRelPath) ) {
			$this->_setPredictedPath( $newRelPath );
		}


	}
	function _predictThemes() {
		$splitedUrl = explode('themes/', $this->_getImgUrl() ); //explode() $this->_getImgUrl();
		$splitedPath = explode('themes/', TEMPLATEPATH);
		$newRelPath = $splitedPath[0].'themes/'.$splitedUrl[1];

		if( file_exists( $newRelPath )) {
			$this->_setPredictedPath( $newRelPath );
		}
	}
	function _predictPlugins() {
		$imgPluginDirSplitted = explode('wp-content/plugins', $this->_getImgUrl() );
		$imgAfterPluginDir = $imgPluginDirSplited[1];

		$pluginDir = WP_PLUGIN_DIR;
		$newRelPath = $pluginDir . $imgAfterPluginDir;

		if( file_exists( $newRelPath ) ) {
			$this->_setPredictedPath( $newRelPath );
		}

	}


	function _getPredictedPath() {
		return $this->_predictedPath;
	}

	function _setPredictedPath( $predictedPath ) {
		$this->_predictedPath = $predictedPath;
	}

	function _setImgUrl( $imgUrl ) {
		$this->_imgUrl = $imgUrl;
	}

	function _getImgUrl() {
		return $this->_imgUrl;
	}
}

class fImgPathPredictor_Single {
	var $_imgUrl = null;
	var $_predictedPath = null;
	
	function predictPath( $url ) {
		$this->_setImgUrl( $url );
		$this->_predictionJunction();
		return $this->_getPredictedPath();
	}
	
	function _predictionJunction() {
		if( strpos( $this->_getImgUrl(), 'wp-content/uploads') !== false ) {
			$this->_predictUploads();
		} else if ( strpos( $this->_getImgUrl(), 'wp-content/themes') !== false ) {
			$this->_predictThemes();
		} else if ( strpos( $this->_getImgUrl(), 'wp-content/themes') !== false ) {
			$this->_predictPlugins();
		}		
		
	}
	
	function _predictUploads() {
		$imgUploadDirSplited = explode('wp-content/uploads', $this->_getImgUrl() );
		$imgAfterUploadDir = $imgUploadDirSplited[1];
		$wpUploadDir = wp_upload_dir();
		$baseDir = $wpUploadDir['basedir'];
		
		$newRelPath = $baseDir . $imgAfterUploadDir;
		
		if( file_exists( $newRelPath) ) {
			$this->_setPredictedPath( $newRelPath );
		}
		
		
	}
	function _predictThemes() {
		$imgThemeDirSplited = explode('wp-content/themes', $this->_getImgUrl() );
		$imgAfterThemeDir = $imgThemeDirSplited[1];
		
		$currentThemeDirSplited = explode( 'wp-content/themes', TEMPLATEPATH);
		$currentThemeFolder = $currentThemeDirSplited[0];
		
		$newRelPath = $currentThemeFolder . 'wp-content/themes' . $imgAfterThemeDir;
		
		if( file_exists( $newRelPath )) {
			$this->_setPredictedPath( $newRelPath );
		}
	}
	function _predictPlugins() {
		$imgPluginDirSplitted = explode('wp-content/plugins', $this->_getImgUrl() ); 
		$imgAfterPluginDir = $imgPluginDirSplited[1];
		
		$pluginDir = WP_PLUGIN_DIR;
		$newRelPath = $pluginDir . $imgAfterPluginDir;
		
		if( file_exists( $newRelPath ) ) {
			$this->_setPredictedPath( $newRelPath );
		}

	}
	
	
	function _getPredictedPath() {
		return $this->_predictedPath;
	}
	
	function _setPredictedPath( $predictedPath ) {
		$this->_predictedPath = $predictedPath;
	}
	
	function _setImgUrl( $imgUrl ) {
		$this->_imgUrl = $imgUrl;
	}
	
	function _getImgUrl() {
		return $this->_imgUrl;
	}
}


class fImgResizer {
	
	/**
	 * 
	 * @var blFileSystem
	 */
	var $_fileSystem = null;
	
	/**
	 * @var fImgResizerCalculator
	 */
	var $_imgResizerCalculator = null;
	
	function __construct( blFileSystem $fileSystem ) {
		$this->_setFileSystem($fileSystem);
	}
	
	function resize( fImgData $imgData ) {// stdClass $pathInfo, stdClass $imgInfo ) {
		$imageOld = $this->_openImage( $imgData->old->path );
		$orig = $this->_getImgDimensions( $imgData->old->path );
		
	
		$newDimensions = $this->_getImgResizerCalculator()->calculateNewDimensions( $orig->width, 
																					$orig->height, 
																					$imgData->new->width, 
																					$imgData->new->height, 
																					$imgData->new->crop );
		
		$imageNew = $this->_createImage( $newDimensions['dst']['w'],$newDimensions['dst']['h']);
		
		
		imagecopyresampled($imageNew, $imageOld, $newDimensions['dst']['x'],
												 $newDimensions['dst']['y'],
												 $newDimensions['src']['x'],
												 $newDimensions['src']['y'],
												 $newDimensions['dst']['w'],
												 $newDimensions['dst']['h'],
												 $newDimensions['src']['w'],
												 $newDimensions['src']['h']);

		
		$this->_getFileSystem()->saveImage( $imageNew, $imgData->new->path );
		imagedestroy($imageOld);
		imagedestroy($imageNew);

	}
	
	function _openImage( $path ) {
		$imageString = $this->_getFileSystem()->openFile( $path )->readAllAndClose();

		@ini_set( 'memory_limit', '256M' );
		$image = imagecreatefromstring( $imageString );
		return $image;
	}
	
	
	function _getImgDimensions( $path ) {
		$dim = getimagesize( $path );
		$result = new stdClass();
		$result->width = $dim[0];
		$result->height = $dim[1];
		return $result;
	}
	function _createImage ($width, $height) {
		$img = imagecreatetruecolor($width, $height);
		if ( is_resource($img) && function_exists('imagealphablending') && function_exists('imagesavealpha') ) {
			imagealphablending($img, false);
			imagesavealpha($img, true);
		}
		return $img;
	}	
	
/*----------------------------------------------------------------------------*/
/* SETTERS AND GETTERS
/*----------------------------------------------------------------------------*/
	function _getImgResizerCalculator() {
		if( $this->_imgResizerCalculator == null ) { 
			$this->_imgResizerCalculator = new fImgResizerCalculator();
		}
		
		return $this->_imgResizerCalculator;
	}
	
	/**
	 * @return blFileSystem
	 */
	function _getFileSystem() {
		return $this->_fileSystem;
	}
	
	function _setFileSystem( $fileSystem ) {
		$this->_fileSystem = $fileSystem;
	}
}


class fImgResizerCalculator {
	function calculateNewDimensions($orig_w, $orig_h, $dest_w, $dest_h, $crop = false) {
	
		if ( $crop ) {
	
			// crop the largest possible portion of the original image that we can size to $dest_w x $dest_h
			$aspect_ratio = $orig_w / $orig_h;
			$new_w =$dest_w;// min($dest_w, $orig_w);
			$new_h =$dest_h;// min($dest_h, $orig_h);
	
			if ( !$new_w ) {
				$new_w = intval($new_h * $aspect_ratio);
			}
	
			if ( !$new_h ) {
				$new_h = intval($new_w / $aspect_ratio);
			}
	
			$size_ratio = max($new_w / $orig_w, $new_h / $orig_h);
	
			$crop_w = round($new_w / $size_ratio);
			$crop_h = round($new_h / $size_ratio);
	
			$s_x = floor( ($orig_w - $crop_w) / 2 );
			$s_y = floor( ($orig_h - $crop_h) / 2 );
		} else {
			// don't crop, just resize using $dest_w x $dest_h as a maximum bounding box
			$crop_w = $orig_w;
			$crop_h = $orig_h;
	
			$s_x = 0;
			$s_y = 0;
	
	
			list( $new_w, $new_h ) = $this->constrainNewDimensions( $orig_w, $orig_h, $dest_w, $dest_h );
		}
		$to_return = array();
		$to_return['src']['x'] = (int)$s_x;
		$to_return['src']['y'] = (int)$s_y;
		$to_return['src']['w'] = (int)$crop_w;
		$to_return['src']['h'] = (int)$crop_h;
	
		$to_return['dst']['x'] = 0;
		$to_return['dst']['y'] = 0;
		$to_return['dst']['w'] = (int)$new_w;
		$to_return['dst']['h'] = (int)$new_h;
		 
		return $to_return;
	}
	
	/**
	 * This function has been take over from wordpress core. It calculate the best proportion to uncropped image
	 */
	function constrainNewDimensions( $current_width, $current_height, $max_width=0, $max_height=0 ) {
	
		if ( !$max_width and !$max_height )
			return array( $current_width, $current_height );
	
		$width_ratio = $height_ratio = 1.0;
		$did_width = $did_height = false;
	
		if ( $max_width > 0 && $current_width > 0 )
		{
			$width_ratio = $max_width / $current_width;
			$did_width = true;
		}
	
		if ( $max_height > 0 && $current_height > 0 )
		{
			$height_ratio = $max_height / $current_height;
			$did_height = true;
		}
	
		// Calculate the larger/smaller ratios
		$smaller_ratio = min( $width_ratio, $height_ratio );
		$larger_ratio  = max( $width_ratio, $height_ratio );
	
		if ( intval( $current_width * $larger_ratio ) > $max_width || intval( $current_height * $larger_ratio ) > $max_height )
			// The larger ratio is too big. It would result in an overflow.
			$ratio = $smaller_ratio;
		else
			// The larger ratio fits, and is likely to be a more "snug" fit.
			$ratio = $larger_ratio;
		$w = intval( round($current_width  * $ratio ));
		$h = intval( round($current_height * $ratio ));
	
		// Sometimes, due to rounding, we'll end up with a result like this: 465x700 in a 177x177 box is 117x176... a pixel short
		// We also have issues with recursive calls resulting in an ever-changing result. Constraining to the result of a constraint should yield the original result.
		// Thus we look for dimensions that are one pixel shy of the max value and bump them up
		if ( $did_width && $w == $max_width - 1 )
			$w = $max_width; // Round it up
		if ( $did_height && $h == $max_height - 1 )
			$h = $max_height; // Round it up
		return array( $w, $h );
	}
}

class fImg {
	/**
	 * @var fImg
	 */
	static $_instance = null;

	/**
	 * @var fImgNamer
	 */
	var $_imgNamer = null;
	
	/**
	 * @var blFileSystem
	 */
	var $_fileSystem = null;

	/**
	 * @var blImgCache
	 */
	var $_imgCache = null;
	
	/**
	 * @var fImgDeliverer
	 */
	var $_imgDeliverer = null;
	
	/**
	 * @var blInputStreamAdapteour
	 */
	var $_inputStream = null;
	
	/**
	 * @var fImgResizer
	 */
	var $_imgResizer = null;
	
	
	var $_defaultUrl = null;
	
	var $_defaultDir = null;
	
/*----------------------------------------------------------------------------*/
/* FUNCTIONS
/*----------------------------------------------------------------------------*/
		
	function __construct() {
		$this->_createDefaultUrlAndDir();
		$this->_setImgCache( new blImgCache( $this->_getFileSystem(), $this->_getDefaultDir() ) );
		$this->_setImgDeliverer( new fImgDeliverer( $this->_getFileSystem(), $this->_getInputStream(), $this->_getImgCache(), $this->_getDefaultDir(), $this->_getDefaultUrl() ) );
		$this->_setImgResizer( new fImgResizer( $this->_getFileSystem() ) );
	}
	
	function getInstance() {
		if( self::$_instance == null ) {
			self::$_instance = new fImg();
		}
		
		return self::$_instance;
	}

	
	static function ResizeC( $url, $width, $height = false, $crop = false ) {
		$width = (int)$width;
		$height = (int)$height;
		
		return self::getInstance()->_resize($url, $width, $height, $crop);
	}
	

	static function resize( $url, $width, $height = false, $crop = false ) {
		$width = (int)$width;
		$height = (int)$height;
		
		return self::getInstance()->_resize($url, $width, $height, $crop);
	}	
	
	
	function _resize( $url, $width, $height = false, $crop = false ) {

		$imgData = $this->_getImgData($url, $width, $height, $crop);
		$imgData = $this->_getImgDeliverer()->deliveryImage( $imgData );
		if( $imgData == false ) {
			echo 'Image :'.$url.' cannot be opened';
			return false; 
		}
		
		if( $imgData->ready == false ) {
			
			$this->_getImgResizer()->resize( $imgData );
			$this->_getImgCache()
					->addCachedFile( $imgData->new->url, $imgData->old->url, $imgData->new->path, $imgData->old->path, $imgData->remote);
		}
		
		return $imgData->new->url;
	}	
	
/*----------------------------------------------------------------------------*/
/* FUNCTIONS
/*----------------------------------------------------------------------------*/
	/**
	 * @return fImgData
	 */
	function _getImgData( $url, $width, $height, $crop ) {
		$imgData = $this->_getImageInfoAsClass($url, $width, $height, $crop);
		$imgData->new->url = $this->_getImgNamer()->getNewImageUrl( $url, $width, $height, $crop);
		$imgData->new->filename = $this->_getImgNamer()->getNewImageName($url, $width, $height, $crop);
	
		return $imgData;
	}	
	
	/**
	 * 
	 * @return fImgData
	 */
	function _getImageInfoAsClass( $url, $width, $height,$crop) {
		$imgData = new fImgData();
		$imgData->old->url = $url;
		$imgData->new->width = (int)$width;
		$imgData->new->height = (int)$height;
		$imgData->new->crop = $crop;

		return $imgData;
	}	
	
	function _createDefaultUrlAndDir() {
		$wpUploadDir = wp_upload_dir();
		$this->_setDefaultUrl( $wpUploadDir['baseurl'].'/freshizer');
		$this->_setDefaultDir( $wpUploadDir['basedir'].'/freshizer/');
	}
		
/*----------------------------------------------------------------------------*/
/* SETTERS AND GETTERS
/*----------------------------------------------------------------------------*/
	function _setImgResizer( fImgResizer $imgResizer ) {
		$this->_imgResizer = $imgResizer;
	}
	/**
	 * @return fImgResizer
	 */
	function _getImgResizer() {
		return $this->_imgResizer;
	}
	
	function _setImgDeliverer( fImgDeliverer $imgDeliverer ) {
		$this->_imgDeliverer = $imgDeliverer;
	}
	
	/**
	 * @return fImgDeliverer
	 */
	function _getImgDeliverer() {
		return $this->_imgDeliverer;
	}
	
	function _setInputStream( blInputStreamAdapteour $inputStream ) {
		$this->_inputStream = $inputStream;
	}
	
	/**
	 * 
	 * @return blInputStreamAdapteour
	 */
	function _getInputStream() {
		if( $this->_inputStream == null ) {
			$this->_inputStream =  new blInputStreamAdapteour() ;
		}
		return $this->_inputStream;
	}
	
	function _setDefaultUrl( $defaultUrl ) {
		$this->_defaultUrl = $defaultUrl;
	}
	function _getDefaultUrl() {
		return $this->_defaultUrl;
	}
	
	function _getImgNamer() {
		if( $this->_imgNamer == null ) {
			$this->_imgNamer = new fImgNamer($this->_getDefaultUrl());
		}
		return $this->_imgNamer;
	}
	
	function _getImgCache() {
		return $this->_imgCache;
	}
	
	function _setImgCache( blImgCache $imgCache ) {
		$this->_imgCache = $imgCache;
	}
	
	function _getFileSystem() {
		if( $this->_fileSystem == null ) {
			$this->_fileSystem = new blFileSystem();
		}
		return $this->_fileSystem;
	}
	
	function _setDefaultDir( $defaultDir) { 
		$this->_defaultDir = $defaultDir;
	}
	
	function _getDefaultDir() { 
		return $this->_defaultDir;
	}
	
	static function DeleteCache() {
		
	}
}