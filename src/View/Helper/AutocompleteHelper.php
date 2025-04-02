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

use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\Helper;
use Cake\View\Helper\FormHelper;
use Cake\View\Helper\HtmlHelper;
use Cake\View\Helper\UrlHelper;
use SilvarCode\Autocomplete\View\Widget\AutocompleteWidget;

class AutocompleteHelper extends Helper
{
    /**
     * @var Helper|HtmlHelper|null
     */
    protected null|Helper|HtmlHelper $Html = null;

    /**
     * @var Helper|FormHelper|null
     */
    protected null|Helper|FormHelper $Form = null;

    /**
     * @var Helper|UrlHelper|null
     */
    protected null|Helper|UrlHelper $Url = null;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected array $defaultConfig = [
        'loadAssets' => true,
        'loadAssetsBlock' => [
            'style' => 'bottomStyle',
            'script' => 'bottomScript',
        ],
    ];

    /**
     * @param array $config
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setConfig(Hash::merge($this->defaultConfig, $config));

        parent::initialize($config);

        $this->Url = $this->getView()->loadHelper('Url');
        $this->Html = $this->getView()->loadHelper('Html');

        if ($this->getConfig('loadAssets')) {
            $this->Html->css('/autocomplete/css/autocomplete.min.css', [
                'block' => $this->getConfig('loadAssetsBlock.style')
            ]);
            $this->Html->script('/autocomplete/js/autocomplete.min.js', [
                'block' => $this->getConfig('loadAssetsBlock.script')
            ]);
        }

        $this->Form = $this->getView()->loadHelper('Form');
        $this->Form->addWidget('autocomplete', [AutocompleteWidget::class]);
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
            'placeholder' => h(__('Type to search ...')),
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
