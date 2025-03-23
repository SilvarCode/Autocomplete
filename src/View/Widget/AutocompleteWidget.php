<?php
declare(strict_types=1);

/**
 * ***********************************
 * ||       AutocompleteWidget      ||
 * ***********************************
 *
 * @copyright   2022 SilvarCode / SilvarCode.com
 *              All rights reserved.
 * @link        https://silvarcode.com
 * @since       1.0.0
 * @license     MIT License - see LICENSE.txt for more details.
 *              Redistributions of files must retain the above notice.
 *              https://opensource.org/licenses/mit-license.php MIT License
 */
namespace SilvarCode\Autocomplete\View\Widget;

use Cake\Routing\Router;
use Cake\Utility\Hash;
use Cake\View\Form\ContextInterface;
use Cake\View\Helper\IdGeneratorTrait;
use Cake\View\StringTemplate;
use Cake\View\Widget\WidgetInterface;

class AutocompleteWidget implements WidgetInterface
{
    use IdGeneratorTrait;

    /**
     * StringTemplate instance.
     *
     * @var StringTemplate
     */
    protected StringTemplate $templates;

    /**
     * @var array
     */
    protected array $defaults = [
        'name' => '',
        'label' => null,
        'multiple' => null,
        'options' => null,
        'currentValues' => [],
        'autocomplete' => 'off',
        'itemOptions' => [],
        'data-url' => '',
        'val' => null,
    ];

    /**
     * Constructor.
     *
     * @param StringTemplate $templates Templates list.
     */
    public function __construct(StringTemplate $templates)
    {
        $this->templates = $templates;
        unset($templates);

        $this->templates->add([
            'autocompleteInput' => ' <input type="autocomplete" name="{{name}}" {{attrs}} />',
            'autocompleteSelect' => '<select name="{{name}}"{{attrs}}>{{content}}</select>',
            'autocompleteSelectMultiple' => '<select name="{{name}}[]" multiple="multiple"{{attrs}}>{{content}}</select>',
            'autocompleteContainer' => '<div class="autocomplete">{{content}}</div>',
            'autocompleteShow' => '<div id="{{id}}" class="{{class}}">{{content}}</div>',
            'autocompleteShowItem' => '<span class="autocomplete-selection-item">{{text}}{{remove}}</span>',
            'autocompleteShowItemText' => '<span class="text">{{text}}</span>',
            'autocompleteShowItemRemove' => '<span class="remove-button">{{text}}</span>',
            'autocompleteShowItemRemoveIcon' => '<i class="fa fa-times" data-hidden-value="{{value}}"></i>',
            'autocompleteOption' => '<option value="{{value}}"{{attrs}}>{{text}}</option>',
        ]);
    }

    /**
     * @param string $string
     * @param string $suffix
     * @return bool
     */
    public static function hasSuffix(string $string, string $suffix): bool
    {
        $strlen = strlen($suffix);
        if (($strlen > 0) && (substr($string, -$strlen, $strlen) === $suffix)) {
            return true;
        }

        return false;
    }

