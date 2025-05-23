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

namespace Tests\Core\Security\Application\ProviderConfiguration\OpenId\UseCase\UpdateOpenIdConfiguration;

use Centreon\Domain\Common\Assertion\AssertionException;
use Centreon\Domain\Repository\Interfaces\DataStorageEngineInterface;
use Core\Application\Common\UseCase\NoContentResponse;
use Core\Application\Common\UseCase\ErrorResponse;
use Core\Contact\Application\Repository\ReadContactGroupRepositoryInterface;
use Core\Security\Application\ProviderConfiguration\OpenId\Repository\WriteOpenIdConfigurationRepositoryInterface;
use Core\Security\Application\ProviderConfiguration\OpenId\UseCase\UpdateOpenIdConfiguration\{
    UpdateOpenIdConfiguration,
    UpdateOpenIdConfigurationPresenterInterface,
    UpdateOpenIdConfigurationRequest
};
use Core\Contact\Application\Repository\ReadContactTemplateRepositoryInterface;
use Core\Contact\Domain\Model\ContactGroup;
use Core\Security\Application\ProviderConfiguration\OpenId\Builder\ConfigurationBuilder;
use Core\Security\Application\Repository\ReadAccessGroupRepositoryInterface;
use Core\Security\Domain\ProviderConfiguration\OpenId\Exceptions\OpenIdConfigurationException;
use Core\Security\Domain\ProviderConfiguration\OpenId\Model\OpenIdConfigurationFactory;

beforeEach(function () {
    $this->repository = $this->createMock(WriteOpenIdConfigurationRepositoryInterface::class);
    $this->contactGroupRepository = $this->createMock(ReadContactGroupRepositoryInterface::class);
    $this->accessGroupRepository = $this->createMock(ReadAccessGroupRepositoryInterface::class);
    $this->presenter = $this->createMock(UpdateOpenIdConfigurationPresenterInterface::class);
    $this->contactTemplateRepository = $this->createMock(ReadContactTemplateRepositoryInterface::class);
    $this->dataStorageEngine = $this->createMock(DataStorageEngineInterface::class);
});

it('should present a NoContentResponse when the use case is executed correctly', function () {
    $request = new UpdateOpenIdConfigurationRequest();
    $request->isActive = true;
    $request->isForced = true;
    $request->trustedClientAddresses = [];
    $request->blacklistClientAddresses = [];
    $request->baseUrl = 'http://127.0.0.1/auth/openid-connect';
    $request->authorizationEndpoint = '/authorization';
    $request->tokenEndpoint = '/token';
    $request->introspectionTokenEndpoint = '/introspect';
    $request->userInformationEndpoint = '/userinfo';
    $request->endSessionEndpoint = '/logout';
    $request->connectionScopes = [];
    $request->loginClaim = 'preferred_username';
    $request->clientId = 'MyCl1ientId';
    $request->clientSecret = 'MyCl1ientSuperSecr3tKey';
    $request->authenticationType = 'client_secret_post';
    $request->verifyPeer = false;
    $request->isAutoImportEnabled = false;
    $request->contactGroupId = 1;
    $request->claimName = 'groups';

    $contactGroup = new ContactGroup(1, 'contact_group');

    $openIdConfiguration = ConfigurationBuilder::create($request, null, $contactGroup, []);

    $this->repository
        ->expects($this->once())
        ->method('updateConfiguration')
        ->with($openIdConfiguration);

    $this->contactGroupRepository
        ->expects($this->once())
        ->method('find')
        ->with(1)
        ->willReturn($contactGroup);

    $this->presenter
        ->expects($this->once())
        ->method('setResponseStatus')
        ->with(new NoContentResponse());

    $useCase = new UpdateOpenIdConfiguration(
        $this->repository,
        $this->contactTemplateRepository,
        $this->contactGroupRepository,
        $this->accessGroupRepository,
        $this->dataStorageEngine
    );
    $useCase($this->presenter, $request);
});

