<?php

namespace Monstrex\Ave\Core\Fields;

use Monstrex\Ave\Core\Forms\FormContext;

/**
 * RichEditor Field - поле для визуального редактирования HTML контента (WYSIWYG)
 *
 * Адаптация v1 RichEditor для v2 с использованием Jodit Editor.
 * Поддерживает:
 * - Visual HTML editing (WYSIWYG)
 * - Source code view с Ace Editor
 * - Загрузка изображений
 * - Форматирование текста (bold, italic, underline, strikethrough)
 * - Списки (ul, ol)
 * - Заголовки (h1-h6)
 * - Ссылки и таблицы
 * - Различные пресеты toolbar (minimal, basic, full)
 * - Настраиваемая высота
 */
class RichEditor extends AbstractField
{
    /**
     * Высота редактора в пикселях
     */
    protected int $height = 400;

    /**
     * Пресет toolbar
     * Поддерживаемые значения: 'minimal', 'basic', 'full'
     *
     * minimal: bold, italic, lists
     * basic: headings, bold, italic, link, lists
     * full: headings, bold, italic, link, image, lists, blockquote, code
     */
    protected string $toolbar = 'full';

    /**
     * Показывать ли меню бар
     */
    protected bool $showMenuBar = true;

    /**
     * Максимальная длина HTML содержимого в символах
     */
    protected ?int $maxLength = null;

    /**
     * Позволить ли использование inline стилей
     */
    protected bool $allowInlineStyles = true;

    /**
     * Позволить ли загрузку изображений
     */
    protected bool $allowImageUpload = true;

    /**
     * Позволить ли создание таблиц
     */
    protected bool $allowTables = true;

    /**
     * Позволить ли использование списков
     */
    protected bool $allowLists = true;

    /**
     * Позволить ли создание ссылок
     */
    protected bool $allowLinks = true;

    /**
     * Позволить ли использование blockquote
     */
    protected bool $allowBlockquote = true;

    /**
     * Позволить ли использование кода/pre
     */
    protected bool $allowCode = true;

    /**
     * Placeholder текст для пустого редактора
     */
    protected ?string $editorPlaceholder = null;

    /**
     * Установить высоту редактора в пикселях
     */
    public function height(int $height): static
    {
        $this->height = max(200, $height); // Минимум 200px
        return $this;
    }

    /**
     * Установить пресет toolbar
     *
     * @param string $toolbar 'minimal', 'basic', или 'full'
     */
    public function toolbar(string $toolbar): static
    {
        if (in_array($toolbar, ['minimal', 'basic', 'full'])) {
            $this->toolbar = $toolbar;
        }
        return $this;
    }

    /**
     * Показывать/скрывать меню bar
     */
    public function showMenuBar(bool $show = true): static
    {
        $this->showMenuBar = $show;
        return $this;
    }

    /**
     * Установить максимальную длину контента в символах
     */
    public function maxLength(int $length): static
    {
        $this->maxLength = max(100, $length); // Минимум 100 символов
        return $this;
    }

    /**
     * Позволить/запретить inline стили
     */
    public function allowInlineStyles(bool $allow = true): static
    {
        $this->allowInlineStyles = $allow;
        return $this;
    }

    /**
     * Позволить/запретить загрузку изображений
     */
    public function allowImageUpload(bool $allow = true): static
    {
        $this->allowImageUpload = $allow;
        return $this;
    }

    /**
     * Позволить/запретить таблицы
     */
    public function allowTables(bool $allow = true): static
    {
        $this->allowTables = $allow;
        return $this;
    }

    /**
     * Позволить/запретить списки
     */
    public function allowLists(bool $allow = true): static
    {
        $this->allowLists = $allow;
        return $this;
    }

    /**
     * Позволить/запретить ссылки
     */
    public function allowLinks(bool $allow = true): static
    {
        $this->allowLinks = $allow;
        return $this;
    }

