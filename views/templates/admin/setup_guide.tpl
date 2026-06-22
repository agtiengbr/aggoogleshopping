<div class="panel">
  <div class="panel-heading">
    <i class="icon-book"></i> {l s='Como configurar o feed' mod='aggoogleshopping'}
  </div>
  <div class="panel-body aggs-setup-guide">
    <p class="help-block">
      {l s='Siga os passos abaixo na ordem. As URLs necessárias estão mais abaixo nesta mesma página.' mod='aggoogleshopping'}
    </p>

    <h4>{l s='1. Código do produto (SKU)' mod='aggoogleshopping'}</h4>
    <p>
      {l s='No formulário abaixo, escolha qual código da loja identifica cada produto na lista. Use o mesmo critério configurado no Google Merchant Center (na maioria das lojas: Referência).' mod='aggoogleshopping'}
    </p>
    <p>{l s='Clique em Salvar.' mod='aggoogleshopping'}</p>

    <h4>{l s='2. Gerar a lista pela primeira vez' mod='aggoogleshopping'}</h4>
    <p>
      {l s='Mais abaixo, clique em Regenerar feed agora. Isso cria o arquivo com todos os produtos ativos, preços e fotos atuais.' mod='aggoogleshopping'}
    </p>

    <h4>{l s='3. Enviar a lista para o Google Merchant Center' mod='aggoogleshopping'}</h4>
    <ol>
      <li>{l s='Copie a URL do feed (campo logo abaixo).' mod='aggoogleshopping'}</li>
      <li>{l s='No Google Merchant Center, cadastre o feed de produtos informando essa URL como origem do arquivo.' mod='aggoogleshopping'}</li>
      <li>{l s='Salve e aguarde o Google processar a lista.' mod='aggoogleshopping'}</li>
    </ol>

    <h4>{l s='4. Atualização automática no servidor (CRON)' mod='aggoogleshopping'}</h4>
    <p>
      {l s='A URL do feed apenas exibe a lista já gerada. Para preços e estoques ficarem em dia sem clicar no botão todo dia, configure uma tarefa CRON no servidor (cPanel, Plesk, painel da hospedagem) que acesse a URL de atualização agendada — também nesta página.' mod='aggoogleshopping'}
    </p>
    <ol>
      <li>{l s='Copie a URL para atualização agendada (CRON).' mod='aggoogleshopping'}</li>
      <li>{l s='No painel do servidor, crie uma tarefa CRON com o comando de exemplo exibido abaixo da URL.' mod='aggoogleshopping'}</li>
      <li>{l s='Resposta esperada quando funcionar: OK products=... generated=...' mod='aggoogleshopping'}</li>
    </ol>

    <h4>{l s='5. Campos extras por categoria (opcional, recomendado)' mod='aggoogleshopping'}</h4>
    <p>
      {l s='Para cor, tamanho, gênero e categoria Google, abra o Mapeamento por categoria (painel abaixo) e configure por departamento da loja.' mod='aggoogleshopping'}
    </p>

    <h4>{l s='Manutenção' mod='aggoogleshopping'}</h4>
    <ul>
      <li>{l s='Muitas mudanças de preço ou estoque: Regenerar feed agora.' mod='aggoogleshopping'}</li>
      <li>{l s='Link vazou: Rotacionar token e atualizar as duas URLs (feed no Merchant Center + CRON no servidor).' mod='aggoogleshopping'}</li>
    </ul>

    <p class="help-block" style="margin-bottom:0;">
      {l s='Guia completo (português e inglês):' mod='aggoogleshopping'}
      <a href="https://github.com/agtiengbr/aggoogleshopping/blob/main/README.md" target="_blank" rel="noopener noreferrer">README.md</a>
    </p>
  </div>
</div>
