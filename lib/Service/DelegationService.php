<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Db\Delegation;
use OCA\Mail\Db\DelegationMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

class DelegationService {

	public function __construct(
		private DelegationMapper $delegationMapper,
		private LoggerInterface $logger,
	) {
	}

	public function delegate(int $accountId, string $userId): void {
		$delegation = new Delegation();
		$delegation->setAccountId($accountId);
		$delegation->setUserId($userId);
		$this->delegationMapper->insert($delegation);
	}

	public function findDelegatedAccountForUser(string $userId): array {
		return $this->delegationMapper->findDelegatedAccountsForUser($userId);
	}

	public function findDelegatedToUsersForAccount(string $accountId): array {
		return $this->delegationMapper->findDelegatedToUsers($accountId);
	}

	public function unDelegate(int $accountId, string $userId): void {
		try {
			$delegation = $this->delegationMapper->find($accountId, $userId);
		} catch (DoesNotExistException $e) {
			$this->logger->warning("Delegation of account:$accountId not found for user: $userId", ['exception' => $e]);
			return;
		}
		$this->delegationMapper->delete($delegation);
	}
}
