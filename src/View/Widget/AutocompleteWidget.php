<?php 
declare(strict_types=1);

/**
 * ***********************************
 * ||       AutocompleteWidget      ||
 * ***********************************
 * @copyright   2022 SilvarCode / SilvarCode.com
 *              All rights reserved.
 * @link        https://silvarcode.com
 * @since       1.0.0
 * @license     MIT License - see LICENSE.txt for more details.
 *              Redistributions of files must retain the above notice.
 *              https://opensource.org/licenses/mit-license.php MIT License
 */
namespace SilvarCode\Autocomplete\View\Widget;

use Cake\View\StringTemplate;
use Cake\View\Widget\BasicWidget;
use Cake\View\Form\ContextInterface;
use Cake\View\Helper\IdGeneratorTrait;
use Cake\Routing\Router;
use Cake\Utility\Hash;

class AutocompleteWidget extends BasicWidget
{
    use IdGeneratorTrait;

    /**
     * StringTemplate instance.
     *
     * @var \Cake\View\StringTemplate
     */
    protected $_templates = [
        'autocompleteInput' =>' <input type="autocomplete" name="{{name}}" {{attrs}} />',
        'autocompleteSelect' => '<select name="{{name}}" {{attrs}}>{{content}}</select>',
        'autocompleteSelectMultiple' => '<select name="{{name}}[]" multiple="multiple"{{attrs}}>{{content}}</select>',
        'autocompleteContainer' => '<div class="autocomplete">{{content}}</div>',
        'autocompleteShow' => '<div id="{{id}}" class="autocomplete-selection">{{content}}</div>',
        'autocompleteShowItem' => '<span class="autocomplete-selection-item">
            <span class="text">{{text}}</span>
            <span class="remove-button"><i class="fa fa-times" data-hidden-value="{{value}}"></i></span>
        </span>',
    ];
    
    protected $defaults = [
        'name' => '',
        'label' => null,
        'multiple'=>null,
        'options' => null,
        'currentValues' => [],
        'autocomplete'=>'off',
        'data-url' => '',
        'val'=>null,
    ];

    /**
     * Constructor.
     *
     * @param \Cake\View\StringTemplate $templates Templates list.
     */
    public function __construct(StringTemplate $templates)
    {
        $templates->add($this->_templates);
        $this->_templates = $templates;
    }

    /**
     * Methods that render the widget.
     *
     * @param array $data The data to build an input with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     *
     * @return string
     */
    public function render(array $data, ContextInterface $context): string
    {
        $data = array_merge($this->defaults, $data);
        $data = array_merge(['id'=>$this->_domId($data['name'])], $data);
        if ((empty($data['multiple'])) && (substr($data['fieldName'], -4) === '_ids')) {
            $data['multiple'] = true; 
        }
        
        if (!empty($data['data-url'])) {
            $data['data-url'] = Router::url(
                $data['data-url'], 
                true
            );
        }
        
        $data['val'] = (array) $data['val'];
        $data['data-options'] = json_encode($this->formatOptions((array)$data['options']));
        $multiple = in_array($data['multiple'], [1, '1', 'true', 'multiple']);
        $currentValues = (!empty($data['val'])) ? [] : (array) $data['currentValues'];
        foreach (json_decode($data['data-options']) as $op) {
            if (in_array($op->value, $data['val'])) {
                $currentValues[$op->value] = (array) $op;
            }
        }
        unset($data['multiple'], $data['currentValues'], $data['val'], $data['options']);
        //We must rely on a class to manipulate the input later.
        $data = array_merge(['id'=>$this->_domId($data['name'])], $data);
        $classes = preg_split('/\s+/', Hash::get($data, 'class', ''));
        $classes[] = 'sc-autocomplete';
        $classes = array_unique(array_map('trim', $classes));
        sort($classes);
        $data['class'] = implode(' ', $classes);
        unset($classes);
        
        //Input
        $input = $this->_templates->format('input', [
            'type'=>'text',
            'name' => $data['name'],
            'attrs' => $this->_templates->formatAttributes($data, ['name']),
        ]);

        //Current values
        $show = $this->_templates->format('autocompleteShow', [
            'id' => "{$data['id']}-show",
            'content' => implode("\n", $this->getShowItems($currentValues)),
        ]);

        $data['id'] = $data['id'].'-hidden';
        foreach ($data as $key => $value) {
            if (empty(in_array($key, ['id','name']))) {
                unset($data[$key]);
            }
        }
        
        //Hidden
        $data['style'] = 'display:none !important;';
        $select = $multiple ? 'autocompleteSelectMultiple' : 'autocompleteSelect';
        $hidden = $this->_templates->format('hiddenBlock', [
            'content' => $this->_templates->format($select, [
                'name' => $data['name'],
                'content' => implode("\n", $this->getSelectOptions($currentValues)),
                'attrs' => $this->_templates->formatAttributes($data, ['name'])
            ])
        ]);
        
        return  $show.$input.$hidden;
    }

    /**
     * @inheritdoc
     */
    protected function getShowItem(array $option = [])
    {   
        return $this->_templates->format('autocompleteShowItem', [
            'text' => $option['text'],
            'value'=> $option['value'],
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function getShowItems(array $options = []): array
    {   
        foreach ($options as $key => $option) {
            if (!is_array($option)) {
                $option = [
                    'value'=>$option,
                    'text'=>$option,
                ];
            }

            $options[$key] = $this->getShowItem($option);
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    protected function getOption(array $option = [])
    {   
        return $this->_templates->format('option', [
            'value' => $option['value'],
            'text' => $option['text'],
            'attrs' => $this->_templates->formatAttributes($option, ['value','text'])
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function formatOptions(array $options = [])
    {
        foreach ($options as $key => $option) {
            if (!is_array($option)) {
                $option = [
                    'value'=>$key,
                    'text'=>$option,
                ];
            }
            
            if (
                (!isset($option['value'], $option['text'])) && 
                (isset($option[0], $option[1]))
            ) {
                $option = ['value'=> $option[0], 'text'=> $option[1]];
            }
            
            $options[$key] = $option;
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    protected function getSelectOptions(array $options = [])
    {
        foreach ($this->formatOptions($options) as $key => $option) {
            $options[$key] = $this->getOption($option);
        }

        return $options;
    }
    
    /**
     * @inheritdoc
     */
    public function secureFields(array $data): array
    {
        return [$data['name']];
    }
}
