<?php
/*
 Copyright (C) AC SOFTWARE SP. Z O.O.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace SuplaBundle\Tests\Integration\Auth;

use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use PHPUnit_Framework_ExpectationFailedException;
use SuplaBundle\Model\TargetSuplaCloud;
use SuplaBundle\Supla\SuplaAutodiscover;
use SuplaBundle\Supla\SuplaAutodiscoverMock;
use SuplaBundle\Tests\Integration\IntegrationTestCase;
use SuplaBundle\Tests\Integration\TestClient;
use SuplaBundle\Tests\Integration\Traits\ResponseAssertions;
use Symfony\Component\HttpFoundation\Response;

class OAuthBrokerAuthorizationIntegrationTest extends IntegrationTestCase {
    use ResponseAssertions;

    /** @var SuplaAutodiscoverMock */
    private $autodiscover;

    /** @var ClientManagerInterface */
    private $clientManager;

    /** @before */
    public function initAutodiscover() {
        $this->autodiscover = $this->container->get(SuplaAutodiscover::class);
        $this->clientManager = $this->container->get(ClientManagerInterface::class);
        SuplaAutodiscoverMock::clear();
    }

    public function testDisplaysNormalLoginFormIfLocalClientExists() {
        $localClient = $this->clientManager->createClient();
        $localClient->setName('Local App');
        $this->clientManager->updateClient($localClient);
        $client = $this->createHttpsClient();
        $crawler = $client->request('GET', $this->oauthAuthorizeUrl($localClient->getPublicId()));
        $routerView = $crawler->filter('router-view')->getNode(0);
        $askForTargetCloud = $routerView->getAttribute(':ask-for-target-cloud');
        $this->assertEquals('false', $askForTargetCloud);
    }

    public function testDisplaysBrokerLoginFormIfLocalClientDoesNotExist() {
        $client = $this->createHttpsClient();
        $crawler = $client->request('GET', $this->oauthAuthorizeUrl('1_ABC'));
        $routerView = $crawler->filter('router-view')->getNode(0);
        $askForTargetCloud = $routerView->getAttribute(':ask-for-target-cloud');
        $this->assertEquals('true', $askForTargetCloud);
    }

    public function testDisplaysNormalLoginFormIfLocalClientDoesNotExistButCloudIsNotBroker() {
        SuplaAutodiscoverMock::$isBroker = false;
        $client = $this->createHttpsClient();
        $crawler = $client->request('GET', $this->oauthAuthorizeUrl('1_ABC'));
        $routerView = $crawler->filter('router-view')->getNode(0);
        $askForTargetCloud = $routerView->getAttribute(':ask-for-target-cloud');
        $this->assertEquals('false', $askForTargetCloud);
    }

    public function testRedirectsToGivenTargetCloudIfAutodiscoverKnowsIt() {
        SuplaAutodiscoverMock::$clientMapping['https://target.cloud']['1_ABC']['client_id'] = '1_CBA';
        $client = $this->createHttpsClient(false);
        $client->request('GET', $this->oauthAuthorizeUrl('1_ABC'));
        $client->followRedirect();
        $client->request('POST', '/oauth/v2/broker_login', ['_username' => 'ala@supla.org', 'targetCloud' => 'target.cloud']);
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect());
        $targetUrl = $response->headers->get('Location');
        $this->assertContains('https://target.cloud/oauth/v2/auth?', $targetUrl);
        $this->assertContains('client_id=1_CBA', $targetUrl);
        $this->assertContains('scope=account_r', $targetUrl);
        $this->assertContains('ad_username=ala%40supla.org', $targetUrl);
    }

    public function testRedirectsToTargetCloudByAutodiscoveredUsername() {
        SuplaAutodiscoverMock::$clientMapping['https://target.cloud']['1_ABC']['client_id'] = '1_CBA';
        SuplaAutodiscoverMock::$userMapping['ala@supla.org'] = 'target.cloud';
        $client = $this->createHttpsClient(false);
        $client->request('GET', $this->oauthAuthorizeUrl('1_ABC'));
        $client->followRedirect();
        $client->request('POST', '/oauth/v2/broker_login', ['_username' => 'ala@supla.org']);
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect());
        $targetUrl = $response->headers->get('Location');
        $this->assertContains('https://target.cloud/oauth/v2/auth?', $targetUrl);
        $this->assertContains('client_id=1_CBA', $targetUrl);
        $this->assertContains('scope=account_r', $targetUrl);
        $this->assertContains('ad_username=ala%40supla.org', $targetUrl);
    }

    public function testDisplaysErrorIfTargetCloudIsNotRegisteredInAutodiscover() {
        $client = $this->createHttpsClient();
        $client->request('GET', $this->oauthAuthorizeUrl('1_ABC'));
        $crawler = $client->request('POST', '/oauth/v2/broker_login', ['_username' => 'ala@supla.org', 'targetCloud' => 'target.cloud']);
        $routerView = $crawler->filter('router-view')->getNode(0);
        $askForTargetCloud = $routerView->getAttribute('error');
        $this->assertEquals('autodiscover_fail', $askForTargetCloud);
    }

    public function testDisplaysErrorIfUserCannotBeAutodiscovered() {
        $client = $this->createHttpsClient();
        $client->request('GET', $this->oauthAuthorizeUrl('1_ABC'));
        $crawler = $client->request('POST', '/oauth/v2/broker_login', ['_username' => 'ala@supla.org']);
        $routerView = $crawler->filter('router-view')->getNode(0);
        $askForTargetCloud = $routerView->getAttribute('error');
        $this->assertEquals('autodiscover_fail', $askForTargetCloud);
    }

    public function testCreatesNewApiClientForPublicClientInTargetCloudDuringTheFirstRequest() {
        SuplaAutodiscoverMock::$clientMapping['https://supla.local']['1_ABC']['client_id'] = '1_CBA';
        SuplaAutodiscoverMock::$publicClients['1_ABC'] = [
            'name' => 'unicorn',
            'description' => 'Cool app',
            'redirectUris' => ['https://cool.app'],
        ];
        $client = $this->createHttpsClient(false);
        $client->request('GET', $this->oauthAuthorizeUrl('1_CBA'));
        $client->followRedirect(); // to login form
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect()); // to new client id
        $targetUrl = $response->headers->get('Location');
        $createdClient = $this->clientManager->findClientBy(['name' => 'unicorn']);
        $this->assertContains('https://supla.local/oauth/v2/auth?', $targetUrl);
        $this->assertContains('client_id=' . $createdClient->getPublicId(), $targetUrl);
        $this->assertEquals('Cool app', $createdClient->getDescription());
        $this->assertCount(1, $createdClient->getRedirectUris());
    }

    public function testForwardsIssueTokenRequestBasedOnAuthCode() {
        SuplaAutodiscoverMock::$publicClients['1_ABC'] = ['secret' => 'public-secret'];
        SuplaAutodiscoverMock::$clientMapping['https://target.cloud']['1_ABC'] = ['client_id' => '1_CBA', 'secret' => 'target-secret'];
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => '1_ABC',
            'client_secret' => 'public-secret',
            'redirect_uri' => 'https://cool.app',
            'code' => 'ABC.' . base64_encode('https://target.cloud'),
        ];
        $targetCalled = false;
        TargetSuplaCloud::$requestExecutor = function (string $endpoint, array $data) use ($params, &$targetCalled) {
            $this->assertEquals('/oauth/v2/token', $endpoint);
            $this->assertEquals('1_CBA', $data['client_id']);
            $this->assertEquals('target-secret', $data['secret']);
            $this->assertEquals($params['code'], $data['code']);
            $this->assertEquals($params['redirect_uri'], $data['redirect_uri']);
            $targetCalled = true;
            return ['OK', Response::HTTP_OK];
        };
        $client = $this->createHttpsClient(false);
        $client->apiRequest('POST', '/oauth/v2/token', $params);
        $this->assertTrue($targetCalled);
    }

    public function testDoesNotForwardAuthAnywhereIfNotBroker() {
        $this->expectException(PHPUnit_Framework_ExpectationFailedException::class);
        SuplaAutodiscoverMock::$isBroker = false;
        $this->testForwardsIssueTokenRequestBasedOnAuthCode();
    }

    public function testReturnsErrorIfAutodiscoverDoesNotKnowTargetCloudGivenInAuthCode() {
        SuplaAutodiscoverMock::$publicClients['1_ABC'] = ['secret' => 'public-secret'];
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => '1_ABC',
            'client_secret' => 'public-secret',
            'redirect_uri' => 'https://cool.app',
            'code' => 'ABC.' . base64_encode('https://target.cloud'),
        ];
        $targetCalled = false;
        TargetSuplaCloud::$requestExecutor = function () use ($params, &$targetCalled) {
            $targetCalled = true;
            return ['OK', Response::HTTP_OK];
        };
        $client = $this->createHttpsClient();
        $client->apiRequest('POST', '/oauth/v2/token', $params);
        $response = $client->getResponse();
        $this->assertFalse($targetCalled);
        $this->assertFalse($response->isSuccessful());
    }

    public function testReturnsErrorIfPublicSecretDoesNotMatch() {
        SuplaAutodiscoverMock::$publicClients['1_ABC'] = ['secret' => 'public-secret'];
        SuplaAutodiscoverMock::$clientMapping['https://target.cloud']['1_ABC'] = ['client_id' => '1_CBA', 'secret' => 'target-secret'];
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => '1_ABC',
            'client_secret' => 'wrong-secret',
            'redirect_uri' => 'https://cool.app',
            'code' => 'ABC.' . base64_encode('https://target.cloud'),
        ];
        $targetCalled = false;
        TargetSuplaCloud::$requestExecutor = function () use ($params, &$targetCalled) {
            $targetCalled = true;
            return ['OK', Response::HTTP_OK];
        };
        $client = $this->createHttpsClient();
        $client->apiRequest('POST', '/oauth/v2/token', $params);
        $response = $client->getResponse();
        $this->assertFalse($targetCalled);
        $this->assertFalse($response->isSuccessful());
    }

    public function testReturnsErrorIfPublicClientIdDoesNotExist() {
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => '1_ABC',
            'client_secret' => 'public-secret',
            'redirect_uri' => 'https://cool.app',
            'code' => 'ABC.' . base64_encode('https://target.cloud'),
        ];
        $targetCalled = false;
        TargetSuplaCloud::$requestExecutor = function () use ($params, &$targetCalled) {
            $targetCalled = true;
            return ['OK', Response::HTTP_OK];
        };
        $client = $this->createHttpsClient();
        $client->apiRequest('POST', '/oauth/v2/token', $params);
        $response = $client->getResponse();
        $this->assertFalse($targetCalled);
        $this->assertFalse($response->isSuccessful());
    }

    public function testReturnsErrorForInvalidSyntaxOfAuthCode() {
        SuplaAutodiscoverMock::$publicClients['1_ABC'] = ['secret' => 'public-secret'];
        SuplaAutodiscoverMock::$clientMapping['https://target.cloud']['1_ABC'] = ['client_id' => '1_CBA', 'secret' => 'target-secret'];
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => '1_ABC',
            'client_secret' => 'public-secret',
            'redirect_uri' => 'https://cool.app',
            'code' => 'ABC',
        ];
        $targetCalled = false;
        TargetSuplaCloud::$requestExecutor = function () use ($params, &$targetCalled) {
            $targetCalled = true;
            return ['OK', Response::HTTP_OK];
        };
        $client = $this->createHttpsClient();
        $client->apiRequest('POST', '/oauth/v2/token', $params);
        $response = $client->getResponse();
        $this->assertFalse($targetCalled);
        $this->assertFalse($response->isSuccessful());
    }

    public function testForwardsIssueTokenRequestBasedOnRefreshToken() {
        SuplaAutodiscoverMock::$publicClients['1_ABC'] = ['secret' => 'public-secret'];
        SuplaAutodiscoverMock::$clientMapping['https://target.cloud']['1_ABC'] = ['client_id' => '1_CBA', 'secret' => 'target-secret'];
        $params = [
            'grant_type' => 'refresh_token',
            'client_id' => '1_ABC',
            'client_secret' => 'public-secret',
            'refresh_token' => 'ABC.' . base64_encode('https://target.cloud'),
        ];
        $targetCalled = false;
        TargetSuplaCloud::$requestExecutor = function (string $endpoint, array $data) use ($params, &$targetCalled) {
            $this->assertEquals('/oauth/v2/token', $endpoint);
            $this->assertEquals('1_CBA', $data['client_id']);
            $this->assertEquals('target-secret', $data['secret']);
            $this->assertEquals($params['grant_type'], 'refresh_token');
            $this->assertEquals($params['refresh_token'], $data['refresh_token']);
            $this->assertEquals($params['redirect_uri'], $data['redirect_uri']);
            $targetCalled = true;
            return ['OK', Response::HTTP_OK];
        };
        $client = $this->createHttpsClient(false);
        $client->apiRequest('POST', '/oauth/v2/token', $params);
        $this->assertTrue($targetCalled);
    }

    private function oauthAuthorizeUrl($clientId, $redirectUri = 'https://app.com/auth', $scope = 'account_r', $responseType = 'code') {
        return '/oauth/v2/auth?' . http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'scope' => $scope,
                'response_type' => $responseType,
            ]);
    }

    private function createHttpsClient($followRedirects = true): TestClient {
        $client = self::createClient(['debug' => false], [
            'HTTPS' => true,
        ]);
        if ($followRedirects) {
            $client->followRedirects();
        }
        return $client;
    }
}