<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'aggoogleshopping/classes/AgGoogleShoppingFieldRegistry.php';
require_once _PS_MODULE_DIR_ . 'aggoogleshopping/classes/AgGoogleShoppingCategoryMappingRepository.php';
require_once _PS_MODULE_DIR_ . 'aggoogleshopping/classes/AgGoogleShoppingCategoryMappingResolver.php';
require_once _PS_MODULE_DIR_ . 'aggoogleshopping/classes/AgGoogleShoppingTaxonomyProvider.php';

class AdminAgGoogleShoppingCategoryMappingController extends ModuleAdminController
{
    /** @var AgGoogleShoppingCategoryMappingRepository */
    private $mappingRepository;

    /** @var AgGoogleShoppingTaxonomyProvider */
    private $taxonomyProvider;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->mappingRepository = new AgGoogleShoppingCategoryMappingRepository();
        $this->taxonomyProvider = new AgGoogleShoppingTaxonomyProvider();

        parent::__construct();

        if (Tools::getValue('ajax')) {
            $this->ajax = true;
        }
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS($this->module->getPathUri() . 'views/css/category_mapping.css');
        $this->addJS($this->module->getPathUri() . 'views/js/category_mapping.js');
    }

    public function postProcess()
    {
        if (!Tools::isSubmit('submitAgGoogleShoppingCategoryMapping')) {
            return parent::postProcess();
        }

        $idCategory = (int) Tools::getValue('id_category');
        $idShop = (int) $this->context->shop->id;

        if ($idCategory <= 0) {
            $this->errors[] = $this->t('Categoria inválida.');

            return parent::postProcess();
        }

        foreach (AgGoogleShoppingFieldRegistry::keys() as $googleField) {
            $idAttributeGroup = (int) Tools::getValue('id_attribute_group_' . $googleField);
            $idFeature = (int) Tools::getValue('id_feature_' . $googleField);
            $fixedValue = trim((string) Tools::getValue('fixed_value_' . $googleField));

            if (
                AgGoogleShoppingFieldRegistry::usesGoogleTaxonomy($googleField)
                && $fixedValue !== ''
                && !ctype_digit($fixedValue)
            ) {
                $this->errors[] = $this->t('A categoria Google deve ser um ID numérico válido.');

                return parent::postProcess();
            }

            if (
                AgGoogleShoppingFieldRegistry::usesGoogleTaxonomy($googleField)
                && $fixedValue !== ''
                && !$this->taxonomyProvider->isValidId(
                    (int) $fixedValue,
                    $this->getLanguageIsoCode()
                )
            ) {
                $this->errors[] = $this->t('A categoria Google selecionada não existe na taxonomia.');

                return parent::postProcess();
            }

            if (
                !$this->mappingRepository->saveField(
                    $idCategory,
                    $googleField,
                    $idAttributeGroup,
                    $idFeature,
                    $fixedValue,
                    $idShop
                )
            ) {
                $this->errors[] = sprintf($this->t('Erro ao salvar o campo %s.'), $googleField);

                return parent::postProcess();
            }
        }

        $this->confirmations[] = $this->t('Mapeamento salvo com sucesso.');

        return parent::postProcess();
    }

    public function initContent()
    {
        parent::initContent();

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $idCategory = (int) Tools::getValue(
            'id_category',
            (int) Configuration::get('PS_HOME_CATEGORY')
        );

        $category = new Category($idCategory, $idLang);
        if (!Validate::isLoadedObject($category)) {
            $this->errors[] = $this->t('Categoria inválida.');

            return;
        }

        $mappingResolver = AgGoogleShoppingCategoryMappingResolver::createForShop($idShop);
        $directMappings = $this->mappingRepository->findAllDirectForCategory($idCategory, $idShop);
        $fieldRows = [];
        $languageIso = $this->getLanguageIsoCode();

        foreach (AgGoogleShoppingFieldRegistry::all() as $googleField => $definition) {
            $direct = $directMappings[$googleField] ?? null;
            $effective = $mappingResolver->resolveEffective($idCategory, $googleField);
            $effectiveMapping = $effective['mapping'];
            $sourceCategoryId = (int) $effective['source_category_id'];
            $inherited = $direct === null && $effectiveMapping !== null && $sourceCategoryId !== $idCategory;
            $fixedValue = $direct !== null
                ? (string) $direct['fixed_value']
                : (string) ($effectiveMapping['fixed_value'] ?? '');

            $fieldRows[] = [
                'google_field' => $googleField,
                'label' => $definition['label'],
                'hint' => $definition['hint'],
                'id_attribute_group' => $direct !== null
                    ? (int) $direct['id_attribute_group']
                    : (int) ($effectiveMapping['id_attribute_group'] ?? 0),
                'id_feature' => $direct !== null
                    ? (int) $direct['id_feature']
                    : (int) ($effectiveMapping['id_feature'] ?? 0),
                'fixed_value' => $fixedValue,
                'is_inherited' => $inherited,
                'inherited_from' => $inherited && $sourceCategoryId > 0
                    ? $this->getCategoryName($sourceCategoryId, $idLang)
                    : '',
                'has_direct_mapping' => $direct !== null,
                'use_fixed_value_autocomplete' => AgGoogleShoppingFieldRegistry::hasFixedValueOptions($googleField),
                'use_google_taxonomy_autocomplete' => AgGoogleShoppingFieldRegistry::usesGoogleTaxonomy($googleField),
                'fixed_value_options' => AgGoogleShoppingFieldRegistry::getFixedValueOptions($googleField),
                'fixed_value_label' => $this->resolveFixedValueLabel($googleField, $fixedValue, $languageIso),
            ];
        }

        $categoryTree = $this->buildCategoryTree($idLang, $idCategory);

        $this->context->smarty->assign([
            'aggs_category_tree' => $categoryTree,
            'aggs_category_search_json' => json_encode(
                $this->buildCategorySearchIndex($categoryTree),
                JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT
            ),
            'aggs_id_category' => $idCategory,
            'aggs_category_name' => (string) $category->name,
            'aggs_field_rows' => $fieldRows,
            'aggs_attribute_groups' => $this->getAttributeGroupOptions($idLang),
            'aggs_features' => $this->getFeatureOptions($idLang),
            'aggs_form_action' => $this->context->link->getAdminLink('AdminAgGoogleShoppingCategoryMapping'),
            'aggs_google_taxonomy_ajax_url' => $this->context->link->getAdminLink('AdminAgGoogleShoppingCategoryMapping', true, [], [
                'ajax' => 1,
                'action' => 'searchGoogleTaxonomy',
            ]),
            'aggs_module_config_url' => $this->context->link->getAdminLink('AdminModules', true, [], [
                'configure' => $this->module->name,
            ]),
        ]);

        $this->setTemplate('category_mapping.tpl');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildCategoryTree(int $idLang, int $selectedId): array
    {
        $rootCategory = Category::getRootCategory();
        $nested = Category::getNestedCategories((int) $rootCategory->id, $idLang, true);
        if (!is_array($nested) || $nested === []) {
            return [];
        }

        $rootNode = $nested[(int) $rootCategory->id] ?? reset($nested);
        if (!is_array($rootNode)) {
            return [];
        }

        $formatted = $this->formatCategoryNode($rootNode, $idLang, $selectedId);

        return $formatted !== null ? [$formatted] : [];
    }

    /**
     * @param array<int, array<string, mixed>> $tree
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildCategorySearchIndex(array $tree): array
    {
        $index = [];
        $this->collectCategorySearchIndex($tree, $index);

        return $index;
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @param array<int, array<string, mixed>> $index
     */
    private function collectCategorySearchIndex(array $nodes, array &$index): void
    {
        foreach ($nodes as $node) {
            $index[] = [
                'id_category' => (int) $node['id_category'],
                'name' => (string) $node['name'],
                'path' => (string) $node['path'],
                'url' => (string) $node['url'],
            ];

            if (!empty($node['children']) && is_array($node['children'])) {
                $this->collectCategorySearchIndex($node['children'], $index);
            }
        }
    }

    /**
     * @param array<string, mixed> $category
     *
     * @return array<string, mixed>|null
     */
    private function formatCategoryNode(array $category, int $idLang, int $selectedId, string $parentPath = '')
    {
        $idCategory = (int) ($category['id_category'] ?? 0);
        if ($idCategory <= 0) {
            return null;
        }

        $name = (string) ($category['name'] ?? '');
        $path = $parentPath === '' ? $name : $parentPath . ' > ' . $name;

        $children = [];
        if (!empty($category['children']) && is_array($category['children'])) {
            foreach (array_values($category['children']) as $child) {
                $formattedChild = $this->formatCategoryNode($child, $idLang, $selectedId, $path);
                if ($formattedChild !== null) {
                    $children[] = $formattedChild;
                }
            }
        }

        $selected = $idCategory === $selectedId;
        $expanded = $selected;
        foreach ($children as $child) {
            if (!empty($child['selected']) || !empty($child['expanded'])) {
                $expanded = true;
                break;
            }
        }

        return [
            'id_category' => $idCategory,
            'name' => $name,
            'path' => $path,
            'selected' => $selected,
            'expanded' => $expanded,
            'has_children' => $children !== [],
            'children' => $children,
            'url' => $this->context->link->getAdminLink('AdminAgGoogleShoppingCategoryMapping', true, [], [
                'id_category' => $idCategory,
            ]),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getAttributeGroupOptions(int $idLang): array
    {
        $options = [['id' => '0', 'name' => '—']];
        if (!Combination::isFeatureActive()) {
            return $options;
        }

        $groups = AttributeGroup::getAttributesGroups($idLang);
        if (!is_array($groups)) {
            return $options;
        }

        foreach ($groups as $group) {
            $options[] = [
                'id' => (string) (int) $group['id_attribute_group'],
                'name' => (string) $group['name'],
            ];
        }

        return $options;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getFeatureOptions(int $idLang): array
    {
        $options = [['id' => '0', 'name' => '—']];
        if (!Feature::isFeatureActive()) {
            return $options;
        }

        $features = Feature::getFeatures($idLang);
        if (!is_array($features)) {
            return $options;
        }

        foreach ($features as $feature) {
            $options[] = [
                'id' => (string) (int) $feature['id_feature'],
                'name' => (string) $feature['name'],
            ];
        }

        return $options;
    }

    private function getCategoryName(int $idCategory, int $idLang): string
    {
        $category = new Category($idCategory, $idLang);
        if (!Validate::isLoadedObject($category)) {
            return '';
        }

        return (string) $category->name;
    }

    public function ajaxProcessSearchGoogleTaxonomy()
    {
        $query = trim((string) Tools::getValue('q'));
        $results = $this->taxonomyProvider->search($query, $this->getLanguageIsoCode(), 20);

        $this->ajaxRender(json_encode(['results' => $results], JSON_UNESCAPED_UNICODE));
    }

    private function resolveFixedValueLabel(string $googleField, string $fixedValue, string $languageIso): string
    {
        if (AgGoogleShoppingFieldRegistry::usesGoogleTaxonomy($googleField)) {
            $id = (int) $fixedValue;
            if ($id <= 0) {
                return '';
            }

            $label = $this->taxonomyProvider->getLabelById($id, $languageIso);

            return $label !== '' ? $label : $fixedValue;
        }

        return AgGoogleShoppingFieldRegistry::getFixedValueLabel($googleField, $fixedValue);
    }

    private function getLanguageIsoCode(): string
    {
        $language = $this->context->language;

        return is_object($language) ? (string) $language->iso_code : 'en';
    }

    private function t(string $id, array $parameters = []): string
    {
        return $this->translator->trans($id, $parameters, 'Modules.Aggoogleshopping.Admin');
    }
}
