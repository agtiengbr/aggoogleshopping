# Instruções do projeto — AG Google Shopping

Documento de referência para desenvolvimento, releases e automações deste repositório.

## Onde está o guia de configuração do feed?

| Público | Onde ler |
|---------|----------|
| **Lojista** | Painel da loja → **Módulos** → **AG Google Shopping Feed** → **Configurar** (painel *Como configurar o feed*, no topo da página) |
| **Lojista / equipe** | [README.md](README.md) — guia completo em português e inglês |
| **Servidor / CRON** | Na mesma página do módulo: seção *URL para atualização agendada*, com comando `curl` pronto para copiar |

### URLs do módulo (mesmo token de segurança)

| Função | Controller front |
|--------|-------------------|
| Exibir a lista (Google Merchant Center lê daqui) | `feed` |
| Regenerar a lista (tarefa CRON do servidor) | `cron` |

Não usa o módulo Cronjobs do PrestaShop. A atualização automática é uma tarefa CRON **no servidor** chamando a URL `cron`.

## Regra obrigatória: versões e tags

**Nunca criar tag (`git tag`) nem publicar release sem pedido explícito.**

**Nunca subir versão em `config.xml` / `aggoogleshopping.php` sem pedido explícito.**

**Nunca reverter versão (voltar de 1.1.1 para 1.1.0, etc.) sem pedido explícito.**

Isso vale para humanos no time, assistentes de IA e automações.

Atualizar código sem mudar versão é normal. **Tag, release e bump de versão são ações separadas**, só quando alguém pedir claramente.

## Regra obrigatória: commits Git

- **Nunca** incluir `Co-authored-by: Cursor` nem menção ao Cursor na mensagem de commit.
- **Nunca** indicar que o commit foi feito por IA ou assistente automático.
- Commits devem conter apenas a descrição técnica da mudança.

## Fluxo de release (quando solicitado)

1. Confirmar que `config.xml` e `aggoogleshopping.php` têm a **mesma versão** (ex.: `1.1.0`).
2. Commitar todas as alterações da versão.
3. Criar a tag com prefixo `v` (ex.: `v1.1.0`).
4. Enviar commit e tag para o GitHub:

```bash
git push origin main
git push origin v1.1.0
```

5. O workflow [`.github/workflows/release.yml`](.github/workflows/release.yml) gera o ZIP e publica o GitHub Release.

## Formato do ZIP (PrestaShop)

O pacote deve seguir o padrão do back-office do PrestaShop:

```text
aggoogleshopping-1.1.0.zip
└── aggoogleshopping/
    ├── aggoogleshopping.php
    ├── config.xml
    └── …
```

O CI monta o arquivo a partir do checkout, executando `composer install --no-dev --optimize-autoloader` quando houver `composer.json`, e publica o ZIP já com os arquivos necessários de runtime (incluindo `vendor/` quando aplicável).

## O que não vai no ZIP de release

Definido pelas exclusões do workflow de release:

- `.github/`
- `.gitignore`, `.gitattributes`
- `scripts/install_and_feed.php` (script local de desenvolvimento)

## Checklist antes de pedir uma release

- [ ] Versão atualizada em `config.xml` e `aggoogleshopping.php`
- [ ] `upgrade/upgrade-X.Y.Z.php` criado se houver mudança de schema ou migração
- [ ] README e este arquivo revisados se o fluxo mudou
- [ ] Alterações commitadas
- [ ] Pedido explícito para criar a tag

## Repositório

https://github.com/agtiengbr/aggoogleshopping

Releases: https://github.com/agtiengbr/aggoogleshopping/releases
