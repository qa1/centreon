<?php

/*
 * Copyright 2005 - 2021 Centreon (https://www.centreon.com/)
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

namespace Core\Security\Application\ProviderConfiguration\Local\UseCase\UpdateConfiguration;

use Centreon\Domain\Common\Assertion\AssertionException;
use Centreon\Domain\Log\LoggerTrait;
use Core\Application\Common\UseCase\ErrorResponse;
use Core\Application\Common\UseCase\NoContentResponse;
use Core\Security\Domain\ProviderConfiguration\Local\Model\ConfigurationFactory;
use Core\Security\Application\ProviderConfiguration\Local\Repository\WriteConfigurationRepositoryInterface;
use Core\Application\Configuration\User\Repository\ReadUserRepositoryInterface;
use Core\Security\Application\ProviderConfiguration\Local\UseCase\UpdateConfiguration\UpdateConfigurationRequest;

class UpdateConfiguration
{
    use LoggerTrait;

    /**
     * @param WriteConfigurationRepositoryInterface $writeConfigurationRepository
     * @param ReadUserRepositoryInterface $readUserRepository
     */
    public function __construct(
        private WriteConfigurationRepositoryInterface $writeConfigurationRepository,
        private ReadUserRepositoryInterface $readUserRepository,
    ) {
    }

    /**
     * @param UpdateConfigurationPresenterInterface $presenter
     * @param UpdateConfigurationRequest $request
     */
    public function __invoke(
        UpdateConfigurationPresenterInterface $presenter,
        UpdateConfigurationRequest $request
    ): void {
        $this->info('Updating Security Policy');

        try {
            $configuration = ConfigurationFactory::createFromRequest($request);
        } catch (AssertionException $ex) {
            $this->error('Unable to create Security Policy because one or many parameters are invalid');
            $presenter->setResponseStatus(new ErrorResponse($ex->getMessage()));
            return;
        }

        $excludedUserIds = $this->readUserRepository->findUserIdsByAliases(
            $request->passwordExpirationExcludedUserAliases
        );

        $this->writeConfigurationRepository->updateConfiguration($configuration, $excludedUserIds);

        $presenter->setResponseStatus(new NoContentResponse());
    }
}
