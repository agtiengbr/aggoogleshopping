<div class="panel">
  <div class="panel-heading">
    <i class="icon-sitemap"></i>
    {l s='Mapeamento Google Shopping por categoria' mod='aggoogleshopping'}
    <span class="panel-heading-action">
      <a href="{$aggs_module_config_url|escape:'html':'UTF-8'}" class="btn btn-default">
        <i class="icon-arrow-left"></i> {l s='Voltar à configuração do módulo' mod='aggoogleshopping'}
      </a>
    </span>
  </div>

  <div class="row">
    <div class="col-lg-3">
      <div class="panel aggs-category-tree-panel" id="aggs-category-tree-root">
        <div class="panel-heading">
          {l s='Categorias' mod='aggoogleshopping'}
        </div>
        <div class="panel-body">
          <script type="application/json" id="aggs-category-search-data">{$aggs_category_search_json nofilter}</script>
          <div class="aggs-category-search">
            <input
              type="text"
              class="form-control aggs-category-search-input"
              placeholder="{l s='Buscar categoria…' mod='aggoogleshopping'}"
              autocomplete="off"
            />
            <ul class="aggs-autocomplete-menu aggs-category-search-menu aggs-hidden"></ul>
          </div>
          <div class="aggs-category-tree-toolbar">
            <button type="button" class="btn btn-default btn-xs" data-aggs-action="expand-all">
              {l s='Expandir tudo' mod='aggoogleshopping'}
            </button>
            <button type="button" class="btn btn-default btn-xs" data-aggs-action="collapse-all">
              {l s='Recolher tudo' mod='aggoogleshopping'}
            </button>
          </div>
          <div class="aggs-category-tree-scroll">
            <ul class="aggs-category-tree">
              {include file='module:aggoogleshopping/views/templates/admin/_partials/category_tree.tpl' nodes=$aggs_category_tree}
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-9">
      <form method="post" action="{$aggs_form_action|escape:'html':'UTF-8'}" class="form-horizontal">
        <input type="hidden" name="id_category" value="{$aggs_id_category|intval}" />

        <div class="panel">
          <div class="panel-heading">
            {l s='Configuração para:' mod='aggoogleshopping'}
            <strong>{$aggs_category_name|escape:'html':'UTF-8'}</strong>
          </div>

          <p class="help-block" style="padding: 0 20px;">
            {l s='Prioridade na geração do feed: atributo do produto → feature → valor fixo. Categorias filhas herdam a configuração do pai quando não possuem mapeamento próprio.' mod='aggoogleshopping'}
          </p>

          <div class="table-responsive-row clearfix">
            <table class="table">
              <thead>
                <tr>
                  <th>{l s='Campo Google' mod='aggoogleshopping'}</th>
                  <th>{l s='Grupo de atributo' mod='aggoogleshopping'}</th>
                  <th>{l s='Feature' mod='aggoogleshopping'}</th>
                  <th>{l s='Valor fixo' mod='aggoogleshopping'}</th>
                  <th>{l s='Origem' mod='aggoogleshopping'}</th>
                </tr>
              </thead>
              <tbody>
                {foreach from=$aggs_field_rows item=row}
                  <tr>
                    <td>
                      <strong>{$row.label|escape:'html':'UTF-8'}</strong><br />
                      <code>g:{$row.google_field|escape:'html':'UTF-8'}</code><br />
                      <small class="text-muted">{$row.hint|escape:'html':'UTF-8'}</small>
                    </td>
                    <td>
                      <select name="id_attribute_group_{$row.google_field|escape:'html':'UTF-8'}" class="form-control fixed-width-xl">
                        {foreach from=$aggs_attribute_groups item=option}
                          <option value="{$option.id|escape:'html':'UTF-8'}"
                            {if $row.id_attribute_group == $option.id}selected="selected"{/if}>
                            {$option.name|escape:'html':'UTF-8'}
                          </option>
                        {/foreach}
                      </select>
                    </td>
                    <td>
                      <select name="id_feature_{$row.google_field|escape:'html':'UTF-8'}" class="form-control fixed-width-xl">
                        {foreach from=$aggs_features item=option}
                          <option value="{$option.id|escape:'html':'UTF-8'}"
                            {if $row.id_feature == $option.id}selected="selected"{/if}>
                            {$option.name|escape:'html':'UTF-8'}
                          </option>
                        {/foreach}
                      </select>
                    </td>
                    <td>
                      {include file='module:aggoogleshopping/views/templates/admin/_partials/fixed_value_input.tpl' row=$row}
                    </td>
                    <td>
                      {if $row.has_direct_mapping}
                        <span class="label label-success">{l s='Própria' mod='aggoogleshopping'}</span>
                      {elseif $row.is_inherited}
                        <span class="label label-info">
                          {l s='Herdado de:' mod='aggoogleshopping'} {$row.inherited_from|escape:'html':'UTF-8'}
                        </span>
                      {else}
                        <span class="label label-default">{l s='Sem configuração' mod='aggoogleshopping'}</span>
                      {/if}
                    </td>
                  </tr>
                {/foreach}
              </tbody>
            </table>
          </div>

          <div class="panel-footer">
            <button type="submit" name="submitAgGoogleShoppingCategoryMapping" class="btn btn-primary pull-right">
              <i class="process-icon-save"></i> {l s='Salvar mapeamento' mod='aggoogleshopping'}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