    /**
     * Позволить/запретить blockquote
     */
    public function allowBlockquote(bool $allow = true): static
    {
        $this->allowBlockquote = $allow;
        return $this;
    }

    /**
     * Позволить/запретить код/pre
     */
    public function allowCode(bool $allow = true): static
    {
        $this->allowCode = $allow;
        return $this;
    }

    /**
     * Установить placeholder текст
     */
    public function placeholder(string $text): static
    {
        $this->editorPlaceholder = $text;
        return $this;
    }

    /**
     * Получить высоту редактора
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Получить пресет toolbar
     */
    public function getToolbar(): string
    {
        return $this->toolbar;
    }

    /**
     * Проверить, показывается ли меню bar
     */
    public function hasMenuBar(): bool
    {
        return $this->showMenuBar;
    }

    /**
     * Получить максимальную длину контента
     */
    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    /**
     * Получить placeholder текст
     */
    public function getPlaceholder(): ?string
    {
        return $this->editorPlaceholder;
    }

    /**
     * Получить конфигурацию для JavaScript
     */
    public function getJsConfig(): array
    {
        $config = [
            'height' => $this->height,
            'theme' => 'default',
            'allowInlineStyles' => $this->allowInlineStyles,
            'allowImageUpload' => $this->allowImageUpload,
            'allowTables' => $this->allowTables,
            'allowLists' => $this->allowLists,
            'allowLinks' => $this->allowLinks,
            'allowBlockquote' => $this->allowBlockquote,
            'allowCode' => $this->allowCode,
        ];

        // Отключить функции если не разрешены
        if (!$this->allowTables) {
            $config['disablePlugins'] = array_merge($config['disablePlugins'] ?? [], ['table']);
        }

        if (!$this->allowImageUpload) {
            $config['disablePlugins'] = array_merge($config['disablePlugins'] ?? [], ['image']);
        }

        // Применить пресет toolbar
        $config['buttons'] = $this->getToolbarButtons();

        return $config;
    }

    /**
     * Получить кнопки toolbar на основе пресета
     */
    protected function getToolbarButtons(): array
    {
        $buttons = match ($this->toolbar) {
            'minimal' => [
                'bold', 'italic', 'ul', 'ol'
            ],
            'basic' => [
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6', '|',
                'ul', 'ol', '|',
                'link', 'image'
            ],
            'full' => [
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6', '|',
                'font', 'fontsize', 'brush', 'paragraph', '|',
                'ul', 'ol', '|',
                'link', 'image', 'table', '|',
                'blockquote', 'code', '|',
                'undo', 'redo', '|',
                'source'
            ],
            default => ['source']
        };

        return $buttons;
    }

    /**
     * Подготовить для отображения
     */
    public function prepareForDisplay(FormContext $context): void
    {
        // Заполнить значение из источника данных
        $this->fillFromDataSource($context->dataSource());
    }

    /**
     * Преобразовать в массив для Blade шаблона
     */
    public function toArray(): array
    {
        $value = $this->getValue() ?? '';

        // Убедиться что это строка
        if (!is_string($value)) {
            $value = (string)$value;
        }

        return array_merge(parent::toArray(), [
            'type' => 'rich-editor',
            'height' => $this->getHeight(),
            'toolbar' => $this->getToolbar(),
            'showMenuBar' => $this->hasMenuBar(),
            'maxLength' => $this->getMaxLength(),
            'placeholder' => $this->getPlaceholder() ?? 'Начните печатать...',
            'jsConfig' => json_encode($this->getJsConfig()),
            'value' => $value,
        ]);
    }

    /**
     * Отобразить поле
     */
    public function render(FormContext $context): string
    {
        if (is_null($this->getValue())) {
            $this->prepareForDisplay($context);
        }

        $view = $this->view ?: 'ave::components.forms.rich-editor';

        return view($view, [
            'field' => $this,
            'context' => $context,
        ])->render();
    }
}
