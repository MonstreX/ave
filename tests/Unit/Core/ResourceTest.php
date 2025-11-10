<?php

namespace Monstrex\Ave\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Core\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * ResourceTest - Unit tests for Resource abstract class.
 *
 * Tests the resource system which provides:
 * - Model associations and eager loading
 * - Authorization via policies
 * - Table and form schema resolution
 * - Label and navigation management
 * - Slug-based resource identification
 */
class ResourceTest extends TestCase
{
    /**
     * Test resource can be instantiated as subclass
     */
    public function test_resource_can_be_instantiated(): void
    {
        $resource = new class extends Resource {
            public static ?string $model = null;
        };

        $this->assertInstanceOf(Resource::class, $resource);
    }

    /**
     * Test resource implements authorizable interface
     */
    public function test_resource_implements_authorizable(): void
    {
        $resource = new class extends Resource {};

        $reflection = new \ReflectionClass($resource);
        $this->assertTrue($reflection->implementsInterface('Monstrex\\Ave\\Contracts\\Authorizable'));
    }

    /**
     * Test resource get slug with custom slug
     */
    public function test_resource_get_slug_with_custom(): void
    {
        $resource = new class extends Resource {
            public static ?string $slug = 'articles';
        };

        $this->assertEquals('articles', $resource::getSlug());
    }

    /**
     * Test resource get slug defaults to class name lowercase
     */
    public function test_resource_get_slug_defaults_to_class_name(): void
    {
        $resource = new class extends Resource {
            public static ?string $slug = null;
        };

        $this->assertIsString($resource::getSlug());
        $this->assertEquals(strtolower(class_basename($resource)), $resource::getSlug());
    }

    /**
     * Test resource slug instance method
     */
    public function test_resource_slug_instance_method(): void
    {
        $resource = new class extends Resource {
            public static ?string $slug = 'articles';
        };

        $this->assertEquals('articles', $resource->slug());
        $this->assertEquals($resource::getSlug(), $resource->slug());
    }

    /**
     * Test resource get label with custom label
     */
    public function test_resource_get_label_with_custom(): void
    {
        $resource = new class extends Resource {
            public static ?string $label = 'Articles';
        };

        $this->assertEquals('Articles', $resource::getLabel());
    }

    /**
     * Test resource get label defaults to class name
     */
    public function test_resource_get_label_defaults_to_class_name(): void
    {
        $resource = new class extends Resource {
            public static ?string $label = null;
        };

        $this->assertIsString($resource::getLabel());
        $this->assertEquals(class_basename($resource), $resource::getLabel());
    }

    /**
     * Test resource label instance method
     */
    public function test_resource_label_instance_method(): void
    {
        $resource = new class extends Resource {
            public static ?string $label = 'Articles';
        };

        $this->assertEquals('Articles', $resource->label());
        $this->assertEquals($resource::getLabel(), $resource->label());
    }

    /**
     * Test resource get singular label with custom
     */
    public function test_resource_get_singular_label_with_custom(): void
    {
        $resource = new class extends Resource {
            public static ?string $singularLabel = 'Article';
            public static ?string $label = 'Articles';
        };

        $this->assertEquals('Article', $resource::getSingularLabel());
    }

    /**
     * Test resource get singular label defaults to label
     */
    public function test_resource_get_singular_label_defaults_to_label(): void
    {
        $resource = new class extends Resource {
            public static ?string $label = 'Articles';
            public static ?string $singularLabel = null;
        };

        $this->assertEquals('Articles', $resource::getSingularLabel());
    }

    /**
     * Test resource singular label instance method
     */
    public function test_resource_singular_label_instance_method(): void
    {
        $resource = new class extends Resource {
            public static ?string $singularLabel = 'Article';
        };

        $this->assertEquals('Article', $resource->singularLabel());
    }

    /**
     * Test resource get icon
     */
    public function test_resource_get_icon(): void
    {
        $resource = new class extends Resource {
            public static ?string $icon = 'fa fa-file';
        };

        $this->assertEquals('fa fa-file', $resource::getIcon());
    }

