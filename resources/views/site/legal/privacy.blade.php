<x-legal.page
    title="Política de Privacidade"
    description="Como coletamos, usamos, protegemos e excluímos seus dados pessoais, em conformidade com a LGPD.">

    <x-legal.section title="1. Quem é o responsável">
        <p>
            O controlador dos seus dados pessoais é <strong>{{ config('legal.owner') }}</strong>@if (config('legal.document')) ({{ config('legal.document') }})@endif,
            responsável pela plataforma {{ config('app.name') }}. Contato para assuntos de privacidade:
            <a href="mailto:{{ config('legal.email') }}">{{ config('legal.email') }}</a>.
        </p>
        <p>Esta política segue a Lei Geral de Proteção de Dados (Lei nº 13.709/2018, a LGPD).</p>
    </x-legal.section>

    <x-legal.section title="2. Dados que coletamos">
        <ul>
            <li><strong>Cadastro:</strong> nome e e-mail. A senha é guardada de forma protegida (hash) e nunca fica legível para nós.</li>
            <li><strong>Login com Google:</strong> nome, e-mail verificado e o identificador da sua conta Google. Não guardamos tokens de acesso e não acessamos outros dados da sua conta Google.</li>
            <li><strong>Perfil (opcional):</strong> telefone e foto.</li>
            <li><strong>CPF e telefone na contratação:</strong> pedidos ao assinar um plano ou comprar uma família. O CPF é armazenado de forma criptografada.</li>
            <li><strong>Uso da plataforma:</strong> famílias favoritadas e histórico de downloads (data e endereço IP).</li>
            <li><strong>Segurança:</strong> registros de acesso, com data, endereço IP e navegador, para detectar acessos indevidos.</li>
            <li><strong>Pagamentos:</strong> plano ou família adquirida, valor, forma de pagamento, status e o identificador da transação no Mercado Pago. <strong>Não temos acesso aos dados do seu cartão.</strong></li>
        </ul>
    </x-legal.section>

    <x-legal.section title="3. Para que usamos e em que base legal">
        <ul>
            <li><strong>Criar e manter sua conta, liberar downloads e controlar o limite do plano</strong> (execução do contrato).</li>
            <li><strong>Processar pagamentos e identificar suas cobranças:</strong> o CPF é usado somente para isso (execução do contrato e obrigação legal).</li>
            <li><strong>Telefone:</strong> verificar a sua conta, liberar o acesso ao grupo privado de assinantes e, opcionalmente, oferecer verificação em duas etapas (execução do contrato e legítimo interesse em segurança).</li>
            <li><strong>Prevenir fraudes e abusos</strong>, como compartilhamento de conta ou downloads automatizados (legítimo interesse).</li>
            <li><strong>Enviar e-mails do serviço</strong>, como confirmação de cadastro, troca de e-mail ou aviso de encerramento de conta.</li>
        </ul>
        <p>Não vendemos seus dados e não os usamos para publicidade de terceiros.</p>
    </x-legal.section>

    <x-legal.section title="4. Com quem compartilhamos">
        <ul>
            <li><strong>Mercado Pago:</strong> para processar pagamentos e assinaturas.</li>
            <li><strong>Google:</strong> somente se você escolher entrar com a sua conta Google.</li>
            <li><strong>Provedores de hospedagem e de e-mail:</strong> que armazenam os dados e enviam as mensagens do serviço, sob as nossas instruções.</li>
            <li><strong>Autoridades:</strong> quando houver obrigação legal ou ordem judicial.</li>
        </ul>
    </x-legal.section>

    <x-legal.section title="5. Cookies">
        <p>
            Usamos apenas cookies essenciais: o de sessão (mantém você conectado), o de segurança contra requisições falsas e,
            se você marcar "Lembrar de mim", o de login persistente. No momento, não usamos cookies de publicidade.
        </p>
    </x-legal.section>

    <x-legal.section title="6. Por quanto tempo guardamos">
        <ul>
            <li>Seus dados ficam guardados enquanto a sua conta existir.</li>
            <li>Ao pedir o encerramento, a conta continua ativa por 30 dias. Depois disso, os dados pessoais, favoritos e histórico de downloads são apagados.</li>
            <li>Se houver compras ou assinaturas, os registros financeiros são mantidos sem identificação pessoal, pelo prazo exigido por obrigações legais e contábeis.</li>
            <li>Os registros de acesso deixam de ser associados à sua conta.</li>
        </ul>
    </x-legal.section>

    <x-legal.section title="7. Como protegemos seus dados">
        <p>
            Aplicamos medidas como senhas protegidas por hash, CPF criptografado, links assinados e com prazo para confirmação de e-mail,
            limite de tentativas de login e acesso restrito às áreas administrativas.
            Nenhum sistema é totalmente imune a incidentes; se ocorrer um que ofereça risco relevante a você, comunicaremos você e a autoridade competente, como a lei determina.
        </p>
    </x-legal.section>

    <x-legal.section title="8. Seus direitos">
        <p>Pela LGPD, você pode pedir a qualquer momento:</p>
        <ul>
            <li>confirmação de que tratamos seus dados e acesso a eles;</li>
            <li>correção de dados incompletos ou desatualizados;</li>
            <li>anonimização, bloqueio ou eliminação de dados desnecessários ou tratados em desconformidade;</li>
            <li>portabilidade dos dados;</li>
            <li>informação sobre com quem compartilhamos seus dados;</li>
            <li>revogação de consentimento, quando for essa a base legal.</li>
        </ul>
        <p>
            Você edita nome e telefone em <strong>Minha conta &gt; Perfil</strong> e pode encerrar a conta em <strong>Minha conta &gt; Encerrar conta</strong>.
            Para os demais pedidos, escreva para <a href="mailto:{{ config('legal.email') }}">{{ config('legal.email') }}</a>.
            Você também pode reclamar à Autoridade Nacional de Proteção de Dados (ANPD).
        </p>
    </x-legal.section>

    <x-legal.section title="9. Menores de idade">
        <p>A plataforma é destinada a maiores de 18 anos e não coletamos dados de menores de forma intencional.</p>
    </x-legal.section>

    <x-legal.section title="10. Alterações desta política">
        <p>
            Podemos atualizar esta política. A data da última atualização aparece no topo da página, e mudanças relevantes serão comunicadas.
            Veja também os nossos <a href="{{ route('legal.terms') }}">Termos de Uso</a>.
        </p>
    </x-legal.section>
</x-legal.page>