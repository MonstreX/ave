<?php

namespace Monstrex\Ave\Tests\Unit\Core\Actions;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\CloneRecordAction;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Monstrex\Ave\Core\Resource;
use PHPUnit\Framework\TestCase;

class CloneRecordActionTest extends TestCase
{
    private Capsule $capsule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->capsule = new Capsule();
        $this->capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();

        $this->capsule::schema()->create('clone_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        $this->capsule::schema()->drop('clone_items');
        parent::tearDown();
    }

    public function test_clone_action_creates_duplicate_record(): void
    {
        $original = CloneItem::create([
            'title' => 'Original Title',
            'slug' => 'original-title',
            'status' => true,
        ]);

        $action = new CloneRecordAction();
        $context = ActionContext::row(CloneItemResource::class, new CloneActionUser(), $original);

        $clone = $action->handle($context, Request::create('/', 'POST'));

        $this->assertInstanceOf(CloneItem::class, $clone);
        $this->assertNotSame($original->getKey(), $clone->getKey());
        $this->assertSame('Original Title (copy)', $clone->title);
        $this->assertSame('original-title-copy', $clone->slug);
        $this->assertTrue($clone->status);
    }
}

class CloneItem extends Model
{
    protected $table = 'clone_items';

    protected $fillable = ['title', 'slug', 'status'];
}

class CloneItemResource extends Resource
{
    public static ?string $model = CloneItem::class;

    public static function cloneableFields(): array
    {
        return ['title', 'slug', 'status'];
    }

    public static function mutateCloneAttributes(Model $original, array $attributes): array
    {
        $attributes['title'] = ($attributes['title'] ?? $original->title) . ' (copy)';
        $attributes['slug'] = ($attributes['slug'] ?? $original->slug) . '-copy';

        return $attributes;
    }
}

class CloneActionUser implements \Illuminate\Contracts\Auth\Authenticatable
{
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return 1;
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }

    public function getAuthPassword()
    {
        return 'secret';
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // no-op
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}
