<?php

use MediaWiki\MediaWikiServices;

class SmiteSpamUtils {

	/**
	 * Provides a database for read access.
	 *
	 * @return IDatabase
	 */
	public static function getReadDB() {
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		if ( method_exists( $lbFactory, 'getReplicaDatabase' ) ) {
			// MW 1.40+
			return $lbFactory->getReplicaDatabase();
		} else {
			return $lbFactory->getMainLB()->getConnection( DB_REPLICA );
		}
	}

	/**
	 * Provides a database for write access.
	 *
	 * @return IDatabase
	 */
	public static function getWriteDB() {
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		if ( method_exists( $lbFactory, 'getPrimaryDatabase' ) ) {
			// MW 1.40+
			return $lbFactory->getPrimaryDatabase();
		} else {
			return $lbFactory->getMainLB()->getConnection( DB_PRIMARY );
		}
	}

}