it('should present an ErrorResponse when an error occured during the use case execution', function () {
    $request = new UpdateOpenIdConfigurationRequest();
    $request->isActive = true;
    $request->isForced = true;
    $request->trustedClientAddresses = ["abcd_.@"];
    $request->blacklistClientAddresses = [];
    $request->baseUrl = 'http://127.0.0.1/auth/openid-connect';
    $request->authorizationEndpoint = '/authorization';
    $request->tokenEndpoint = '/token';
    $request->introspectionTokenEndpoint = '/introspect';
    $request->userInformationEndpoint = '/userinfo';
    $request->endSessionEndpoint = '/logout';
    $request->connectionScopes = [];
    $request->loginClaim = 'preferred_username';
    $request->clientId = 'MyCl1ientId';
    $request->clientSecret = 'MyCl1ientSuperSecr3tKey';
    $request->authenticationType = 'client_secret_post';
    $request->verifyPeer = false;
    $request->isAutoImportEnabled = false;
    $request->contactGroupId = 1;
    $request->claimName = 'groups';

    $contactGroup = new ContactGroup(1, 'contact_group');

    $this->contactGroupRepository
        ->expects($this->once())
        ->method('find')
        ->with(1)
        ->willReturn($contactGroup);

    $this->presenter
        ->expects($this->once())
        ->method('setResponseStatus')
        ->with(new ErrorResponse(
            AssertionException::ipOrDomain('abcd_.@', 'OpenIdConfiguration::trustedClientAddresses')->getMessage()
        ));

    $useCase = new UpdateOpenIdConfiguration(
        $this->repository,
        $this->contactTemplateRepository,
        $this->contactGroupRepository,
        $this->accessGroupRepository,
        $this->dataStorageEngine
    );

    $useCase($this->presenter, $request);
});

it('should present an Error Response when auto import is enable and mandatory parameters are missing', function () {
    $request = new UpdateOpenIdConfigurationRequest();
    $request->isActive = true;
    $request->isForced = true;
    $request->trustedClientAddresses = [];
    $request->blacklistClientAddresses = [];
    $request->baseUrl = 'http://127.0.0.1/auth/openid-connect';
    $request->authorizationEndpoint = '/authorization';
    $request->tokenEndpoint = '/token';
    $request->introspectionTokenEndpoint = '/introspect';
    $request->userInformationEndpoint = '/userinfo';
    $request->endSessionEndpoint = '/logout';
    $request->connectionScopes = [];
    $request->loginClaim = 'preferred_username';
    $request->clientId = 'MyCl1ientId';
    $request->clientSecret = 'MyCl1ientSuperSecr3tKey';
    $request->authenticationType = 'client_secret_post';
    $request->verifyPeer = false;
    $request->isAutoImportEnabled = true;
    $request->contactGroupId = 1;
    $request->claimName = 'groups';

    $missingParameters = [
        'contact_template',
        'email_bind_attribute',
        'alias_bind_attribute',
        'fullname_bind_attribute',
    ];

    $contactGroup = new ContactGroup(1, 'contact_group');

    $this->contactGroupRepository
        ->expects($this->once())
        ->method('find')
        ->with(1)
        ->willReturn($contactGroup);

    $this->presenter
        ->expects($this->once())
        ->method('setResponseStatus')
        ->with(new ErrorResponse(
            OpenIdConfigurationException::missingAutoImportMandatoryParameters($missingParameters)->getMessage()
        ));

    $useCase = new UpdateOpenIdConfiguration(
        $this->repository,
        $this->contactTemplateRepository,
        $this->contactGroupRepository,
        $this->accessGroupRepository,
        $this->dataStorageEngine
    );

    $useCase($this->presenter, $request);
});

it('should present an Error Response when auto import is enable and the contact template doesn\'t exist', function () {
    $request = new UpdateOpenIdConfigurationRequest();
    $request->isActive = true;
    $request->isForced = true;
    $request->trustedClientAddresses = [];
    $request->blacklistClientAddresses = [];
    $request->baseUrl = 'http://127.0.0.1/auth/openid-connect';
    $request->authorizationEndpoint = '/authorization';
    $request->tokenEndpoint = '/token';
    $request->introspectionTokenEndpoint = '/introspect';
    $request->userInformationEndpoint = '/userinfo';
    $request->endSessionEndpoint = '/logout';
    $request->connectionScopes = [];
    $request->loginClaim = 'preferred_username';
    $request->clientId = 'MyCl1ientId';
    $request->clientSecret = 'MyCl1ientSuperSecr3tKey';
    $request->authenticationType = 'client_secret_post';
    $request->verifyPeer = false;
    $request->isAutoImportEnabled = true;
    $request->contactTemplate = ['id' => 1, "name" => 'contact_template'];
    $request->emailBindAttribute = 'email';
    $request->userAliasBindAttribute = 'alias';
    $request->userNameBindAttribute = 'name';
    $request->contactGroupId = 1;
    $request->claimName = 'groups';

    $this->contactTemplateRepository
        ->expects($this->once())
        ->method('find')
        ->with($request->contactTemplate['id'])
        ->willReturn(null);

    $this->presenter
        ->expects($this->once())
        ->method('setResponseStatus')
        ->with(new ErrorResponse(
            OpenIdConfigurationException::contactTemplateNotFound($request->contactTemplate['name'])->getMessage()
        ));

    $useCase = new UpdateOpenIdConfiguration(
        $this->repository,
        $this->contactTemplateRepository,
        $this->contactGroupRepository,
        $this->accessGroupRepository,
        $this->dataStorageEngine
    );

    $useCase($this->presenter, $request);
});
