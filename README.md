# ScrapingPrevent
**Web Application Firewall**

O **ScrapingPrevent** é uma aplicação de firewall para web desenvolvida em PHP, projetada para proteger contra scrapers maliciosos e ataques automatizados. Ele oferece uma série de funcionalidades de segurança, como verificação de IPs, validações de Captcha, limitação de taxa, verificação de User-Agent e muito mais. A aplicação permite configurar essas funcionalidades e aplicar medidas punitivas personalizadas com base em um sistema de pontuação.

## Funcionalidades

- **Verificação de IPs da AWS:** Verifica se o IP de um usuário está na lista de IPs da Amazon Web Services (AWS).
- **Verificação de IPs Blacklisted:** Integra com a API [AbuseIPDB](https://www.abuseipdb.com/) para verificar se o IP de um usuário está listado como malicioso.
- **Validação de Captcha:** Insere Captchas para garantir que o tráfego seja gerado por humanos.
- **Honeypots:** Usa honeypots para capturar bots que tentam acessar áreas protegidas.
- **Rate Limiting (Ratelimiter):** Impõe limites no número de requisições feitas por um IP dentro de um intervalo de tempo.
- **Verificação de Referer:** Verifica o cabeçalho de referer para garantir que as requisições venham de fontes legítimas.
- **Verificação de User-Agent:** Inspeciona o User-Agent das requisições para identificar bots comuns.
- **Sistema de Pontuação:** Atribui pontos a IPs que não cumpram os parâmetros de segurança definidos. O usuário pode personalizar quantos pontos são atribuídos.
- **Sistema de Sanções:** Quando um IP atinge um número configurável de pontos, são aplicadas sanções, como:
  - **Sleep:** Coloca o IP em espera por um tempo configurável.
  - **Erro:** Exibe uma página de erro personalizada.
  - **Bloqueio:** Bloqueia o acesso do IP.
- **Cookie ID:** Quando um usuário acessa o sistema pela primeira vez, um **cookie** é enviado com um **UUID** exclusivo. Se o **cookie** não estiver presente nas requisições subsequentes, a partir da **terceira requisição** sem o cookie, uma sanção é aplicada e a contagem é reiniciada.
- **Redirecionamento de IPs da AWS:** Se um usuário com um IP da AWS ultrapassar o limite de requisições (rate limit) por **duas vezes**, todos os IPs da mesma faixa de IPs (intervalo) serão redirecionados para a mesma **view de erro**. 
  - **Exibição da View de Erro:** Quando os IPs são redirecionados para a view de erro, ao clicar no botão **Voltar à Página Inicial**, apenas o IP que clicou será desbloqueado, enquanto os outros IPs da mesma faixa continuarão redirecionados para a view de erro até que suas condições de sanção sejam cumpridas.

## Instalação

### Pré-requisitos

- **PHP 7.x ou superior**
- **Composer** (gerenciador de dependências para PHP)
- **Servidor Web** (Apache, Nginx, etc.)
- Banco de Dados **MySQL**

### Passos para Instalação

1. Clone o repositório:
    ```bash
    git clone https://github.com/seu-usuario/ScrapingPrevent.git
    cd ScrapingPrevent
    ```

2. Instale as dependências com o **Composer**:
    ```bash
    composer install
    ```

3. Configure o arquivo `db_connection.php` para realizar a conexão com seu banco de dados:
    - Abra o arquivo `db_connection.php` e adicione as informações corretas do seu banco de dados (host, usuário, senha, banco de dados).
    - Exemplo:
      ```php
      <?php
      $host = 'localhost';
      $user = 'root';
      $password = '';
      $dbname = 'scraping_prevent';

      try {
          $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
          $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      } catch (PDOException $e) {
          echo "Connection failed: " . $e->getMessage();
      }
      ?>
      ```

4. Configure as permissões do banco de dados e crie as tabelas necessárias. O projeto pode requerer scripts SQL específicos, ou você pode criar as tabelas manualmente, conforme as necessidades do sistema.

5. Acesse o seu servidor local ou de produção (por exemplo, Apache ou Nginx) e configure o projeto para ser executado no diretório onde o código foi baixado.

## Configuração

### Interface de Configuração

O **ScrapingPrevent** oferece uma interface de administração onde você pode ativar ou desativar as funcionalidades do firewall, como verificação de IPs, honeypots, Captchas e outras.

1. Acesse a página de **Configurações** no painel de administração:
    - Aqui, você pode ativar ou desativar as funcionalidades de segurança de sua preferência.

2. **Configuração de Pontuação:**
    - Você pode configurar a quantidade de pontos que um IP acumula ao violar as regras.
    - Também é possível definir quando aplicar sanções, como o tempo de **sleep**, exibir uma **view de erro** ou **bloquear** o IP.

### Sistema de Cookie ID

- **Primeiro acesso:** Quando o usuário acessa o sistema pela primeira vez, um **cookie** com um UUID exclusivo é enviado ao navegador.
- **Verificação de Cookie:** Para requisições subsequentes, o sistema verifica a presença do cookie. Se o **cookie** não estiver presente, a partir da **terceira requisição** sem o cookie, o sistema aplica uma sanção (por exemplo, **sleep**, erro, ou bloqueio) e reinicia a contagem de requisições.


### Redirecionamento de IPs da AWS

- **Verificação de AWS IP:** Quando um usuário com um IP da AWS ultrapassa o limite de requisições (rate limit) por duas vezes, todos os outros IPs do mesmo intervalo de IPs da AWS serão redirecionados para a **view de erro**.
- **Exibição da View de Erro:**
  - Na view de erro, existe um **botão Voltar à Página Inicial**.
  - Quando o usuário clica neste botão, apenas o IP do usuário que clicou será desbloqueado, permitindo o acesso à página inicial novamente.
  - Os outros IPs da mesma faixa de IPs (mesmo intervalo) continuarão redirecionados para a **view de erro** até que suas condições de sanção sejam cumpridas.
  
### Exemplo de Configuração de Pontuação

- **Pontuação:** Quando um IP atinge 10 pontos, aplica-se uma sanção.
- **Sanção:** Sleep de 30 segundos.

### Visualização de Erro

Você pode configurar uma **view de erro** personalizada para ser exibida quando um IP atingir a pontuação definida.

### Exemplo de Configuração do Sistema de Sanções

- Quando um IP atinge a pontuação configurada, pode ser aplicada uma das seguintes sanções:
  - **Sleep:** O IP ficará em espera por um número de segundos configurável.
  - **Erro:** O IP verá uma página de erro personalizada.
  - **Bloqueio:** O IP será bloqueado permanentemente.

## Contribuição

Contribuições são bem-vindas! Se você deseja ajudar a melhorar o **ScrapingPrevent**, siga estas etapas:

1. Fork este repositório.
2. Crie uma branch para suas modificações (`git checkout -b feature/nova-funcionalidade`).
3. Faça as alterações necessárias e commit suas mudanças (`git commit -am 'Adicionando nova funcionalidade'`).
4. Envie para o repositório remoto (`git push origin feature/nova-funcionalidade`).
5. Abra um pull request.

## Licença

Este projeto está licenciado sob a [MIT License](LICENSE).

## Contact

If you have any questions or suggestions, feel free to reach out:

- **Name:** Tiago Murtinho
- **Email:** tiago_miguelmurtinho@hotmail.com
- **LinkedIn:** [Tiago Murtinho](https://www.linkedin.com/in/tiago-murtinho/)

---

**ScrapingPrevent** foi criado para tornar a web mais segura, prevenindo o tráfego automatizado malicioso e ataques de scraping. Agradecemos pela sua contribuição!
