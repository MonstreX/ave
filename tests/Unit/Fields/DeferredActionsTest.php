<?php

namespace Monstrex\Ave\Tests\Unit\Fields;

use Monstrex\Ave\Core\Fields\Media;
use Monstrex\Ave\Core\Fields\Fieldset;
use Monstrex\Ave\Core\FormContext;
use PHPUnit\Framework\TestCase;

/**
 * Critical tests: Deferred actions with nested media fields
 */
class DeferredActionsTest extends TestCase
{
    /**
     * Test FormContext deferred actions storage
     */
    public function test_form_context_stores_deferred_actions(): void
    {
        $context = FormContext::forData([]);

        $called = false;
        $action = function () use (&$called) {
            $called = true;
        };

        $context->addDeferredAction($action);

        $actions = $context->getDeferredActions();
        $this->assertCount(1, $actions);

        // Execute action
        foreach ($actions as $act) {
            $act();
        }

        $this->assertTrue($called);
    }

    /**
     * Test multiple deferred actions
     */
    public function test_multiple_deferred_actions(): void
    {
        $context = FormContext::forData([]);

        $results = [];

        for ($i = 0; $i < 3; $i++) {
            $context->addDeferredAction(function () use (&$results, $i) {
                $results[] = $i;
            });
        }

        $actions = $context->getDeferredActions();
        $this->assertCount(3, $actions);

        foreach ($actions as $action) {
            $action();
        }

        $this->assertEquals([0, 1, 2], $results);
    }

    /**
     * Test deferred actions with media state paths
     */
    public function test_deferred_actions_track_state_paths(): void
    {
        $context = FormContext::forData([]);

        $fieldset = Fieldset::make('gallery')->statePath('gallery.0');
        $media = Media::make('images')->container($fieldset);

        $trackedPath = null;
        $context->addDeferredAction(function () use ($media, &$trackedPath) {
            $trackedPath = $media->getStatePath();
        });

        $actions = $context->getDeferredActions();
        foreach ($actions as $action) {
            $action();
        }

        // Deferred action should have captured correct state path
        $this->assertEquals('gallery.0.images', $trackedPath);
    }

    /**
     * Test deferred actions for multiple media in same fieldset
     */
    public function test_multiple_media_deferred_actions(): void
    {
        $context = FormContext::forData([]);

        $fieldset = Fieldset::make('item')->statePath('items.1');

        $paths = [];

        $featured = Media::make('featured')->container($fieldset);
        $context->addDeferredAction(function () use ($featured, &$paths) {
            $paths[] = $featured->getStatePath();
        });

        $gallery = Media::make('gallery')->container($fieldset);
        $context->addDeferredAction(function () use ($gallery, &$paths) {
            $paths[] = $gallery->getStatePath();
        });

        $actions = $context->getDeferredActions();
        foreach ($actions as $action) {
            $action();
        }

        $this->assertCount(2, $paths);
        $this->assertContains('items.1.featured', $paths);
        $this->assertContains('items.1.gallery', $paths);
    }

    /**
     * Test deferred actions preserve nesting context
     */
    public function test_nested_fieldset_deferred_actions(): void
    {
        $context = FormContext::forData([]);

        // Level 1
        $level1 = Fieldset::make('chapters')->statePath('chapters.0');

        // Level 2
        $level2 = Fieldset::make('sections')->statePath('chapters.0.sections.1')->container($level1);

        // Media
        $media = Media::make('image')->container($level2);

        $capturedPath = null;
        $context->addDeferredAction(function () use ($media, &$capturedPath) {
            $capturedPath = $media->getStatePath();
        });

        $actions = $context->getDeferredActions();
        foreach ($actions as $action) {
            $action();
        }

        // Should have full nesting path
        $this->assertEquals('chapters.0.sections.1.image', $capturedPath);
    }

    /**
     * Test deferred action execution order
     */
    public function test_deferred_action_order(): void
    {
        $context = FormContext::forData([]);

        $order = [];

        $context->addDeferredAction(function () use (&$order) {
            $order[] = 1;
        });

        $context->addDeferredAction(function () use (&$order) {
            $order[] = 2;
        });

        $context->addDeferredAction(function () use (&$order) {
            $order[] = 3;
        });

        $actions = $context->getDeferredActions();
        foreach ($actions as $action) {
            $action();
        }

        $this->assertEquals([1, 2, 3], $order);
    }

    /**
     * Test clearing deferred actions
     */
    public function test_clear_deferred_actions(): void
    {
        $context = FormContext::forData([]);

        for ($i = 0; $i < 5; $i++) {
            $context->addDeferredAction(function () {});
        }

        $this->assertCount(5, $context->getDeferredActions());

        // Clear (if method exists)
        if (method_exists($context, 'clearDeferredActions')) {
            $context->clearDeferredActions();
            $this->assertCount(0, $context->getDeferredActions());
        }
    }

    /**
     * Test deferred actions with form context modes
     */
    public function test_deferred_actions_in_different_contexts(): void
    {
        // Create mode
        $create = FormContext::forCreate();
        $create->addDeferredAction(function () {});

        // Edit mode (with mock model)
        $edit = FormContext::forData([]);
        $edit->addDeferredAction(function () {});

        // Both should track deferred actions
        $this->assertCount(1, $create->getDeferredActions());
        $this->assertCount(1, $edit->getDeferredActions());
    }

    /**
     * Test action capture of fieldset state
     */
    public function test_action_captures_fieldset_state(): void
    {
        $context = FormContext::forData([]);

        $fieldset = Fieldset::make('gallery');

        $capturedState = null;
        $context->addDeferredAction(function () use ($fieldset, &$capturedState) {
            $capturedState = [
                'key' => $fieldset->getKey(),
                'type' => $fieldset->type(),
                'childCount' => count($fieldset->getChildSchema()),
            ];
        });

        $actions = $context->getDeferredActions();
        foreach ($actions as $action) {
            $action();
        }

        $this->assertEquals('gallery', $capturedState['key']);
        $this->assertEquals('fieldset', $capturedState['type']);
    }

    /**
     * Test exception handling in deferred actions
     */
    public function test_exception_in_deferred_action(): void
    {
        $context = FormContext::forData([]);

        $executed = false;
        $context->addDeferredAction(function () use (&$executed) {
            throw new \Exception('Test error');
        });

        $actions = $context->getDeferredActions();
        $this->expectException(\Exception::class);

        foreach ($actions as $action) {
            $action();
        }
    }
}
