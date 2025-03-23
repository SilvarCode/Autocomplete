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
namespace SilvarCode\Autocomplete\Controller;

use Cake\Core\Configure;
use Cake\Http\Response;

trait AutocompleteTrait
{
    /**
     * @property $autocompleteTable
     */
    protected ?string $autocompleteTable = null;

    /**
     * @property array $autocompleteSelectFields
     */
    protected array $autocompleteSelectFields = [];

    /**
     * @property array $autocompleteContain
     */
    protected array $autocompleteContain = [];

    /**
     * @param string $table
     * @return void
     */
    protected function setAutocompleteTable(string $table): void
    {
        $this->autocompleteTable = $table;
    }

    /**
     * @return string
     */
    protected function getAutocompleteTable(): string
    {
        if (empty($this->autocompleteTable) && isset($this->defaultTable)) {
            $this->setAutocompleteTable($this->defaultTable);
        }

        return $this->autocompleteTable;
    }

    /**
     * @param array $fields
     * @return void
     */
    protected function setAutocompleteSelectFields(array $fields = []): void
    {
        $this->autocompleteSelectFields = $fields;
    }

    /**
     * @return array
     */
    protected function getAutocompleteSelectFields(): array
    {
        return $this->autocompleteSelectFields;
    }

    /**
     * @param array $autocompleteContain
     * @return void
     */
    protected function setAutocompleteContain(array $autocompleteContain = []): void
    {
        $this->autocompleteContain = $autocompleteContain;
    }

    /**
     * @return array
     */
    protected function getAutocompleteContain(): array
    {
        return $this->autocompleteContain;
    }

    /**
     * @return void
     */
    public function autocomplete(): void
    {
        $this->disableAutoRender();
        if ($this->request->is(['ajax'])) {
            $this->enableAutoRender();
            $this->viewBuilder()->setLayout('ajax');
            $component = 'Flash';
            if (empty($this->components()->has($component))) {
                $this->loadComponent($component);
            }

            $term = 'term';
            $term = (string)$this->request->getData(
                $term,
                $this->request->getQuery(
                    $term
                )
            );

            $records = [];
            $displayField = null;
            if ($this->request->getAttribute('isAjax') || (Configure::read('debug'))) {
                $this->enableAutoRender();
                $table = $this->fetchTable($this->getAutocompleteTable());
                $alias = $table->getAlias();
                $displayField = $table->getDisplayField();
                $select = $this->getAutocompleteSelectFields();
                $select = !empty($select) ? $select : array_unique([
                    $table->aliasField(
                        $table->getPrimaryKey()
                    ),
                    $table->aliasField(
                        $table->getDisplayField()
                    ),
                ]);

                if (method_exists($this, 'getAutocompleteConditions')) {
                    $conditions = $this->getAutocompleteConditions();
                } else {
                    $conditions = [
                        "{$alias}.{$displayField} LIKE" => '%' . $term . '%',
                    ];

                    if ($table->hasField('active')) {
                        $conditions["{$alias}.active"] = 1;
                    } elseif ($table->hasField('published')) {
                        $conditions["{$alias}.published"] = 1;
                    }
                }

                $records = $table->find()
                    ->select($select)
                    ->contain($this->getAutocompleteContain())
                    ->where($conditions)
                    ->order([$table->aliasField($displayField) => 'ASC'])
                    ->limit(10)
                    ->all();
            }

            $this->set(compact('records', 'displayField'));
            $this->response = $this->response->withCache('-1 minute', '+5 minutes');
            $this->response = $this->response->withType('application/json');

            try {
                $this->render();
            } catch (\Exception $e) {
                $viewBuilder = $this->viewBuilder();
                $viewBuilder->setLayout('ajax');
                $viewBuilder->setPlugin('SilvarCode/Autocomplete');
                $viewBuilder->setTemplate('autocomplete');
                $viewBuilder->setTemplatePath('element');
                $this->render();
            }
        }
    }
}