    /**
     * Methods that render the widget.
     *
     * @param array $data The data to build an input with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string
     */
    public function render(array $data, ContextInterface $context): string
    {
        $data = array_merge($this->defaults, $data);
        $data = array_merge(['id' => $this->_domId($data['name'])], $data);
        if (empty($data['multiple']) && self::hasSuffix($data['fieldName'], '_ids')) {
            $data['multiple'] = true;
        }

        if (!empty($data['data-url'])) {
            $data['data-url'] = Router::url(
                $data['data-url'],
                true
            );
        }

        $data['val'] = (array)$data['val'];
        $data['data-options'] = json_encode($this->formatOptions((array)$data['options']));
        $multiple = in_array($data['multiple'], [1, '1', 'true', 'multiple']);
        $currentValues = !empty($data['val']) ? [] : (array)$data['currentValues'];
        foreach (json_decode($data['data-options']) as $op) {
            if (in_array($op->value, $data['val'])) {
                $currentValues[$op->value] = (array)$op;
            }
        }

        unset($data['multiple'], $data['currentValues'], $data['val'], $data['options']);
        // We must rely on a class to manipulate the input later.
        $data = array_merge(['id' => $this->_domId($data['name'])], $data);
        $classes = preg_split('/\s+/', Hash::get($data, 'class', ''));
        $classes[] = 'sc-autocomplete';
        $classes = array_unique(array_map('trim', $classes));
        sort($classes);
        $data['class'] = implode(' ', $classes);
        unset($classes);

        // Input
        $input = $this->templates->format('input', [
            'type' => 'text',
            'name' => $data['name'],
            'attrs' => $this->templates->formatAttributes($data, ['name']),
        ]);

        $multiline = boolval($data['multiline'] ?? false);
        $showClass = ['autocomplete-selection', $multiline ? 'multiline' : null];
        $showClass = implode(' ', array_filter($showClass));

        // Current values
        $show = $this->templates->format('autocompleteShow', [
            'id' => "{$data['id']}-show",
            'class' => $showClass,
            'content' => implode("\n", $this->getShowItems($currentValues)),
        ]);

        $data['id'] = $data['id'] . '-hidden';
        foreach ($data as $key => $value) {
            if (!in_array($key, ['id', 'name'])) {
                unset($data[$key]);
            }
        }

        // Hidden
        $data['style'] = 'display:none !important;';
        $select = $multiple ? 'autocompleteSelectMultiple' : 'autocompleteSelect';
        $hidden = $this->templates->format('hiddenBlock', [
            'content' => $this->templates->format($select, [
                'name' => $data['name'],
                'content' => implode("\n", $this->getSelectOptions($currentValues)),
                'attrs' => $this->templates->formatAttributes($data, ['name']),
            ]),
        ]);

        return $show . $input . $hidden;
    }

    /**
     * @param array $option
     * @return string
     */
    protected function getShowItem(array $option): string
    {
        return $this->templates->format('autocompleteShowItem', [
            'text' => $this->templates->format('autocompleteShowItemText', [
                'text' => $option['text'],
            ]),
            'remove' => $this->templates->format('autocompleteShowItemRemove', [
                'text' => $this->templates->format('autocompleteShowItemRemoveIcon', [
                    'value' => $option['value'],
                ]),
            ]),
        ]);
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getShowItems(array $options): array
    {
        foreach ($options as $key => $option) {
            if (!is_array($option)) {
                $option = [
                    'value' => $option,
                    'text' => $option,
                ];
            }

            $options[$key] = $this->getShowItem($option);
        }

        return $options;
    }

    /**
     * @param array $option
     * @return string
     */
    protected function getOption(array $option): string
    {
        return $this->templates->format('autocompleteOption', [
            'value' => $option['value'],
            'text' => $option['text'],
            'attrs' => $this->templates->formatAttributes($option, ['value', 'text']),
        ]);
    }

    /**
     * @param array $options
     * @return array
     */
    protected function formatOptions(array $options): array
    {
        foreach ($options as $key => $option) {
            if (!is_array($option)) {
                $option = [
                    'value' => $key,
                    'text' => $option,
                ];
            }

            if (
                !isset($option['value'], $option['text']) &&
                (isset($option[0], $option[1]))
            ) {
                $option = ['value' => $option[0], 'text' => $option[1]];
            }

            $options[$key] = $option;
        }

        return $options;
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getSelectOptions(array $options): array
    {
        $options = $this->formatOptions($options);

        foreach ($options as $key => $option) {
            $options[$key] = $this->getOption($option);
        }

        return $options;
    }

    /**
     * @param array $data
     * @return array|string[]
     */
    public function secureFields(array $data): array
    {
        return [$data['name']];
    }
}
