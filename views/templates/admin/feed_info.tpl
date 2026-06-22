<div class="panel">
  <div class="panel-heading">
    <i class="icon-rss"></i> {l s='Feed Google Shopping' mod='aggoogleshopping'}
  </div>
  <div class="panel-body">
    <p><strong>{l s='URL do feed (copie para o Google Merchant Center):' mod='aggoogleshopping'}</strong></p>
    <div class="form-group">
      <input type="text" readonly="readonly" class="form-control" value="{$aggs_feed_url|escape:'htmlall':'UTF-8'}" onclick="this.select();" />
    </div>

    <p><strong>{l s='URL para atualização agendada (CRON do servidor):' mod='aggoogleshopping'}</strong></p>
    <p class="help-block">
      {l s='Use esta URL em uma tarefa CRON do servidor (cPanel, Plesk, etc.) para regenerar a lista de produtos automaticamente. A mesma chave de segurança do feed protege este endereço.' mod='aggoogleshopping'}
    </p>
    <div class="form-group">
      <input type="text" readonly="readonly" class="form-control" value="{$aggs_cron_url|escape:'htmlall':'UTF-8'}" onclick="this.select();" />
    </div>
    <p class="help-block">
      {l s='Exemplo — uma vez por dia às 3h da manhã:' mod='aggoogleshopping'}<br />
      <code>0 3 * * * curl -fsS "{$aggs_cron_url|escape:'htmlall':'UTF-8'}" &gt; /dev/null</code>
    </p>

    <p>
      {l s='Última geração:' mod='aggoogleshopping'} <strong>{$aggs_last_generated|escape:'htmlall':'UTF-8'}</strong><br />
      {l s='Token (início):' mod='aggoogleshopping'} <code>{$aggs_token_preview|escape:'htmlall':'UTF-8'}</code>
    </p>
    <form method="post" class="form-inline" style="margin-right:8px;display:inline-block;">
      <button type="submit" name="submitAgGoogleShoppingRegenerate" class="btn btn-primary">
        <i class="icon-refresh"></i> {l s='Regenerar feed agora' mod='aggoogleshopping'}
      </button>
    </form>
    <form method="post" class="form-inline" style="display:inline-block;" onsubmit="return confirm(&quot;{l s='Rotacionar token? As URLs antigas deixarão de funcionar.' mod='aggoogleshopping'|escape:'htmlall':'UTF-8'}&quot;);">
      <button type="submit" name="submitAgGoogleShoppingRotateToken" class="btn btn-warning">
        <i class="icon-key"></i> {l s='Rotacionar token' mod='aggoogleshopping'}
      </button>
    </form>
  </div>
</div>
