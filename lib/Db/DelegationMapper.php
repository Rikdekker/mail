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

		$select = $qb->select('account_id')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid))
			);

		$rows = $this->findEntities($select);

		return $rows;
	}

    public function findDelegatedToUsers(string $accountId) : array {
        $qb = $this->db->getQueryBuilder();

		$select = $qb->select('user_id')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('account_id', $qb->createNamedParameter($accountId))
			);

		$rows = $this->findEntities($select);
		return $rows;
    }

    /**
	 * @throws DoesNotExistException
	 */
	public function find(int $accountId, string $uid): Actions {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('account_id', $qb->createNamedParameter($accountId)));
		return $this->findEntity($qb);
	}
}
