<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Db\Delegation;
use OCA\Mail\Db\DelegationMapper;
use OCA\Mail\Exception\DelegationExistsException;
use OCP\AppFramework\Db\DoesNotExistException;

class DelegationService {

	public function __construct(
		private DelegationMapper $delegationMapper,
	) {
	}

	public function delegate(int $accountId, string $userId): Delegation {
		try {
			$this->delegationMapper->find($accountId, $userId);
			throw new DelegationExistsException("Delegation already exists for account $accountId and user $userId");
		} catch (DoesNotExistException) {
			// continue
		}

		$delegation = new Delegation();
		$delegation->setAccountId($accountId);
		$delegation->setUserId($userId);
		return $this->delegationMapper->insert($delegation);
	}

	public function findDelegatedAccountForUser(string $userId): array {
		return $this->delegationMapper->findDelegatedAccountsForUser($userId);
	}

	public function findDelegatedToUsersForAccount(int $accountId): array {
		return $this->delegationMapper->findDelegatedToUsers($accountId);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function unDelegate(int $accountId, string $userId): void {
		$delegation = $this->delegationMapper->find($accountId, $userId);
		$this->delegationMapper->delete($delegation);
	}
}
