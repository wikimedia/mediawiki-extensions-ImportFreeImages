<?php

class SpecialImportFreeImages extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'ImportFreeImages'/*class*/, 'upload'/*restriction*/ );
	}

	/** @inheritDoc */
	public function doesWrites() {
		return true;
	}

	/**
	 * Group this special page under the correct header on Special:SpecialPages.
	 *
	 * @return string
	 */
	protected function getGroupName() {
		return 'media';
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par Parameter passed to the page
	 */
	public function execute( $par ) {
		global $wgEnableUploads;

		$this->setHeaders();
		$this->outputHeader();

		$out = $this->getOutput();
		$user = $this->getUser();

		# a lot of this code is duplicated from SpecialUpload, should be refactored
		# Check uploading enabled
		if ( !$wgEnableUploads ) {
			$out->showErrorPage( 'uploaddisabled', 'uploaddisabledtext' );
			return;
		}

		# Check that the user has 'upload' right and is logged in
		if ( !$user->isAllowed( 'upload' ) ) {
			if ( !$user->isRegistered() ) {
				$out->showErrorPage( 'uploadnologin', 'uploadnologintext' );
			} else {
				throw new PermissionsError( 'upload' );
			}
			return;
		}

		# Check blocks
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		# Show a message if the database is in read-only mode
		$this->checkReadOnly();

		# Do all magic
		$this->showForm();
		$this->showResult();
	}

	/**
	 * Show the search form
	 */
	protected function showForm() {
		global $wgScript;

		$this->getOutput()->addHTML(
			Html::rawElement( 'fieldset', [],
				Html::element( 'legend', [], $this->msg( 'importfreeimages' )->text() ) . "\n" .
				$this->msg( 'importfreeimages_description' )->parse() . "\n" .
				Html::rawElement( 'form', [ 'action' => $wgScript ],
					Html::element( 'input', [
						'type' => 'hidden',
						'name' => 'title',
						'value' => $this->getPageTitle()->getPrefixedText(),
					] ) . "\n" .
					Html::element( 'input', [
						'type' => 'text',
						'name' => 'q',
						'size' => '40',
						'value' => $this->getRequest()->getText( 'q' ),
					] ) . "\n" .
					Html::element( 'input', [
						'type' => 'submit',
						'value' => $this->msg( 'search' )->text()
					] )
				)
		) );
	}

	/**
	 * Show the search result if available
	 */
	protected function showResult() {
		$out = $this->getOutput();
		$request = $this->getRequest();

		$page = $request->getInt( 'p', 1 );
		$q = $request->getVal( 'q' );
		if ( !$q ) {
			return;
		}

		$ifi = new ImportFreeImages();
		// TODO: get the right licenses
		$photos = $ifi->searchPhotos( $q, $page );
		if ( !$photos ) {
			$out->addHTML( $this->msg( 'importfreeimages_nophotosfound', $q )->escaped() );
			return;
		}

		$out->addHTML( '<table cellpadding="4" id="mw-ifi-result">' );

		$specialUploadTitle = SpecialPage::getTitleFor( 'Upload' );

		$ownermsg = $this->msg( 'importfreeimages_owner' )->escaped();
		$importmsg = $this->msg( 'importfreeimages_importthis' )->escaped();
		$i = 0;

		foreach ( $photos['photo'] as $photo ) {
			$owner = $ifi->getOwnerInfo( $photo['owner'] );

			$owner_esc = htmlspecialchars( $photo['owner'], ENT_QUOTES );
			$id_esc = htmlspecialchars( $photo['id'], ENT_QUOTES );
			$title_esc = htmlspecialchars( $photo['title'], ENT_QUOTES );
			$username_esc = htmlspecialchars( $owner['username'], ENT_QUOTES );
			$thumb_esc = htmlspecialchars( "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}_{$ifi->thumbType}.jpg", ENT_QUOTES );
			$link = htmlspecialchars( $specialUploadTitle->getLocalURL( [
				'wpSourceType' => 'IFI',
				'wpFlickrId' => $photo['id']
			] ), ENT_QUOTES );

			if ( $i % $ifi->resultsPerRow == 0 ) {
				$out->addHTML( '<tr>' );
			}

			# TODO: Fix nasty HTML generation
			$out->addHTML( "
					<td align='center' style='padding-top: 15px; border-bottom: 1px solid #ccc;'>
						<font size=-2><a href='http://www.flickr.com/photos/$owner_esc/$id_esc/'>$title_esc</a>
						<br />$ownermsg: <a href='http://www.flickr.com/people/$owner_esc/'>$username_esc</a>
						<br /><img src='$thumb_esc' alt='' />
						<br />(<a href='$link'>$importmsg</a>)</font>
					</td>
			" );

			if ( $i % $ifi->resultsPerRow == ( $ifi->resultsPerRow - 1 ) ) {
				$out->addHTML( '</tr>' );
			}

			$i++;
		}

		$out->addHTML( '</table>' );

		if ( $ifi->resultsPerPage * $page < $photos['total'] ) {
			$page++;
			$out->addHTML( '<br />' . Linker::link(
				$this->getPageTitle(),
				$this->msg( 'importfreeimages_next', $ifi->resultsPerPage )->escaped(),
				[],
				[ 'p' => $page, 'q' => $q ]
			) );
		}
	}

} // class
