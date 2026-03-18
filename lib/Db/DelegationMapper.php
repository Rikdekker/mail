<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Delegation>
 */
class DelegationMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_delegations');
	}

	public function findDelegatedAccountsForUser(string $uid): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid))
			);

		return $this->findEntities($select);
	}

	public function findDelegatedToUsers(int $accountId): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('account_id', $qb->createNamedParameter($accountId))
			);

		return $this->findEntities($select);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function find(int $accountId, string $uid): Delegation {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('account_id', $qb->createNamedParameter($accountId)));
		return $this->findEntity($qb);
	}
}
