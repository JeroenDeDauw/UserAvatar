<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die();
}

class UserAvatar {

	/**
	 * @param $out OutputPage
	 * @param $parserOutput ParserOutput
	 * @return bool
	*/
	public static function onOutputPageParserOutput( $out, $parserOutput ) {
	
		// Get page from H1 -> https://doc.wikimedia.org/mediawiki-core/master/php/html/classOutputPage.html#a123901ea83961422ed94ba850d396ec1
		// Careful https://www.mediawiki.org/wiki/Manual:$wgAllowDisplayTitle
		// $title = $out->getPageTitle();
		
		// https://www.mediawiki.org/wiki/RequestContext
		$context = new RequestContext();
		$title = $context->getTitle();
		
		// https://www.mediawiki.org/wiki/Manual:Title.php
		$titleText = $title->getText();
		
		// Namespaces https://www.mediawiki.org/wiki/Help:Namespace
		if ( $title->getNamespace() == NS_USER ) {
			
			# User class from name
			#https://doc.wikimedia.org/mediawiki-core/master/php/html/classUser.html#ae4fdc63272b943d3b74fabeee106cb9b
			$user = User::newFromName( $titleText );
			
			$file = self::getFilefromUser( $user );
			
			// Since output is HTML we put URL directly
			$out->prependHTML(
				"<div class='useravatar-profile'>" .
				Html::element(
					'img',
					array(
						'src' => $file->getUrl(),
						'alt' => $user->getName(),
						'data-username' => $user->getName(),
					)
				) .
				'<p>' . htmlspecialchars( $titleText ) . '</p>' .
				"</div>"
			);
		}
		
		
		// Other things you can do: http://www.mediawiki.org/wiki/Manual:OutputPage.php
		
		return true;
	
	}

	/**
	 * @param $data string
	 * @param $skin Skin
	 * @return bool
	*/
	public static function onSkinAfterContent( &$data, $skin ) {

		$title = $skin->getTitle();

		// Get Last revision
		# https://doc.wikimedia.org/mediawiki-core/master/php/html/classRevision.html#a510568f576b3b3ab6e64abcbde5fd48e
		$last_revision = Revision::newFromTitle( $title );
		
		if ( $last_revision ) {
			// Get user of revision
			$userid = $last_revision->getUser();
			$user = User::newFromId( $userid );
		
			// Let's get the file
			$file = self::getFilefromUser( $user );
	
			if ( $file ) {	
				// Let's get the user page
				$userpage = $user->getUserPage();
		
				// User profile link
				$userlink = $userpage->getLocalURL();
		
				// HTML OUTPUT
				$data.= "<div class='useravatar-lastedit'><span class='label'>Last edition by:</span> <a href='".$userlink."'><img src='".$file->getUrl()."' alt='".$user->getName()."'></a></div>";
		
			}
		}
		
		return true;
	
	}
	
	/**
	 * @param $out OutputPage
	 * @param $text string
	 * @return $out OutputPage
	*/
	public static function onOutputPageBeforeHTML( &$out, &$text ) {
	
		// We add Modules
		$out->addModules( 'ext.UserAvatar' );
		
		return $out;
	}
	
	/**
	 * @param $username string
	 * @return string
	**/
	
	public static function getUserInfo( $username ) {
	
		// Create user
		$user = User::newFromName( $username );
		
		if ( $user ) {
			$timestamp = $user->getRegistration();
			// We could format timestamp
			return $timestamp;
		}
		
		return '';
	}
	
	
	/**
	 * @param $user User
	 * @return File
	*/
	
	private static function getFilefromUser( $user ) {
	
		// Let's retrieve username from user object
		$username = $user->getName();
		
		// Check name;
		if ( empty( $username ) ) {
			return "";
		}
	
		// We assume all files are User-username.jpg
		$filename = "User-".$username.".jpg";
		
		// wfFindFile https://doc.wikimedia.org/mediawiki-core/master/php/html/GlobalFunctions_8php.html#a6f62cc18b743211dffaf3919d8d1dfdf
		// http://www.mediawiki.org/wiki/Manual:GlobalFunctions.php
	
		// Returns a file object
		$file = wfFindFile( $filename );
		
		if ( ! $file ) {
			return "";
		}
		
		// https://doc.wikimedia.org/mediawiki-core/master/php/html/classFile.html
		return $file;
	
	}
	
	/**
	 * @param $input string
	 * @param $args array
	 * @param $parser Parser
	 * @param $frame Frame 
	 * @return string
	*/
	
	public static function printTag( $input, $args, $parser, $frame ) {
	
		$width = "50px";
		
		if ( isset( $args['width'] ) && is_numeric( $args['width'] ) ) {
			$width = $args['width']."px";
		}
		
		if ( !empty( $input ) ) {
			$username = trim( $input );
			$user = User::newFromName( $username );
			
			// If larger than 0, user exists
			if ( $user->getId() > 0 ) {
				$file = self::getFilefromUser( $user );
				
				if ( $file ) {
					
					$data = "<div class='useravatar-output'><img src='".$file->getUrl()."' width='".$width."' alt='".$user->getName()."'></div>";
					return $data;
					
				} else {
					return "No file associated to the user!";
				}
			}
		}
		
		return ( "No existing user associated!" );
		
	}
	

	/**
	 * @param $parser Parser
	 * @param $frame PPFrame
	 * @param $args array
	 * @return string
	*/

	public static function printFunction( $parser, $frame, $args ) {
		
		if ( isset( $args['0'])  && !empty( $args[0] ) ) {

			$width = "50px";
			
			if ( isset( $args['1'] ) && is_numeric( $args[1] ) ) {
				$width = $args[1]."px";
			}
			
			$username = trim( $args[0] );
			$user = User::newFromName( $username );
			
			// If larger than 0, user exists
			if ( $user->getId() > 0 ) {
				$file = self::getFilefromUser( $user );
				
				if ( $file ) {
					
					// We return wiki text -> We can control more: https://www.mediawiki.org/wiki/Manual:Parser_functions#Parser_interface
					$data = "<div class='useravatar-output'>[[File:".$file->getName()."|".$width."px|link=User:".$user->getName()."]]</div>";
					return $data;
					
				} else {
					return "No file associated to the user!";
				}
			}

		} else {
			return "No existing user associated!";
		}
		
		
	}

}
