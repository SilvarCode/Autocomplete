<?php
declare(strict_types=1);

/**
 * ***********************************
 * ||       AutocompleteTrait       ||
 * ***********************************
 * @copyright   2022 SilvarCode / SilvarCode.com
 *              All rights reserved.
 * @link        https://silvarcode.com
 * @since       1.0.0
 * @license     MIT License - see LICENSE.txt for more details.
 *              Redistributions of files must retain the above notice.
 *              https://opensource.org/licenses/mit-license.php MIT License
 */
namespace SilvarCode\Autocomplete\View\Helper;

use Cake\View\Helper;
use Cake\View\View;
use Cake\Utility\Inflector;
use SilvarCode\Autocomplete\View\Widget\AutocompleteWidget;

/**
 * Autocomplete helper
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

    /**
     * 
     */
    public function initialize(array $config): void
	{
		parent::initialize($config);

        $this->Form->addWidget(
            'autocomplete', 
            [
                AutocompleteWidget::class
            ]
        );
        
        $this->Html->css('/autocomplete/autocomplete.css', ['block'=>true]);
        $this->Html->script('/autocomplete/autocomplete.mini.js', ['block'=>'scriptBottom']);
	}

    /**
     * Render form control
     */
    public function autocomplete(string $field, array $options = [])
    {
        $request = $this->getView()->getRequest();
        $fieldArray = explode('.', $field);
        $fieldController = $request->getParam('controller');
        if ((count($fieldArray) > 1) && (!isset($options['data-url']))) {
            $fieldController = Inflector::pluralize(current($fieldArray));
            $fieldController = Inflector::dasherize($fieldController);
        }

        $options = array_merge([
            'label' => false,
            'placeholder'=> __('Type to search ...'),
            'data-url'=> $this->Url->build([
                'prefix'=>null,
                'action'=>'autocomplete',
                'controller'=>$fieldController,
            ]),
        ], array_merge($options, [
            'type'=>'autocomplete',
        ]));
        
        return $this->Form->control($field, $options);
    }
}
