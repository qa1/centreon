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

namespace Core\Infrastructure\RealTime\Api\Hypermedia;

use Centreon\Domain\Contact\Contact;
use Centreon\Domain\Contact\Interfaces\ContactInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Core\Application\RealTime\UseCase\FindHost\FindHostResponse;
use Core\Infrastructure\Common\Api\HttpUrlTrait;

class HostHypermediaProvider implements HypermediaProviderInterface
{
    use HttpUrlTrait;

    public const URI_CONFIGURATION = '/main.php?p=60101&o=c&host_id={hostId}',
                 URI_EVENT_LOGS = '/main.php?p=20301&h={hostId}',
                 URI_REPORTING = '/main.php?p=307&host={hostId}',
                 URI_HOSTGROUP_CONFIGURATION = '/main.php?p=60102&o=c&hg_id={hostgroupId}',
                 ENDPOINT_HOST_TIMELINE = 'centreon_application_monitoring_gettimelinebyhost',
                 ENDPOINT_HOST_NOTIFICATION_POLICY = 'configuration.host.notification-policy';

    /**
     * @param ContactInterface $contact
     * @param UrlGeneratorInterface $router
     */
    public function __construct(
        private ContactInterface $contact,
        protected UrlGeneratorInterface $router
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isValidFor(mixed $data): bool
    {
        return ($data instanceof FindHostResponse);
    }

    /**
     * @inheritDoc
     */
    public function createEndpoints(mixed $response): array
    {
        return [
            'timeline' => $this->createForTimelineEndpoint(['hostId' => $response->id]),
            'notification_policy' => $this->createNotificationPolicyEndpoint(['hostId' => $response->id]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function createInternalUris(mixed $response): array
    {
        $parameters = ['hostId' => $response->id];
        return [
            'configuration' => $this->createForConfiguration($parameters),
            'logs' => $this->createForEventLog($parameters),
            'reporting' => $this->createForReporting($parameters),
        ];
    }

    /**
     * Create configuration redirection uri
     *
     * @param array<string, int> $parameters
     * @return string|null
     */
    public function createForConfiguration(array $parameters): ?string
    {
        return (
            $this->contact->hasTopologyRole(Contact::ROLE_CONFIGURATION_HOSTS_WRITE)
            || $this->contact->hasTopologyRole(Contact::ROLE_CONFIGURATION_HOSTS_READ)
            || $this->contact->isAdmin()
        )
        ? $this->getBaseUri() . str_replace('{hostId}', (string) $parameters['hostId'], self::URI_CONFIGURATION)
        : null;
    }

    /**
     * Create reporting redirection uri
     *
     * @param array<string, int> $parameters
     * @return string|null
     */
    public function createForReporting(array $parameters): ?string
    {
        return (
            $this->contact->hasTopologyRole(Contact::ROLE_REPORTING_DASHBOARD_HOSTS)
            || $this->contact->isAdmin()
        )
        ? $this->getBaseUri() . str_replace('{hostId}', (string) $parameters['hostId'], self::URI_REPORTING)
        : null;
    }

    /**
     * Create event logs redirection uri
     *
     * @param array<string, int> $parameters
     * @return string|null
     */
    public function createForEventLog(array $parameters): ?string
    {
        return (
            $this->contact->hasTopologyRole(Contact::ROLE_MONITORING_EVENT_LOGS)
            || $this->contact->isAdmin()
        )
        ? $this->getBaseUri() . str_replace('{hostId}', (string) $parameters['hostId'], self::URI_EVENT_LOGS)
        : null;
    }

    /**
     * Create hostgroup configuration redirection uri
     *
     * @param array<string, int> $parameters
     * @return string|null
     */
    public function createForGroup(array $parameters): ?string
    {
        return (
            $this->contact->hasTopologyRole(Contact::ROLE_CONFIGURATION_HOSTS_HOST_GROUPS_READ_WRITE)
            || $this->contact->hasTopologyRole(Contact::ROLE_CONFIGURATION_HOSTS_HOST_GROUPS_READ)
            || $this->contact->isAdmin()
        )
        ? $this->getBaseUri() . str_replace(
            '{hostgroupId}',
            (string) $parameters['hostgroupId'],
            self::URI_HOSTGROUP_CONFIGURATION
        )
        : null;
    }

    /**
     * Create Timeline endpoint URI for the Host Resource
     *
     * @param array<string, int> $parameters
     * @return string
     */
    public function createForTimelineEndpoint(array $parameters): string
    {
        return $this->router->generate(self::ENDPOINT_HOST_TIMELINE, $parameters);
    }

    /**
     * Create Notification policy endpoint URI for the Host Resource
     *
     * @param array<string, int> $parameters
     * @return string
     */
    public function createNotificationPolicyEndpoint(array $parameters): string
    {
        return $this->router->generate(self::ENDPOINT_HOST_NOTIFICATION_POLICY, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function createInternalGroupsUri(mixed $response): array
    {
        return array_map(
            fn (array $group) => [
                'id' => $group['id'],
                'name' => $group['name'],
                'configuration_uri' => $this->createForGroup(['hostgroupId' => $group['id']])
            ],
            $response->hostgroups
        );
    }
}
