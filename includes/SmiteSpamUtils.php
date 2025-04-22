<?php

use MediaWiki\MediaWikiServices;

class SmiteSpamUtils {

	/**
	 * Provides a database for read access.
	 *
	 * @return \Wikimedia\Rdbms\IReadableDatabase
	 */
	public static function getReadDB() {
		return MediaWikiServices::getInstance()->getDBLoadBalancerFactory()
			->getReplicaDatabase();
	}

	/**
	 * Provides a database for write access.
	 *
	 * @return \Wikimedia\Rdbms\IDatabase
	 */
	public static function getWriteDB() {
		return MediaWikiServices::getInstance()->getDBLoadBalancerFactory()
			->getPrimaryDatabase();
	}

}
