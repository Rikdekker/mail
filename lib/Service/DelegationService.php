<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Db\AliasMapper;
use OCA\Mail\Db\Delegation;
use OCA\Mail\Db\DelegationMapper;
use OCA\Mail\Db\LocalMessageMapper;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\DelegationExistsException;
use OCP\AppFramework\Db\DoesNotExistException;

class DelegationService {

	public function __construct(
		private DelegationMapper $delegationMapper,
		private MailAccountMapper $mailAccountMapper,
		private MailboxMapper $mailboxMapper,
		private MessageMapper $messageMapper,
		private AliasMapper $aliasMapper,
		private LocalMessageMapper $localMessageMapper,
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

	/**
	 * Resolve the effective user ID for accessing an account.
	 *
	 * If the current user owns the account, returns the current user ID.
	 * If the current user is a delegate, returns the account owner's user ID.
	 * Throws DoesNotExistException if the user has no access.
	 *
	 * @throws DoesNotExistException
	 */
	public function resolveAccountUserId(int $accountId, string $currentUserId): string {
		// Check if the current user owns the account
		try {
			$account = $this->mailAccountMapper->find($currentUserId, $accountId);
			return $account->getUserId();
		} catch (DoesNotExistException) {
			// Not the owner — check delegation
		}

		// Check if the current user is a delegate
		return $this->delegationMapper->findAccountOwnerForDelegatedUser($accountId, $currentUserId);
	}

	/**
	 * Resolve the effective user ID for accessing a mailbox.
	 *
	 * Looks up the account ID for the given mailbox, then delegates to
	 * resolveAccountUserId to check ownership or delegation.
	 *
	 * @throws DoesNotExistException
	 */
	public function resolveMailboxUserId(int $mailboxId, string $currentUserId): string {
		$accountId = $this->mailboxMapper->findAccountIdForMailbox($mailboxId);
		return $this->resolveAccountUserId($accountId, $currentUserId);
	}

	/**
	 * Resolve the effective user ID for accessing a message.
	 *
	 * Looks up the account ID for the given message (via its mailbox), then
	 * delegates to resolveAccountUserId to check ownership or delegation.
	 *
	 * @throws DoesNotExistException
	 */
	public function resolveMessageUserId(int $messageId, string $currentUserId): string {
		$accountId = $this->messageMapper->findAccountIdForMessage($messageId);
		return $this->resolveAccountUserId($accountId, $currentUserId);
	}

	/**
	 * Resolve the effective user ID for accessing an alias.
	 *
	 * Looks up the account ID for the given alias, then delegates to
	 * resolveAccountUserId to check ownership or delegation.
	 *
	 * @throws DoesNotExistException
	 */
	public function resolveAliasUserId(int $aliasId, string $currentUserId): string {
		$accountId = $this->aliasMapper->findAccountIdForAlias($aliasId);
		return $this->resolveAccountUserId($accountId, $currentUserId);
	}

	/**
	 * Resolve the effective user ID for accessing a local message (draft/outbox).
	 *
	 * Looks up the account ID for the given local message, then delegates to
	 * resolveAccountUserId to check ownership or delegation.
	 *
	 * @throws DoesNotExistException
	 */
	public function resolveLocalMessageUserId(int $localMessageId, string $currentUserId): string {
		$accountId = $this->localMessageMapper->findAccountIdForLocalMessage($localMessageId);
		return $this->resolveAccountUserId($accountId, $currentUserId);
	}
}
