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
    <div class="col-lg-12">
      <form method="get" action="{$aggs_form_action|escape:'html':'UTF-8'}" class="form-horizontal" style="margin-bottom: 15px;">
        <div class="form-group">
          <label class="control-label col-lg-3">{l s='Categoria' mod='aggoogleshopping'}</label>
          <div class="col-lg-6">
            <select name="id_category" class="form-control" onchange="this.form.submit();">
              {foreach from=$aggs_category_options item=option}
                <option value="{$option.id_category|intval}"{if $option.selected} selected="selected"{/if}>
                  {$option.name|escape:'html':'UTF-8'}
                </option>
              {/foreach}
            </select>
          </div>
        </div>
      </form>

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
                      {if $row.use_fixed_value_autocomplete}
                        <select name="fixed_value_{$row.google_field|escape:'html':'UTF-8'}" class="form-control fixed-width-xxl">
                          <option value="">{l s='—' mod='aggoogleshopping'}</option>
                          {foreach from=$row.fixed_value_options item=option}
                            {if $option.value != ''}
                              <option value="{$option.value|escape:'html':'UTF-8'}"
                                {if $row.fixed_value == $option.value}selected="selected"{/if}>
                                {$option.label|escape:'html':'UTF-8'}
                              </option>
                            {/if}
                          {/foreach}
                        </select>
                      {else}
                        <input
                          type="text"
                          name="fixed_value_{$row.google_field|escape:'html':'UTF-8'}"
                          value="{$row.fixed_value|escape:'html':'UTF-8'}"
                          class="form-control fixed-width-xxl"
                          {if $row.use_google_taxonomy_autocomplete}placeholder="{l s='ID numérico da categoria Google' mod='aggoogleshopping'}"{/if}
                        />
                      {/if}
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
