<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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
 */

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use Illuminate\Filesystem\Filesystem;

class GeneratorsTest extends TestCase
{

    /**
     * @var string
     */
    private $path;

    /**
     * @var Filesystem
     */
    private $files;

    /**
     * @var bool
     */
    private $byResource = true;

    /**
     * @var bool
     */
    private $withoutType = false;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // required for tests to work in Laravel 5.7
        if (method_exists($this, 'withoutMockingConsoleOutput')) {
            $this->withoutMockingConsoleOutput();
        }

        $this->app->setBasePath($this->path = __DIR__ . '/../../dummy');
        $this->files = new Filesystem();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $directories = [
            "{$this->path}/app/JsonApi/Companies",
            "{$this->path}/app/JsonApi/Adapters",
            "{$this->path}/app/JsonApi/Authorizers",
            "{$this->path}/app/JsonApi/ContentNegotiators",
            "{$this->path}/app/JsonApi/Schemas",
            "{$this->path}/app/JsonApi/Validators",
        ];

        foreach ($directories as $dir) {
            if ($this->files->exists($dir)) {
                $this->files->deleteDirectory($dir);
            }
        }

        $files = [
            "{$this->path}/config/json-api-default.php",
            "{$this->path}/config/json-api-foo.php",
            "{$this->path}/app/JsonApi/VisitorAuthorizer.php",
            "{$this->path}/app/JsonApi/JsonContentNegotiator.php",
        ];