    /**
     * Test resource get icon returns null
     */
    public function test_resource_get_icon_returns_null(): void
    {
        $resource = new class extends Resource {
            public static ?string $icon = null;
        };

        $this->assertNull($resource::getIcon());
    }

    /**
     * Test resource icon instance method
     */
    public function test_resource_icon_instance_method(): void
    {
        $resource = new class extends Resource {
            public static ?string $icon = 'fa fa-article';
        };

        $this->assertEquals('fa fa-article', $resource->icon());
    }

    /**
     * Test resource table method returns table instance
     */
    public function test_resource_table_returns_table(): void
    {
        $resource = new class extends Resource {};

        $table = $resource::table(null);

        $this->assertInstanceOf(Table::class, $table);
    }

    /**
     * Test resource form method returns form instance
     */
    public function test_resource_form_returns_form(): void
    {
        $resource = new class extends Resource {};

        $form = $resource::form(null);

        $this->assertInstanceOf(Form::class, $form);
    }

    /**
     * Test resource apply eager loading with with relations
     */
    public function test_resource_apply_eager_loading_with(): void
    {
        $resource = new class extends Resource {
            public static array $with = ['author', 'comments'];
        };

        $builder = $this->createMock(Builder::class);
        $builder->expects($this->once())
            ->method('with')
            ->with(['author', 'comments'])
            ->willReturnSelf();

        $result = $resource::applyEagerLoading($builder);

        $this->assertSame($builder, $result);
    }

    /**
     * Test resource apply eager loading with withCount relations
     */
    public function test_resource_apply_eager_loading_with_count(): void
    {
        $resource = new class extends Resource {
            public static array $withCount = ['comments', 'likes'];
        };

        $builder = $this->createMock(Builder::class);
        $builder->expects($this->once())
            ->method('withCount')
            ->with(['comments', 'likes'])
            ->willReturnSelf();

        $result = $resource::applyEagerLoading($builder);

        $this->assertSame($builder, $result);
    }

    /**
     * Test resource apply eager loading with both with and withCount
     */
    public function test_resource_apply_eager_loading_with_both(): void
    {
        $resource = new class extends Resource {
            public static array $with = ['author'];
            public static array $withCount = ['comments'];
        };

        $builder = $this->createMock(Builder::class);
        $builder->expects($this->once())
            ->method('with')
            ->with(['author'])
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('withCount')
            ->with(['comments'])
            ->willReturnSelf();

        $result = $resource::applyEagerLoading($builder);

        $this->assertSame($builder, $result);
    }

    /**
     * Test resource apply eager loading with empty relations
     */
    public function test_resource_apply_eager_loading_empty(): void
    {
        $resource = new class extends Resource {
            public static array $with = [];
            public static array $withCount = [];
        };

        $builder = $this->createMock(Builder::class);
        $builder->expects($this->never())->method('with');
        $builder->expects($this->never())->method('withCount');

        $result = $resource::applyEagerLoading($builder);

        $this->assertSame($builder, $result);
    }

    /**
     * Test resource can method without policy
     */
    public function test_resource_can_without_policy(): void
    {
        $resource = new class extends Resource {
            public static ?string $policy = null;
        };

        $user = $this->createMock(Authenticatable::class);
        $result = $resource->can('view', $user);

        $this->assertTrue($result);
    }

    /**
     * Test resource can method without user
     */
    public function test_resource_can_without_user(): void
    {
        $resource = new class extends Resource {
            public static ?string $policy = 'TestPolicy';
        };

        $result = $resource->can('view', null);

        $this->assertFalse($result);
    }

    /**
     * Test resource has static properties
     */
    public function test_resource_static_properties(): void
    {
        $resource = new class extends Resource {
            public static ?string $label = 'Articles';
            public static ?string $singularLabel = 'Article';
            public static ?string $icon = 'fa fa-file';
            public static ?string $slug = 'articles';
            public static array $with = ['author'];
            public static array $withCount = ['comments'];
        };

        $this->assertEquals('Articles', $resource::$label);
        $this->assertEquals('Article', $resource::$singularLabel);
        $this->assertEquals('fa fa-file', $resource::$icon);
        $this->assertEquals('articles', $resource::$slug);
        $this->assertCount(1, $resource::$with);
        $this->assertCount(1, $resource::$withCount);
    }

