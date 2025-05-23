<?php

/*
 * Copyright 2005 - 2022 Centreon (https://www.centreon.com/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * For more information : contact@centreon.com
 *
 */

declare(strict_types=1);

namespace Core\Security\Infrastructure\ProviderConfiguration\OpenId\Repository;

use Centreon\Domain\Log\LoggerTrait;
use Centreon\Infrastructure\DatabaseConnection;
use Centreon\Infrastructure\Repository\AbstractRepositoryDRB;
use Core\Security\Application\ProviderConfiguration\OpenId\Repository\WriteOpenIdConfigurationRepositoryInterface
    as WriteRepositoryInterface;
use Core\Security\Domain\ProviderConfiguration\OpenId\Model\Configuration;

class DbWriteOpenIdConfigurationRepository extends AbstractRepositoryDRB implements WriteRepositoryInterface
{
    use LoggerTrait;

    /**
     * @param DatabaseConnection $db
     */
    public function __construct(DatabaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * @inheritDoc
     */
    public function updateConfiguration(Configuration $configuration): void
    {
        $this->info('Updating OpenID Configuration in DBMS');
        $statement = $this->db->prepare(
            $this->translateDbName(
                "UPDATE `:db`.`provider_configuration` SET
                `custom_configuration` = :customConfiguration, `is_active` = :isActive, `is_forced` = :isForced
                WHERE `name`='openid'"
            )
        );
        $statement->bindValue(
            ':customConfiguration',
            json_encode($this->buildCustomConfigurationFromOpenIdConfiguration($configuration)),
            \PDO::PARAM_STR
        );
        $statement->bindValue(':isActive', $configuration->isActive() ? '1' : '0', \PDO::PARAM_STR);
        $statement->bindValue(':isForced', $configuration->isForced() ? '1' : '0', \PDO::PARAM_STR);
        $statement->execute();
    }

    /**
     * Format OpenIdConfiguration for custom_configuration.
     *
     * @param Configuration $configuration
     * @return array<string, mixed>
     */
    private function buildCustomConfigurationFromOpenIdConfiguration(Configuration $configuration): array
    {
        return [
            'trusted_client_addresses' => $configuration->getTrustedClientAddresses(),
            'blacklist_client_addresses' => $configuration->getBlacklistClientAddresses(),
            'base_url' => $configuration->getBaseUrl(),
            'authorization_endpoint' => $configuration->getAuthorizationEndpoint(),
            'token_endpoint' => $configuration->getTokenEndpoint(),
            'introspection_token_endpoint' => $configuration->getIntrospectionTokenEndpoint(),
            'userinfo_endpoint' => $configuration->getUserInformationEndpoint(),
            'endsession_endpoint' => $configuration->getEndSessionEndpoint(),
            'connection_scopes' => $configuration->getConnectionScopes(),
            'login_claim' => $configuration->getLoginClaim(),
            'client_id' => $configuration->getClientId(),
            'client_secret' => $configuration->getClientSecret(),
            'authentication_type' => $configuration->getAuthenticationType(),
            'verify_peer' => $configuration->verifyPeer(),
            'auto_import' => $configuration->isAutoImportEnabled(),
            'contact_template_id' => $configuration->getContactTemplate()?->getId(),
            'email_bind_attribute' => $configuration->getEmailBindAttribute(),
            'alias_bind_attribute' => $configuration->getUserAliasBindAttribute(),
            'fullname_bind_attribute' => $configuration->getUserNameBindAttribute(),
            'claim_name' => $configuration->getClaimName(),
            'contact_group_id' => $configuration->getContactGroup()?->getId()
        ];
    }

    /**
     * @inheritDoc
     */
    public function deleteAuthorizationRules(): void
    {
        $statement = $this->db->query("SELECT id FROM provider_configuration WHERE name='openid'");
        if ($statement !== false && ($result = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $providerConfigurationId = (int) $result['id'];
            $deleteStatement = $this->db->prepare(
                "DELETE FROM security_provider_access_group_relation
                    WHERE provider_configuration_id = :providerConfigurationId"
            );
            $deleteStatement->bindValue(':providerConfigurationId', $providerConfigurationId, \PDO::PARAM_INT);
            $deleteStatement->execute();
        }
    }

    /**
     * @inheritDoc
     */
    public function insertAuthorizationRules(array $authorizationRules): void
    {
        $statement = $this->db->query("SELECT id FROM provider_configuration WHERE name='openid'");
        if ($statement !== false && ($result = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $providerConfigurationId = (int) $result['id'];
            $insertStatement = $this->db->prepare(
                "INSERT INTO security_provider_access_group_relation
                    (claim_value, access_group_id, provider_configuration_id)
                    VALUES (:claimValue, :accessGroupId, :providerConfigurationId)"
            );
            foreach ($authorizationRules as $authorizationRule) {
                $insertStatement->bindValue(':claimValue', $authorizationRule->getClaimValue(), \PDO::PARAM_STR);
                $insertStatement->bindValue(
                    ':accessGroupId',
                    $authorizationRule->getAccessGroup()->getId(),
                    \PDO::PARAM_INT
                );
                $insertStatement->bindValue(':providerConfigurationId', $providerConfigurationId, \PDO::PARAM_INT);
                $insertStatement->execute();
            }
        }
    }
}