        foreach ($files as $file) {
            if ($this->files->exists($file)) {
                $this->files->delete($file);
            }
        }
    }

    /**
     * @return array
     */
    public function byResourceProvider()
    {
        return [
            'by-resource' => [true],
            'not-by-resource' => [false],
            'not-by-resource 0.x' => [false, true],
        ];
    }

    /**
     * We can generate a new API configuration file in our application.
     */
    public function testGeneratesDefaultApi()
    {
        JsonApi::defaultApi('default');

        $result = $this->artisan('make:json-api');

        $this->assertSame(0, $result);
        $this->assertFileEquals(
            __DIR__ . '/../../../stubs/api.php',
            "{$this->path}/config/json-api-default.php"
        );
    }

    /**
     * We can generate a new API configuration file in our application.
     */
    public function testGeneratesNamedApi()
    {
        $result = $this->artisan('make:json-api', ['name' => 'foo']);

        $this->assertSame(0, $result);
        $this->assertFileEquals(
            __DIR__ . '/../../../stubs/api.php',
            "{$this->path}/config/json-api-foo.php"
        );
    }

    /**
     * If Eloquent is set as the default, running the generator without specifying
     * Eloquent will create Eloquent classes.
     */
    public function testEloquentResourceAsDefault()
    {
        $this->withEloquent();

        $result = $this->artisan('make:json-api:resource', [
            'resource' => 'companies',
        ]);

        $this->assertSame(0, $result);
        $this->assertEloquentResource();
    }

    /**
     * If Eloquent is not the default, running the generator with the Eloquent option
     * will create Eloquent classes.
     */
    public function testForceEloquentResource()
    {
        $this->withoutEloquent();

        $result = $this->artisan('make:json-api:resource', [
            'resource' => 'companies',
            '--eloquent' => true,
        ]);

        $this->assertSame(0, $result);
        $this->assertEloquentResource();
    }

    /**
     * Test generating an Eloquent resource with the `by-resource` option set to `false`.
     */
    public function testEloquentResourceNotByResource()
    {
        $this->withEloquent()->notByResource();

        $result = $this->artisan('make:json-api:resource', [
            'resource' => 'companies',
        ]);

        $this->assertSame(0, $result);
        $this->assertEloquentResource();
    }

    /**
     * If Eloquent is not the default, running the generator without specifying
     * anything will create generic classes.
     */
    public function testGenericResourceAsDefault()
    {
        $this->withoutEloquent();

        $result = $this->artisan('make:json-api:resource', [
            'resource' => 'companies',
        ]);

        $this->assertSame(0, $result);
        $this->assertGenericResource();
    }

    /**
     * If Eloquent is the default, running the generator with the non-eloquent option
     * will create generic classes.
     */
    public function testForceGenericResource()
    {
        $this->withEloquent();

        $result = $this->artisan('make:json-api:resource', [
            'resource' => 'companies',
            '--no-eloquent' => true,
        ]);

        $this->assertSame(0, $result);
        $this->assertGenericResource();
    }

    /**
     * Test generating generic resources with the `by-resource` option set to `false`.
     */
    public function testGenericResourceNotByResource()
    {
        $this->withoutEloquent()->notByResource();

        $result = $this->artisan('make:json-api:resource', [
            'resource' => 'companies',
        ]);

        $this->assertSame(0, $result);
        $this->assertGenericResource();
    }

    /**
     * Test generating a reusable authorizer.
     *
     * @param bool $byResource
     * @param bool $withoutType
     * @dataProvider byResourceProvider
     */
    public function testReusableAuthorizer($byResource, $withoutType = false)
    {
        $this->byResource($byResource, $withoutType);

        $result = $this->artisan('make:json-api:authorizer', [
            'name' => 'visitor',
        ]);

        $this->assertSame(0, $result);
        $this->assertReusableAuthorizer();
    }

    /**
     * Test generating a resource-specific authorizer.
     *
     * @param $byResource
     * @param $withoutType
     * @dataProvider byResourceProvider
     */
    public function testResourceAuthorizer($byResource, $withoutType = false)
    {
        $this->byResource($byResource, $withoutType);

        $result = $this->artisan('make:json-api:authorizer', [
            'name' => 'companies',
            '--resource' => true,
        ]);

        $this->assertSame(0, $result);
        $this->assertResourceAuthorizer();
    }

    /**
     * Test generating a resource with an authorizer.
     *
     * @param $byResource
     * @param $withoutType
     * @dataProvider byResourceProvider
     */
    public function testResourceWithAuthorizer($byResource, $withoutType = false)
    {
        $this->byResource($byResource, $withoutType);

        $result = $this->artisan('make:json-api:resource', [
            'resource' => 'companies',
            '--auth' => true,
        ]);

        $this->assertSame(0, $result);
        $this->assertResourceAuthorizer();
    }

    /**
     * Test generating a reusable content negotiator.
     *
     * @param bool $byResource
     * @param bool $withoutType
     * @dataProvider byResourceProvider
     */
    public function testReusableContentNegotiator($byResource, $withoutType = false)
    {
        $this->byResource($byResource, $withoutType);

        $result = $this->artisan('make:json-api:content-negotiator', [
            'name' => 'json',
        ]);

        $this->assertSame(0, $result);
        $this->assertReusableContentNegotiator();
    }

    /**
     * Test generating a resource-specific content negotiator.
     *
     * @param $byResource
     * @param $withoutType
     * @dataProvider byResourceProvider
     */
    public function testResourceContentNegotiator($byResource, $withoutType = false)
    {
        $this->byResource($byResource, $withoutType);

        $result = $this->artisan('make:json-api:content-negotiator', [
            'name' => 'companies',
            '--resource' => true,
        ]);

        $this->assertSame(0, $result);
        $this->assertResourceContentNegotiator();
    }

    /**
     * Test generating a resource with an content negotiator.
     *
     * @param $byResource
     * @param $withoutType
     * @dataProvider byResourceProvider
     */
    public function testResourceWithContentNegotiator($byResource, $withoutType = false)
    {
        $this->byResource($byResource, $withoutType);

        $result = $this->artisan('make:json-api:resource', [
            'resource' => 'companies',
            '--auth' => true,
            '--content-negotiator' => true,
        ]);

        $this->assertSame(0, $result);
        $this->assertResourceContentNegotiator();
    }

    /**
     * @return $this
     */
    private function withEloquent()
    {
        config()->set('json-api-v1.use-eloquent', true);

        return $this;
    }

    /**
     * @return $this
     */
    private function withoutEloquent()
    {
        config()->set('json-api-v1.use-eloquent', false);

        return $this;
    }

    /**
     * @param bool $bool
     * @param bool $withoutType
     * @return $this
     */
    private function byResource($bool, $withoutType = false)
    {
        if (!$bool) {
            $this->notByResource($withoutType);
        }

        return $this;
    }

    /**
     * @param bool $withoutType
     * @return $this
     */
    private function notByResource($withoutType = false)
    {
        $this->byResource = false;
        $this->withoutType = $withoutType;
        config()->set('json-api-v1.by-resource', $withoutType ? 'false-0.x' : false);

        return $this;
    }

    /**
     * @return void
     */
    private function assertEloquentResource()
    {
        $this->assertEloquentAdapter();
        $this->assertEloquentSchema();
        $this->assertValidators();
    }

    /**
     * @return void
     */
    private function assertGenericResource()
    {
        $this->assertGenericAdapter();
        $this->assertGenericSchema();
        $this->assertValidators();
    }

    /**
     * @return void
     */
    private function assertEloquentAdapter()
    {
        $content = $this->assertAdapter();

        $this->assertContentContains('Eloquent\AbstractAdapter', $content);
        $this->assertContentNotContains('use DummyApp\Company;', $content);
        $this->assertContentContains('parent::__construct(new \DummyApp\Company(), $paging);', $content);
    }

    /**
     * @return void
     */
    private function assertGenericAdapter()
    {
        $content = $this->assertAdapter();
        $this->assertContentContains('Adapter\AbstractResourceAdapter', $content);
    }

    /**
     * @return string
     */
    private function assertAdapter()
    {
        if ($this->withoutType) {
            $file = "{$this->path}/app/JsonApi/Adapters/Company.php";
        } else {
            $file = $this->byResource ?
                "{$this->path}/app/JsonApi/Companies/Adapter.php" :
                "{$this->path}/app/JsonApi/Adapters/CompanyAdapter.php";
        }

        $this->assertFileExists($file);
        $content = $this->files->get($file);

        if ($this->byResource) {
            $this->assertContentContains('namespace DummyApp\JsonApi\Companies;', $content);
            $this->assertContentContains('class Adapter extends', $content);
        } else {
            $class = $this->withoutType ? 'Company' : 'CompanyAdapter';
            $this->assertContentContains('namespace DummyApp\JsonApi\Adapters;', $content);
            $this->assertContentContains("class {$class} extends", $content);
        }

        return $content;
    }

    /**
     * @return void
     */
    private function assertEloquentSchema()
    {
        $content = $this->assertSchema();
        $this->assertContentContains('return (string) $resource->getRouteKey();', $content);
    }

    /**
     * @return void
     */
    private function assertGenericSchema()
    {
        $content = $this->assertSchema();
        $this->assertContentNotContains('return (string) $resource->getRouteKey();', $content);
    }

    /**
     * @return string
     */
    private function assertSchema()
    {
        if ($this->withoutType) {
            $file = "{$this->path}/app/JsonApi/Schemas/Company.php";
        } else {
            $file = $this->byResource ?
                "{$this->path}/app/JsonApi/Companies/Schema.php" :
                "{$this->path}/app/JsonApi/Schemas/CompanySchema.php";
        }

        $this->assertFileExists($file);
        $content = $this->files->get($file);

        $this->assertContentContains('extends SchemaProvider', $content);
        $this->assertContentContains("protected \$resourceType = 'companies';", $content);

        if ($this->byResource) {
            $this->assertContentContains('namespace DummyApp\JsonApi\Companies;', $content);
            $this->assertContentContains('class Schema extends', $content);
        } else {
            $class = $this->withoutType ? 'Company' : 'CompanySchema';
            $this->assertContentContains('namespace DummyApp\JsonApi\Schemas;', $content);
            $this->assertContentContains("class {$class} extends", $content);
        }

        return $content;
    }

    /**
     * @return void
     */
    private function assertValidators()
    {
        if ($this->withoutType) {
            $file = "{$this->path}/app/JsonApi/Validators/Company.php";
        } else {
            $file = $this->byResource ?
                "{$this->path}/app/JsonApi/Companies/Validators.php" :
                "{$this->path}/app/JsonApi/Validators/CompanyValidator.php";
        }

        $this->assertFileExists($file);
        $content = $this->files->get($file);

        $this->assertContentNotContains('use DummyApp\Company;', $content);
        $this->assertContentContains('@param mixed|null $record', $content);

        if ($this->byResource) {
            $this->assertContentContains('namespace DummyApp\JsonApi\Companies;', $content);
            $this->assertContentContains('class Validators extends', $content);
        } else {
            $class = $this->withoutType ? 'Company' : 'CompanyValidator';
            $this->assertContentContains('namespace DummyApp\JsonApi\Validators;', $content);
            $this->assertContentContains("class {$class} extends", $content);
        }
    }

    /**
     * @return void
     */
    private function assertReusableAuthorizer()
    {
        if ($this->withoutType) {
            $class = 'Visitor';
            $file = "{$this->path}/app/JsonApi/Authorizers/Visitor.php";
        } else {
            $class = 'VisitorAuthorizer';
            $file = $this->byResource ?
                "{$this->path}/app/JsonApi/VisitorAuthorizer.php" :
                "{$this->path}/app/JsonApi/Authorizers/VisitorAuthorizer.php";
        }


        $this->assertFileExists($file);
        $content = $this->files->get($file);

        $this->assertContentContains("class {$class} extends AbstractAuthorizer", $content);

        if ($this->byResource) {
            $this->assertContentContains('namespace DummyApp\JsonApi;', $content);
        } else {
            $this->assertContentContains('namespace DummyApp\JsonApi\Authorizers;', $content);
        }
    }

    /**
     * @return void
     */
    private function assertResourceAuthorizer()
    {
        if ($this->withoutType) {
            $file = "{$this->path}/app/JsonApi/Authorizers/Company.php";
        } else {
            $file = $this->byResource ?
                "{$this->path}/app/JsonApi/Companies/Authorizer.php" :
                "{$this->path}/app/JsonApi/Authorizers/CompanyAuthorizer.php";
        }


        $this->assertFileExists($file);
        $content = $this->files->get($file);

        if ($this->byResource) {
            $this->assertContentContains('namespace DummyApp\JsonApi\Companies;', $content);
            $this->assertContentContains('class Authorizer extends AbstractAuthorizer', $content);
        } else {
            $class = $this->withoutType ? 'Company' : 'CompanyAuthorizer';
            $this->assertContentContains('namespace DummyApp\JsonApi\Authorizers;', $content);
            $this->assertContentContains("class {$class} extends", $content);
        }
    }

    /**
     * @return void
     */
    private function assertReusableContentNegotiator()
    {
        if ($this->withoutType) {
            $class = 'Json';
            $file = "{$this->path}/app/JsonApi/ContentNegotiators/{$class}.php";
        } else {
            $class = 'JsonContentNegotiator';
            $file = $this->byResource ?
                "{$this->path}/app/JsonApi/{$class}.php" :
                "{$this->path}/app/JsonApi/ContentNegotiators/{$class}.php";
        }


        $this->assertFileExists($file);
        $content = $this->files->get($file);

        $this->assertContentContains("class {$class} extends BaseContentNegotiator", $content);

        if ($this->byResource) {
            $this->assertContentContains('namespace DummyApp\JsonApi;', $content);
        } else {
            $this->assertContentContains('namespace DummyApp\JsonApi\ContentNegotiators;', $content);
        }
    }

    /**
     * @return void
     */
    private function assertResourceContentNegotiator()
    {
        if ($this->withoutType) {
            $file = "{$this->path}/app/JsonApi/ContentNegotiators/Company.php";
        } else {
            $file = $this->byResource ?
                "{$this->path}/app/JsonApi/Companies/ContentNegotiator.php" :
                "{$this->path}/app/JsonApi/ContentNegotiators/CompanyContentNegotiator.php";
        }


        $this->assertFileExists($file);
        $content = $this->files->get($file);

        if ($this->byResource) {
            $this->assertContentContains('namespace DummyApp\JsonApi\Companies;', $content);
            $this->assertContentContains('class ContentNegotiator extends BaseContentNegotiator', $content);
        } else {
            $class = $this->withoutType ? 'Company' : 'CompanyContentNegotiator';
            $this->assertContentContains('namespace DummyApp\JsonApi\ContentNegotiators;', $content);
            $this->assertContentContains("class {$class} extends BaseContentNegotiator", $content);
        }
    }

    /**
     * @param string $expected
     * @param string $content
     * @param string $message
     */
    private function assertContentContains($expected, $content, $message = '')
    {
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString($expected, $content, $message);
        } else {
            $this->assertContains($expected, $content, $message);
        }
    }

    /**
     * @param string $expected
     * @param string $content
     * @param string $message
     */
    private function assertContentNotContains($expected, $content, $message = '')
    {
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString($expected, $content, $message);
        } else {
            $this->assertNotContains($expected, $content, $message);
        }
    }
}
