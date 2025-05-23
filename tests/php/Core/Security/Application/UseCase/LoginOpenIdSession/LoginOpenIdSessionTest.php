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

namespace Tests\Core\Security\Application\UseCase\LoginOpenIdSession;

use CentreonDB;
use Pimple\Container;
use Core\Contact\Domain\Model\ContactGroup;
use Symfony\Component\HttpFoundation\Request;
use Core\Contact\Domain\Model\ContactTemplate;
use Symfony\Component\HttpFoundation\RequestStack;
use Core\Infrastructure\Common\Presenter\JsonPresenter;
use Centreon\Domain\Contact\Interfaces\ContactInterface;
use Security\Domain\Authentication\Model\AuthenticationTokens;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Centreon\Domain\Repository\Interfaces\DataStorageEngineInterface;
use Security\Domain\Authentication\Interfaces\OpenIdProviderInterface;
use Security\Domain\Authentication\Interfaces\SessionRepositoryInterface;
use Core\Security\Application\UseCase\LoginOpenIdSession\LoginOpenIdSession;
use Security\Domain\Authentication\Interfaces\AuthenticationServiceInterface;
use Core\Security\Domain\ProviderConfiguration\OpenId\Model\Configuration;
use Security\Domain\Authentication\Interfaces\AuthenticationRepositoryInterface;
use Core\Security\Application\UseCase\LoginOpenIdSession\LoginOpenIdSessionRequest;
use Core\Security\Infrastructure\Api\LoginOpenIdSession\LoginOpenIdSessionPresenter;
use Core\Security\Application\ProviderConfiguration\OpenId\Repository\ReadOpenIdConfigurationRepositoryInterface;

beforeEach(function () {
    $this->repository = $this->createMock(ReadOpenIdConfigurationRepositoryInterface::class);
    $this->provider = $this->createMock(OpenIdProviderInterface::class);
    $this->session = $this->createMock(SessionInterface::class);
    $this->session
        ->expects($this->any())
        ->method('getId')
        ->willReturn('session_abcd');
    $this->request = $this->createMock(Request::class);
    $this->request
        ->expects($this->any())
        ->method('getSession')
        ->willReturn($this->session);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->requestStack
        ->expects($this->any())
        ->method('getCurrentRequest')
        ->willReturn($this->request);
    $this->centreonDB = $this->createMock(CentreonDB::class);
    $this->dependencyInjector = new Container(['configuration_db' => $this->centreonDB]);
    $this->authenticationService = $this->createMock(AuthenticationServiceInterface::class);
    $this->authenticationRepository = $this->createMock(AuthenticationRepositoryInterface::class);
    $this->sessionRepository = $this->createMock(SessionRepositoryInterface::class);
    $this->dataStorageEngine = $this->createMock(DataStorageEngineInterface::class);
    $this->formatter = $this->createMock(JsonPresenter::class);
    $this->presenter = new LoginOpenIdSessionPresenter($this->formatter);
    $this->contact = $this->createMock(ContactInterface::class);
    $this->authenticationTokens = $this->createMock(AuthenticationTokens::class);

    $this->validOpenIdConfiguration = (new Configuration())
        ->setActive(true)
        ->setForced(true)
        ->setVerifyPeer(false)
        ->setTrustedClientAddresses([])
        ->setBlacklistClientAddresses([])
        ->setBaseUrl('http://127.0.0.1/auth/openid-connect')
        ->setAuthorizationEndpoint('/authorization')
        ->setTokenEndpoint('/token')
        ->setIntrospectionTokenEndpoint('/introspect')
        ->setUserInformationEndpoint('/userinfo')
        ->setEndSessionEndpoint('/logout')
        ->setConnectionScopes([])
        ->setLoginClaim('preferred_username')
        ->setClientId('MyCl1ientId')
        ->setClientSecret('MyCl1ientSuperSecr3tKey')
        ->setAuthenticationType('client_secret_post')
        ->setContactTemplate(new ContactTemplate(1, 'contact_template'))
        ->setAutoImportEnabled(false)
        ->setContactGroup(new ContactGroup(1, 'contact_group'));
});

it('expects to return an error message in presenter when no provider configuration are found', function () {
    $request = new LoginOpenIdSessionRequest();
    $request->authorizationCode = 'abcde-fghij-klmno';
    $request->clientIp = '127.0.0.1';

    $this->repository
        ->expects($this->once())
        ->method('findConfiguration')
        ->willReturn(null);

    $useCase = new LoginOpenIdSession(
        '/monitoring/ressources',
        $this->repository,
        $this->provider,
        $this->requestStack,
        $this->dependencyInjector,
        $this->authenticationService,
        $this->authenticationRepository,
        $this->sessionRepository,
        $this->dataStorageEngine
    );

    $useCase($request, $this->presenter);
    expect($this->presenter->getPresentedData()->error)->toBe('Provider not found');
});

it('expects to execute authenticateOrFail method from OpenIdProvider', function () {
    $request = new LoginOpenIdSessionRequest();
    $request->authorizationCode = 'abcde-fghij-klmno';
    $request->clientIp = '127.0.0.1';

    $this->repository
        ->expects($this->once())
        ->method('findConfiguration')
        ->willReturn($this->validOpenIdConfiguration);

    $this->provider
        ->expects($this->once())
        ->method('authenticateOrFail');

    $useCase = new LoginOpenIdSession(
        '/monitoring/ressources',
        $this->repository,
        $this->provider,
        $this->requestStack,
        $this->dependencyInjector,
        $this->authenticationService,
        $this->authenticationRepository,
        $this->sessionRepository,
        $this->dataStorageEngine
    );
    $useCase($request, $this->presenter);
});

it(
    'expects to return an error message in presenter when the provider can\'t find the user and can\'t create it',
    function () {
        $request = new LoginOpenIdSessionRequest();
        $request->authorizationCode = 'abcde-fghij-klmno';
        $request->clientIp = '127.0.0.1';

        $this->repository
            ->expects($this->once())
            ->method('findConfiguration')
            ->willReturn($this->validOpenIdConfiguration);

        $this->provider
            ->expects($this->once())
            ->method('canCreateUser')
            ->willReturn(false);

        $useCase = new LoginOpenIdSession(
            '/monitoring/ressources',
            $this->repository,
            $this->provider,
            $this->requestStack,
            $this->dependencyInjector,
            $this->authenticationService,
            $this->authenticationRepository,
            $this->sessionRepository,
            $this->dataStorageEngine
        );
        $useCase($request, $this->presenter);
        expect($this->presenter->getPresentedData()->error)->toBe('User not found');
    }
);

it(
    'expects to return an error message in presenter when the provider ' .
    'wasn\'t be able to return a user after creating it',
    function () {
        $request = new LoginOpenIdSessionRequest();
        $request->authorizationCode = 'abcde-fghij-klmno';
        $request->clientIp = '127.0.0.1';

        $this->repository
            ->expects($this->once())
            ->method('findConfiguration')
            ->willReturn($this->validOpenIdConfiguration);

        $this->provider
            ->expects($this->any())
            ->method('getUser')
            ->willReturn(null);

        $this->provider
            ->expects($this->once())
            ->method('canCreateUser')
            ->willReturn(true);

        $useCase = new LoginOpenIdSession(
            '/monitoring/ressources',
            $this->repository,
            $this->provider,
            $this->requestStack,
            $this->dependencyInjector,
            $this->authenticationService,
            $this->authenticationRepository,
            $this->sessionRepository,
            $this->dataStorageEngine
        );

        $useCase($request, $this->presenter);
        expect($this->presenter->getPresentedData()->error)->toBe('User not found');
    }
);