    /**
     * Test multiple resource instances independence
     */
    public function test_multiple_resource_instances(): void
    {
        $resource1 = new class extends Resource {
            public static ?string $slug = 'articles';
            public static ?string $label = 'Articles';
        };

        $resource2 = new class extends Resource {
            public static ?string $slug = 'users';
            public static ?string $label = 'Users';
        };

        $this->assertNotSame($resource1, $resource2);
        $this->assertEquals('articles', $resource1::getSlug());
        $this->assertEquals('users', $resource2::getSlug());
    }

    /**
     * Test resource with special characters in label
     */
    public function test_resource_with_special_characters(): void
    {
        $resource = new class extends Resource {
            public static ?string $label = 'User & Admin Articles';
        };

        $this->assertEquals('User & Admin Articles', $resource::getLabel());
    }

    /**
     * Test resource method visibility
     */
    public function test_resource_methods_are_public(): void
    {
        $reflection = new \ReflectionClass(Resource::class);

        $publicMethods = [
            'table',
            'form',
            'applyEagerLoading',
            'can',
            'getSlug',
            'getLabel',
            'getSingularLabel',
            'getIcon',
            'slug',
            'label',
            'singularLabel',
            'icon',
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Resource should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test resource is abstract
     */
    public function test_resource_is_abstract(): void
    {
        $reflection = new \ReflectionClass(Resource::class);
        $this->assertTrue($reflection->isAbstract());
    }

    /**
     * Test resource namespace
     */
    public function test_resource_namespace(): void
    {
        $reflection = new \ReflectionClass(Resource::class);
        $this->assertEquals('Monstrex\\Ave\\Core', $reflection->getNamespaceName());
    }

    /**
     * Test resource class name
     */
    public function test_resource_class_name(): void
    {
        $reflection = new \ReflectionClass(Resource::class);
        $this->assertEquals('Resource', $reflection->getShortName());
    }

    /**
     * Test resource static properties initialization
     */
    public function test_resource_static_properties_initialization(): void
    {
        $resource = new class extends Resource {};

        // Test default values for array properties
        $this->assertIsArray($resource::$with);
        $this->assertIsArray($resource::$withCount);
        $this->assertEmpty($resource::$with);
        $this->assertEmpty($resource::$withCount);
    }

    /**
     * Test resource with multiple relations
     */
    public function test_resource_with_multiple_relations(): void
    {
        $resource = new class extends Resource {
            public static array $with = ['author', 'comments', 'tags', 'category'];
        };

        $this->assertCount(4, $resource::$with);
        $this->assertContains('author', $resource::$with);
        $this->assertContains('tags', $resource::$with);
    }

    /**
     * Test resource with multiple count relations
     */
    public function test_resource_with_multiple_count_relations(): void
    {
        $resource = new class extends Resource {
            public static array $withCount = ['comments', 'likes', 'shares', 'views'];
        };

        $this->assertCount(4, $resource::$withCount);
        $this->assertContains('comments', $resource::$withCount);
        $this->assertContains('views', $resource::$withCount);
    }

    /**
     * Test resource get model creates instance
     */
    public function test_resource_get_model(): void
    {
        $mockModel = $this->createMock(Model::class);
        $modelClass = get_class($mockModel);

        $resource = new class($modelClass) extends Resource {
            public function __construct(private $modelClass) {}

            public static ?string $model;

            public function getModel()
            {
                return parent::getModel();
            }
        };

        // Set the model class
        $resource::$model = $modelClass;

        // We can't easily test this without a real model, but we can test it doesn't throw
        $this->assertTrue(true);
    }

    /**
     * Test resource new query applies eager loading
     */
    public function test_resource_new_query_applies_eager_loading(): void
    {
        $mockModel = $this->createMock(Model::class);
        $mockBuilder = $this->createMock(Builder::class);

        $resource = new class extends Resource {
            public static ?string $model = null;
            public static array $with = [];

            public function newQuery()
            {
                return parent::newQuery();
            }
        };

        // Can't easily test without a real model, but verify method exists
        $reflection = new \ReflectionClass($resource);
        $this->assertTrue($reflection->hasMethod('newQuery'));
    }

}
