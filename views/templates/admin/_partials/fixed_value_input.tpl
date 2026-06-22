{if $row.use_google_taxonomy_autocomplete}
  <div
    class="aggs-autocomplete aggs-taxonomy-autocomplete"
    data-aggs-taxonomy-autocomplete="1"
    data-field="{$row.google_field|escape:'html':'UTF-8'}"
    data-ajax-url="{$aggs_google_taxonomy_ajax_url|escape:'html':'UTF-8'}"
  >
    <input
      type="text"
      class="form-control fixed-width-xxl aggs-autocomplete-display"
      value="{$row.fixed_value_label|escape:'html':'UTF-8'}"
      placeholder="{l s='Buscar categoria Google…' mod='aggoogleshopping'}"
      autocomplete="off"
    />
    <input
      type="hidden"
      name="fixed_value_{$row.google_field|escape:'html':'UTF-8'}"
      value="{$row.fixed_value|escape:'html':'UTF-8'}"
      class="aggs-autocomplete-value"
    />
    <ul class="aggs-autocomplete-menu aggs-hidden"></ul>
  </div>
{elseif $row.use_fixed_value_autocomplete}
  <div
    class="aggs-autocomplete"
    data-aggs-autocomplete="1"
    data-field="{$row.google_field|escape:'html':'UTF-8'}"
  >
    <input
      type="text"
      class="form-control fixed-width-xxl aggs-autocomplete-display"
      value="{$row.fixed_value_label|escape:'html':'UTF-8'}"
      placeholder="{l s='Digite para filtrar…' mod='aggoogleshopping'}"
      autocomplete="off"
    />
    <input
      type="hidden"
      name="fixed_value_{$row.google_field|escape:'html':'UTF-8'}"
      value="{$row.fixed_value|escape:'html':'UTF-8'}"
      class="aggs-autocomplete-value"
    />
    <ul class="aggs-autocomplete-menu aggs-hidden">
      {foreach from=$row.fixed_value_options item=option}
        {if $option.value != ''}
          <li
            class="aggs-autocomplete-item"
            data-value="{$option.value|escape:'html':'UTF-8'}"
            data-label="{$option.label|escape:'html':'UTF-8'}"
          >
            {$option.label|escape:'html':'UTF-8'}
          </li>
        {/if}
      {/foreach}
    </ul>
  </div>
{else}
  <input
    type="text"
    name="fixed_value_{$row.google_field|escape:'html':'UTF-8'}"
    value="{$row.fixed_value|escape:'html':'UTF-8'}"
    class="form-control fixed-width-xxl"
  />
{/if}
