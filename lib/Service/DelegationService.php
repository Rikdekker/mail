<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service;

use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use OCA\Mail\Db\Delegation;
use  OCA\Mail\Db\DelegationMapper;

class DelegationService {


    public function __construct(
        private DelegationMapper $delegationMapper,
        private LoggerInterface $logger
    ){}

    public function delegate(int $accountId, string $userId){
        $delegation = new Delegation(); 
        $delegation->setAccountId($accountId); 
        $delegation->setUserId($userId);
        $this->delegationMapper->insert($delegation);
    }

    public function findDelgatedAccountForUser(string $userId): array {
        return $this->delegationMapper->findDelegatedAccountsForUser($userId);
    }

    public function findDelegatedToUsersForAccount(string $accountId): array {
        return $this->delegationMapper->findDelegatedToUsers($accountId);
    }

    public function unDelegate(int $accountId, string $userId) {
        try {
            $delegation = $this->delegationMapper->find($accountId,$userId);

        } catch (DoesNotExistException $e) {
            $this->logger->warning("Delegation of account:$accountId not found for user: $userId", ['exception' => $e]);
        }
        if( !$delegation ){
            return; 
        }
        $this->delegationMapper->delete($delegation);
        
    }

}