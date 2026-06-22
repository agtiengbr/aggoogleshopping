{foreach from=$nodes item=node}
  <li
    class="aggs-tree-item{if $node.selected} aggs-tree-item-selected{/if}"
    data-category-id="{$node.id_category|intval}"
    data-category-name="{$node.name|escape:'html':'UTF-8'}"
    data-category-path="{$node.path|escape:'html':'UTF-8'}"
  >
    <div class="aggs-tree-row">
      {if $node.has_children}
        <button
          type="button"
          class="aggs-tree-toggle btn btn-link"
          aria-expanded="{if $node.expanded}true{else}false{/if}"
          title="{l s='Expandir/recolher' mod='aggoogleshopping'}"
        >
          <i class="icon-angle-{if $node.expanded}down{else}right{/if}"></i>
        </button>
      {else}
        <span class="aggs-tree-toggle-spacer" aria-hidden="true"></span>
      {/if}

      {if $node.selected}
        <strong class="aggs-tree-label">{$node.name|escape:'html':'UTF-8'}</strong>
      {else}
        <a href="{$node.url|escape:'html':'UTF-8'}" class="aggs-tree-label">{$node.name|escape:'html':'UTF-8'}</a>
      {/if}
    </div>

    {if $node.has_children}
      <ul class="aggs-tree-children{if !$node.expanded} aggs-tree-collapsed{/if}">
        {include file='module:aggoogleshopping/views/templates/admin/_partials/category_tree.tpl' nodes=$node.children}
      </ul>
    {/if}
  </li>
{/foreach}
