<x-legal.page
    title="Termos de Uso"
    description="Regras de uso da plataforma, licença das famílias Revit, assinaturas, compras avulsas e direitos do consumidor.">

    <x-legal.section title="1. Sobre estes termos">
        <p>
            Estes Termos de Uso regulam o acesso e a utilização da plataforma <strong>{{ config('app.name') }}</strong>,
            operada por <strong>{{ config('legal.owner') }}</strong>@if (config('legal.document')), inscrito(a) sob o documento {{ config('legal.document') }}@endif,
            com sede em {{ config('legal.city') }}. Ao criar uma conta, assinar um plano ou fazer uma compra, você declara que leu e aceita estas regras.
        </p>
        <p>
            A plataforma oferece uma biblioteca de famílias Revit (.rfa), gratuitas e premium, para uso em projetos de arquitetura e engenharia.
        </p>
    </x-legal.section>

    <x-legal.section title="2. Sua conta">
        <ul>
            <li>Você deve ter 18 anos ou mais e informar dados verdadeiros e atualizados.</li>
            <li>Você pode entrar com e-mail e senha ou com a sua conta Google.</li>
            <li>A conta é pessoal e intransferível. Você é responsável por manter a senha em sigilo e por tudo o que acontece na sua conta.</li>
            <li>Podemos desativar contas que violem estes termos, como em casos de fraude, compartilhamento de acesso ou uso abusivo.</li>
            <li>Você pode pedir o encerramento da conta em <strong>Minha conta &gt; Encerrar conta</strong>. A conta continua ativa por 30 dias, período em que você pode cancelar o pedido. Contas com assinatura em andamento só podem ser encerradas depois do cancelamento da assinatura.</li>
        </ul>
    </x-legal.section>

    <x-legal.section title="3. Famílias Revit e licença de uso">
        <p>
            Ao baixar uma família, você recebe uma licença pessoal, não exclusiva e intransferível para usá-la em seus projetos,
            inclusive projetos feitos para clientes.
        </p>
        <p>É proibido:</p>
        <ul>
            <li>revender, redistribuir, compartilhar ou publicar os arquivos, isolados ou em pacotes, gratuitamente ou não;</li>
            <li>usar os arquivos para criar um serviço concorrente de biblioteca de famílias;</li>
            <li>remover créditos, identificações de marca ou avisos de direitos autorais.</li>
        </ul>
        <p>
            As famílias são fornecidas como material de apoio ao projeto. Embora haja um processo de revisão, não garantimos que
            cada arquivo seja adequado a um projeto, norma ou versão específica do Revit. <strong>A verificação técnica e a
            responsabilidade pelo projeto são do profissional que o utiliza.</strong>
        </p>
    </x-legal.section>

    <x-legal.section title="4. Planos e assinaturas">
        <ul>
            <li>As assinaturas são cobradas de forma recorrente, no ciclo mensal ou anual escolhido, e renovam automaticamente até o cancelamento.</li>
            <li>Alguns planos podem oferecer período de teste grátis. Quando houver, ele será informado antes da contratação.</li>
            <li>Cada plano define os módulos incluídos e um limite mensal de downloads de famílias pagas. Famílias gratuitas, famílias compradas avulsas e baixar de novo, no mesmo mês, uma família já baixada não consomem esse limite. O limite volta no início de cada mês.</li>
            <li>Você pode cancelar a qualquer momento. Depois de cancelar, mantém os benefícios até o fim do período já pago e não há novas cobranças.</li>
            <li>Se uma cobrança não for confirmada, a assinatura fica com pagamento pendente e os benefícios do plano podem ser suspensos até a regularização.</li>
            <li>Podemos alterar preços e planos. Mudanças de preço valem para as próximas renovações e podem ser conferidas na página de planos e de renovação.</li>
        </ul>
    </x-legal.section>

    <x-legal.section title="5. Compras avulsas">
        <p>
            Algumas famílias premium podem ser compradas individualmente, com pagamento único. A família comprada fica liberada
            na sua conta para novos downloads e não usa o limite mensal do plano. Cada família só pode ser comprada uma vez por conta.
        </p>
    </x-legal.section>

    <x-legal.section title="6. Pagamentos">
        <p>
            Os pagamentos são processados pelo <strong>Mercado Pago</strong>, em reais. Os dados do seu cartão são informados
            diretamente no ambiente do Mercado Pago e <strong>não são armazenados por nós</strong>.
            O uso do serviço de pagamento também está sujeito aos termos do Mercado Pago.
        </p>
    </x-legal.section>

    <x-legal.section title="7. Arrependimento e reembolso">
        <p>
            Como a contratação é feita pela internet, você pode desistir da assinatura ou da compra em até <strong>7 dias corridos</strong>
            após a contratação, com devolução dos valores pagos, conforme o art. 49 do Código de Defesa do Consumidor.
            Para isso, escreva para <a href="mailto:{{ config('legal.email') }}">{{ config('legal.email') }}</a> informando o e-mail da conta.
        </p>
        <p>
            Depois desse prazo, o cancelamento da assinatura impede novas cobranças, mas não gera reembolso do período já pago, salvo nos
            casos previstos em lei.
        </p>
    </x-legal.section>

    <x-legal.section title="8. Uso adequado">
        <p>Não é permitido:</p>
        <ul>
            <li>usar robôs, scripts ou qualquer meio automatizado para baixar arquivos ou copiar o conteúdo da plataforma;</li>
            <li>burlar limites de download, cobranças ou controles de acesso;</li>
            <li>compartilhar a conta com outras pessoas;</li>
            <li>tentar acessar áreas restritas, sobrecarregar ou comprometer a segurança do sistema.</li>
        </ul>
    </x-legal.section>

    <x-legal.section title="9. Propriedade intelectual">
        <p>
            O conteúdo, o layout e o código da plataforma pertencem a {{ config('legal.owner') }} ou a seus licenciantes.
            Nomes, logotipos e marcas de fabricantes exibidos nas famílias pertencem aos seus respectivos titulares e são usados
            apenas para identificar os produtos.
        </p>
    </x-legal.section>

    <x-legal.section title="10. Disponibilidade e responsabilidade">
        <p>
            Trabalhamos para manter a plataforma disponível, mas ela pode ficar indisponível por manutenção ou por motivos fora do nosso controle.
            Dentro dos limites da lei, não nos responsabilizamos por danos indiretos decorrentes do uso das famílias em projetos,
            nem por falhas de serviços de terceiros, como provedores de pagamento.
        </p>
    </x-legal.section>

    <x-legal.section title="11. Alterações destes termos">
        <p>
            Podemos atualizar estes termos. A versão em vigor e a data da última atualização aparecem no topo desta página.
            Quando houver mudanças relevantes, pediremos que você aceite a nova versão na próxima contratação.
        </p>
    </x-legal.section>

    <x-legal.section title="12. Contato e foro">
        <p>
            Dúvidas ou solicitações: <a href="mailto:{{ config('legal.email') }}">{{ config('legal.email') }}</a>.
            Para resolver conflitos, fica eleito o foro do domicílio do consumidor, conforme a legislação brasileira.
        </p>
        <p>
            Veja também a nossa <a href="{{ route('legal.privacy') }}">Política de Privacidade</a>.
        </p>
    </x-legal.section>
</x-legal.page>