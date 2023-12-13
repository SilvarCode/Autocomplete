<?php
declare(strict_types=1);

/**
 * ***********************************
 * ||       AutocompleteTrait       ||
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
namespace SilvarCode\Autocomplete\View\Helper;

use Cake\Utility\Inflector;
use Cake\View\Helper;
use SilvarCode\Autocomplete\View\Widget\AutocompleteWidget;

/**
 * Autocomplete helper
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\FormHelper $Form
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class AutocompleteHelper extends Helper
{
    /**
     * @var array $helpers
     */
    public $helpers = [
        'Url',
        'Html',
        'Form',
    ];

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->Form->addWidget(
            'autocomplete',
            [
                AutocompleteWidget::class,
            ]
        );

        $this->Html->css('/autocomplete/css/autocomplete.min.css', ['block' => true]);
        $this->Html->script('/autocomplete/js/autocomplete.min.js', ['block' => 'scriptBottom']);
    }

    /**
     * @param array $options
     * @return array
     */
    public function buildOptions(array $options): array
    {
        $niceOptions = [];
        foreach ($options as $key => $option) {
            if (is_string($option) && (is_numeric($key) || (strlen($key) === 16 || strlen($key) === 36))) {
                $niceOptions[] = ['value' => $key, 'text' => $option];
            }
        }

        return $niceOptions;
    }

    /**
     * @param string $field
     * @param array $options
     * @return string
     */
    public function autocomplete(string $field, array $options = []): string
    {
        $request = $this->getView()->getRequest();
        $fieldArray = explode('.', $field);
        $fieldController = $request->getParam('controller');
        if ((count($fieldArray) > 1) && (!isset($options['data-url']))) {
            $fieldController = Inflector::pluralize(current($fieldArray));
            $fieldController = Inflector::dasherize($fieldController);
        } elseif (!isset($options['data-url']) || AutocompleteWidget::hasSuffix($field, '_id')) {
            $fieldController = Inflector::dasherize(
                Inflector::pluralize(
                    rtrim(
                        $field,
                        '_id'
                    )
                )
            );
        }

        $options = array_merge([
            'label' => false,
            'placeholder' => __('Type to search ...'),
            'data-url' => $this->Url->build([
                'prefix' => null,
                'action' => 'autocomplete',
                'controller' => $fieldController,
            ]),
        ], array_merge($options, [
            'type' => 'autocomplete',
        ]));

        return $this->Form->control($field, $options);
    }
}
