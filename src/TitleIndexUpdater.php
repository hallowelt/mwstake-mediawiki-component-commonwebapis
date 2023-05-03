<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs;

use MediaWiki\Hook\AfterImportPageHook;
use MediaWiki\Hook\PageMoveCompleteHook;
use MediaWiki\Page\Hook\ArticleDeleteCompleteHook;
use MediaWiki\Page\Hook\ArticleUndeleteHook;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use Title;
use Wikimedia\Rdbms\ILoadBalancer;

class TitleIndexUpdater implements
	PageSaveCompleteHook,
	PageMoveCompleteHook,
	ArticleDeleteCompleteHook,
	ArticleUndeleteHook,
	AfterImportPageHook
{

	/**
	 * @var ILoadBalancer
	 */
	private $lb = null;

	/**
	 * @param ILoadBalancer $lb
	 */
	public function __construct( ILoadBalancer $lb ) {
		$this->lb = $lb;
	}

	/**
	 * @inheritDoc
	 */
	public function onPageSaveComplete(
		$wikiPage, $user, $summary, $flags, $revisionRecord, $editResult
	) {
		if ( !( $flags & EDIT_NEW ) ) {
			return;
		}
		$this->insert( $wikiPage->getTitle() );
	}

	/**
	 * @inheritDoc
	 */
	public function onPageMoveComplete( $old, $new, $user, $pageid, $redirid, $reason, $revision ) {
		$this->delete( $old->getNamespace(), $old->getDBkey() );
		$this->insert( $new );
	}

	/**
	 * @inheritDoc
	 */
	public function onArticleDeleteComplete(
		$wikiPage, $user, $reason, $id, $content, $logEntry, $archivedRevisionCount
	) {
		$this->delete( $wikiPage->getTitle()->getNamespace(), $wikiPage->getTitle()->getDBkey() );
	}

	/**
	 * @inheritDoc
	 */
	public function onArticleUndelete( $title, $create, $comment, $oldPageId, $restoredPages ) {
		$this->insert( $title, $oldPageId );
	}

	/**
	 * @inheritDoc
	 */
	public function onAfterImportPage( $title, $foreignTitle, $revCount, $sRevCount, $pageInfo ) {
		$this->insert( $title );
	}

	/**
	 * @param Title $page
	 *
	 * @return bool|void
	 */
	private function insert( Title $page, $forceId = null ) {
		$db = $this->lb->getConnection( DB_PRIMARY );
		if ( !$page->exists() ) {
			return;
		}
		return $db->insert(
			'mws_title_index',
			[
				'mti_page_id' => $forceId ?? $page->getId(),
				'mti_namespace' => $page->getNamespace(),
				'mti_title' => mb_strtolower( str_replace( '_', ' ', $page->getDBkey() ) ),
			],
			__METHOD__,
			[ 'IGNORE' ]
		);
	}

	/**
	 * @param int $namespace
	 * @param string $title
	 *
	 * @return bool
	 */
	private function delete( int $namespace, string $title ) {
		$db = $this->lb->getConnection( DB_PRIMARY );
		return $db->delete(
			'mws_title_index',
			[
				'mti_namespace' => $namespace,
				'mti_title' => mb_strtolower( str_replace( '_', ' ', $title ) ),
			],
			__METHOD__
		);
	}
}
