<?php

namespace Database\Seeders;

use App\Enums\ServiceArea;
use App\Models\Service;
use App\Services\ImageService;
use App\Support\ShowcaseMenu;
use Illuminate\Database\Seeder;

/**
 * Initial services of the showcase, with sales copy written for SEO.
 *
 * Only creates the services that don't exist yet (matched by slug), so it
 * never overwrites texts edited in the admin. To restore one, delete it in
 * the admin and run the seeder again (or use "Criar serviços padrão" on the
 * services list, which runs this same seeder; there is no terminal on the
 * shared hosting).
 *
 * Covers live in database/seeders/assets/services/{slug}.jpg and go through
 * ImageService like any upload (webp + thumbnail).
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $images = app(ImageService::class);

        foreach (self::services() as $position => $data) {
            if (Service::query()->where('slug', $data['slug'])->exists()) {
                continue;
            }

            $service = Service::create([
                ...$data,
                'sort_order'  => $position + 1,
                'is_active'   => true,
                'is_featured' => true,
            ]);

            $cover = database_path('seeders/assets/services/' . $data['slug'] . '.jpg');

            if (is_file($cover)) {
                $service->update([
                    'cover_path' => $images->storeWithSlug(
                        slug: $service->slug,
                        file: $cover,
                        directory: 'services',
                        disk: 'public',
                    ),
                ]);
            }
        }

        // Model events may be muted when called from another seeder.
        ShowcaseMenu::forget();
    }

    /**
     * Slugs of the default services that are not in the database.
     *
     * @return array<int, string>
     */
    public static function missing(): array
    {
        $slugs = array_column(self::services(), 'slug');

        return array_values(array_diff($slugs, Service::query()->whereIn('slug', $slugs)->pluck('slug')->all()));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function services(): array
    {
        return [
            ...self::engineering(),
            ...self::technology(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function engineering(): array
    {
        return [
            [
                'area'            => ServiceArea::Engineering,
                'name'            => 'Projeto Estrutural',
                'slug'            => 'projeto-estrutural',
                'icon'            => 'building-office-2',
                'headline'        => 'Estrutura segura e econômica, calculada e detalhada para a sua obra sair do papel sem improviso.',
                'summary'         => 'Cálculo e detalhamento de estruturas em concreto armado para casas, sobrados, edifícios e comércios, conforme a NBR 6118, com ART e projeto pronto para executar.',
                'seo_title'       => 'Projeto Estrutural Residencial e Comercial com ART',
                'seo_description' => 'Projeto estrutural em concreto armado conforme a NBR 6118, com ART e detalhamento completo. Estrutura segura e sem desperdício. Atendimento em todo o Brasil.',
                'body'            => <<<'HTML'
<h2>O que é o projeto estrutural</h2>
<p>É o projeto que define as <strong>fundações, pilares, vigas e lajes</strong> da sua edificação: as dimensões de cada peça, a quantidade e a posição do aço e como tudo deve ser executado. É ele que garante que a estrutura suporte, com segurança, todas as cargas da obra durante toda a vida útil.</p>
<h2>Por que contratar um projeto estrutural</h2>
<ul>
<li><strong>Segurança de verdade:</strong> cada peça é dimensionada conforme a ABNT NBR 6118, sem "achismo" no canteiro de obras.</li>
<li><strong>Economia de concreto e aço:</strong> peças na medida certa evitam o excesso de material, que é um dos maiores custos de uma obra.</li>
<li><strong>Obra sem improviso:</strong> o mestre de obras recebe plantas claras, com cada barra de aço detalhada, e não precisa adivinhar nada.</li>
<li><strong>Menos risco de trincas e patologias:</strong> estrutura bem dimensionada é a melhor prevenção contra fissuras, flechas e recalques.</li>
<li><strong>Documentação em dia:</strong> projeto com ART, exigido por prefeituras, bancos e financiamentos imobiliários.</li>
</ul>
<h2>Para quem é</h2>
<p>Para quem vai <strong>construir ou ampliar</strong> uma casa, sobrado, edifício, galpão ou ponto comercial, para quem quer fazer um segundo pavimento sobre uma construção existente e para quem precisa de laudo ou projeto para regularizar a obra.</p>
<h2>Projeto em BIM, compatibilizado com os outros projetos</h2>
<p>A estrutura é calculada em software profissional e modelada em <strong>BIM</strong>. Assim ela é compatibilizada com os projetos arquitetônico, elétrico e hidrossanitário antes da obra começar, evitando viga atravessando janela, tubulação furando pilar e retrabalho no canteiro.</p>
HTML,
                'deliverables'    => [
                    'Planta de locação e cargas das fundações',
                    'Plantas de formas de todos os pavimentos',
                    'Detalhamento de armaduras de fundações, pilares, vigas e lajes',
                    'Quantitativo de concreto, aço e formas',
                    'Memorial descritivo',
                    'Arquivos em PDF e DWG',
                    'ART registrada no CREA',
                ],
                'faq'             => [
                    ['question' => 'Quanto custa um projeto estrutural?', 'answer' => 'O valor depende da área construída, do número de pavimentos e da complexidade da estrutura. Envie as medidas ou o projeto arquitetônico e receba uma proposta sem compromisso.'],
                    ['question' => 'Preciso ter o projeto arquitetônico antes?', 'answer' => 'Sim, a estrutura é lançada sobre o projeto arquitetônico. Se você ainda não tem, também fazemos o arquitetônico, já pensando na estrutura desde o início.'],
                    ['question' => 'O projeto estrutural ajuda a economizar na obra?', 'answer' => 'Sim. Com as peças dimensionadas na medida certa você compra só o concreto e o aço necessários, e o quantitativo do projeto ajuda a orçar a obra com precisão.'],
                    ['question' => 'O projeto vem com ART?', 'answer' => 'Sim. Todo projeto é entregue com ART (Anotação de Responsabilidade Técnica) registrada no CREA, assinada pelo engenheiro civil responsável.'],
                    ['question' => 'Vocês atendem obras em outras cidades e estados?', 'answer' => 'Sim, o atendimento é online. Para obras fora de Mato Grosso, as exigências do CREA do estado da obra são verificadas e informadas na proposta.'],
                ],
            ],
            [
                'area'            => ServiceArea::Engineering,
                'name'            => 'Projeto Elétrico',
                'slug'            => 'projeto-eletrico',
                'icon'            => 'bolt',
                'headline'        => 'Instalação elétrica segura, sem disjuntor desarmando e pronta para o ar-condicionado, o chuveiro e a energia solar.',
                'summary'         => 'Projeto elétrico residencial e predial em baixa tensão conforme a NBR 5410: pontos, circuitos, quadro de cargas e diagramas, com ART e pronto para a concessionária.',
                'seo_title'       => 'Projeto Elétrico Residencial e Predial com ART | NBR 5410',
                'seo_description' => 'Projeto elétrico em baixa tensão conforme a NBR 5410: circuitos, quadro de cargas e diagramas, com ART e documentação para a concessionária. Peça seu orçamento.',
                'body'            => <<<'HTML'
<h2>O que é o projeto elétrico</h2>
<p>É o projeto que define <strong>onde ficam as tomadas, interruptores e pontos de iluminação</strong>, como os circuitos são divididos e quais cabos, disjuntores e dispositivos de proteção cada um precisa. Tudo dimensionado para a instalação funcionar com segurança e sem sobrecarga.</p>
<h2>Por que contratar um projeto elétrico</h2>
<ul>
<li><strong>Segurança contra choque e incêndio:</strong> cabos e proteções dimensionados conforme a ABNT NBR 5410, com DR e aterramento onde a norma exige.</li>
<li><strong>Fim do disjuntor desarmando:</strong> cada equipamento pesado, como chuveiro, ar-condicionado e forno, ganha o circuito certo.</li>
<li><strong>Tomada no lugar certo:</strong> os pontos são planejados junto com o layout dos móveis, sem extensão espalhada pela casa.</li>
<li><strong>Preparado para o futuro:</strong> previsão de carga para ar-condicionado, energia solar e carregador de carro elétrico.</li>
<li><strong>Ligação sem dor de cabeça:</strong> documentação para o pedido de ligação ou de aumento de carga na concessionária, quando exigida.</li>
</ul>
<h2>Para quem é</h2>
<p>Para <strong>casas, sobrados, apartamentos, salas comerciais e pequenos prédios</strong> em construção, e para quem vai reformar ou precisa aumentar a carga de uma instalação antiga que já não dá conta.</p>
<h2>Compatibilizado com a estrutura e o hidrossanitário</h2>
<p>Como o projeto é feito em <strong>BIM</strong>, os eletrodutos e quadros são posicionados conhecendo vigas, pilares e tubulações, o que evita quebra-quebra e emendas improvisadas na obra.</p>
HTML,
                'deliverables'    => [
                    'Planta baixa com tomadas, interruptores e pontos de iluminação',
                    'Divisão e dimensionamento dos circuitos',
                    'Quadro de cargas e cálculo da demanda',
                    'Diagramas unifilares dos quadros de distribuição',
                    'Dimensionamento do padrão de entrada',
                    'Lista de materiais',
                    'Memorial descritivo',
                    'ART registrada no CREA',
                ],
                'faq'             => [
                    ['question' => 'Preciso de projeto elétrico para construir uma casa?', 'answer' => 'Sim. Além de ser a forma segura de executar a instalação, o projeto costuma ser exigido pela prefeitura, pela concessionária de energia e pelos bancos em financiamentos.'],
                    ['question' => 'O projeto já prevê ar-condicionado e energia solar?', 'answer' => 'Sim. Basta informar o que você pretende instalar, agora ou no futuro, e os circuitos e a carga já são dimensionados para isso.'],
                    ['question' => 'O projeto serve para a concessionária de energia?', 'answer' => 'Sim, a documentação é preparada para o pedido de ligação ou de aumento de carga, de acordo com as exigências da concessionária da sua região.'],
                    ['question' => 'Vocês fazem projeto elétrico para reforma?', 'answer' => 'Sim. Avaliamos a instalação existente e projetamos a adequação, seja para trocar a fiação, aumentar a carga ou incluir novos equipamentos.'],
                    ['question' => 'Quanto custa um projeto elétrico?', 'answer' => 'O valor depende da área, do número de pontos e do tipo de edificação. Envie a planta ou as medidas e receba uma proposta sem compromisso.'],
                ],
            ],
            [
                'area'            => ServiceArea::Engineering,
                'name'            => 'Projeto Arquitetônico',
                'slug'            => 'projeto-arquitetonico',
                'icon'            => 'home-modern',
                'headline'        => 'Veja sua casa pronta em 3D antes de colocar o primeiro tijolo e construa com projeto pronto para a prefeitura.',
                'summary'         => 'Projeto arquitetônico de casas, sobrados e comércios em BIM, com plantas, cortes, fachadas, imagens 3D e projeto legal para aprovação na prefeitura.',
                'seo_title'       => 'Projeto Arquitetônico de Casas e Sobrados em 3D | BIM',
                'seo_description' => 'Projeto arquitetônico em BIM com planta, cortes, fachadas e imagens 3D, pronto para aprovação na prefeitura. Casas, sobrados e comércios em todo o Brasil.',
                'body'            => <<<'HTML'
<h2>O que é o projeto arquitetônico</h2>
<p>É o projeto que transforma a sua ideia em uma edificação real: <strong>distribuição dos ambientes, medidas, aberturas, telhado e fachada</strong>, respeitando o terreno, o seu orçamento e as regras da prefeitura. É a base de todos os outros projetos da obra.</p>
<h2>Por que contratar um projeto arquitetônico</h2>
<ul>
<li><strong>Você vê antes de construir:</strong> imagens 3D mostram a casa pronta, e as mudanças acontecem no projeto, não na obra.</li>
<li><strong>Melhor aproveitamento do terreno:</strong> ambientes bem distribuídos, com ventilação, iluminação natural e circulação pensadas.</li>
<li><strong>Aprovação na prefeitura:</strong> projeto legal de acordo com o código de obras e o plano diretor da sua cidade.</li>
<li><strong>Orçamento mais preciso:</strong> com plantas e quadro de áreas, fica fácil cotar material e mão de obra.</li>
<li><strong>Projeto feito por engenheiro:</strong> a arquitetura já nasce pensando na estrutura e nas instalações, sem soluções impossíveis de construir.</li>
</ul>
<h2>Para quem é</h2>
<p>Para quem vai <strong>construir do zero</strong> uma casa, sobrado ou ponto comercial, para quem vai <strong>reformar ou ampliar</strong> e para quem precisa regularizar uma construção na prefeitura.</p>
<h2>Modelado em BIM</h2>
<p>O projeto é desenvolvido em <strong>BIM no Revit</strong>: plantas, cortes, fachadas e imagens 3D saem do mesmo modelo, sempre coerentes entre si, e servem de base direta para os projetos estrutural, elétrico e hidrossanitário.</p>
HTML,
                'deliverables'    => [
                    'Estudo preliminar com a distribuição dos ambientes',
                    'Plantas baixas cotadas',
                    'Planta de situação, locação e cobertura',
                    'Cortes e fachadas',
                    'Imagens 3D do projeto',
                    'Quadro de áreas',
                    'Projeto legal para aprovação na prefeitura',
                    'ART registrada no CREA',
                ],
                'faq'             => [
                    ['question' => 'Posso pedir alterações no projeto?', 'answer' => 'Sim. O projeto começa por um estudo preliminar justamente para você opinar e ajustar antes de detalhar. O número de revisões fica definido na proposta.'],
                    ['question' => 'O projeto serve para aprovar na prefeitura?', 'answer' => 'Sim. É entregue o projeto legal de acordo com as exigências do município, pronto para você dar entrada no alvará de construção.'],
                    ['question' => 'Vocês fazem projeto de reforma e ampliação?', 'answer' => 'Sim. Levantamos o que já existe e projetamos a reforma ou a ampliação, inclusive a construção de um segundo pavimento.'],
                    ['question' => 'Posso fazer também os projetos complementares?', 'answer' => 'Sim. Os projetos estrutural, elétrico e hidrossanitário podem ser feitos juntos, já compatibilizados com o arquitetônico, o que economiza tempo e evita conflitos na obra.'],
                    ['question' => 'Quanto custa um projeto arquitetônico?', 'answer' => 'O valor depende da área, do padrão da construção e do que está incluso, como imagens 3D e aprovação. Conte sua ideia e receba uma proposta sem compromisso.'],
                ],
            ],
            [
                'area'            => ServiceArea::Engineering,
                'name'            => 'Projeto Hidrossanitário',
                'slug'            => 'projeto-hidrossanitario',
                'icon'            => 'beaker',
                'headline'        => 'Água com boa pressão, esgoto sem mau cheiro e chuva bem escoada, sem quebrar parede depois.',
                'summary'         => 'Projeto hidrossanitário completo de água fria e quente, esgoto e águas pluviais, conforme as normas NBR 5626, NBR 8160 e NBR 10844, com ART.',
                'seo_title'       => 'Projeto Hidrossanitário: Água, Esgoto e Águas Pluviais',
                'seo_description' => 'Projeto hidrossanitário com ART: água fria e quente, esgoto e águas pluviais conforme as normas da ABNT. Sem vazamento e sem mau cheiro. Peça orçamento.',
                'body'            => <<<'HTML'
<h2>O que é o projeto hidrossanitário</h2>
<p>É o projeto das instalações de <strong>água fria e quente, esgoto e águas pluviais</strong>: o caminho de cada tubulação, os diâmetros, as caixas, o reservatório e as calhas. Ele garante água chegando com pressão em todos os pontos e esgoto saindo da edificação sem problemas.</p>
<h2>Por que contratar um projeto hidrossanitário</h2>
<ul>
<li><strong>Pressão certa em todos os pontos:</strong> chuveiro e torneiras funcionando bem, inclusive no pavimento de cima.</li>
<li><strong>Sem mau cheiro:</strong> ventilação e sifonamento do esgoto conforme a ABNT NBR 8160.</li>
<li><strong>Menos risco de vazamento e infiltração:</strong> tubulação dimensionada e bem traçada, sem emendas improvisadas.</li>
<li><strong>Chuva bem escoada:</strong> calhas e condutores dimensionados conforme a ABNT NBR 10844, evitando transbordo e umidade.</li>
<li><strong>Manutenção fácil:</strong> você sabe exatamente por onde passa cada cano, o que evita quebrar a parede errada no futuro.</li>
</ul>
<h2>Para quem é</h2>
<p>Para quem vai construir <strong>casas, sobrados, edifícios e comércios</strong>, para reformas de banheiro e cozinha e para quem precisa do projeto para aprovação ou financiamento.</p>
<h2>Normas e compatibilização</h2>
<p>O projeto segue a ABNT NBR 5626 (água fria e quente), a NBR 8160 (esgoto sanitário) e a NBR 10844 (águas pluviais). Feito em <strong>BIM</strong>, é compatibilizado com a estrutura para que nenhuma tubulação atravesse viga ou pilar sem previsão.</p>
HTML,
                'deliverables'    => [
                    'Plantas de água fria e água quente',
                    'Isométricos das instalações de água',
                    'Plantas de esgoto sanitário com ventilação',
                    'Plantas de águas pluviais com calhas e condutores',
                    'Dimensionamento do reservatório',
                    'Detalhes de caixas e ligações',
                    'Lista de materiais',
                    'ART registrada no CREA',
                ],
                'faq'             => [
                    ['question' => 'O projeto inclui água quente?', 'answer' => 'Sim. Água fria e água quente são projetadas juntas, para aquecedor a gás, solar ou elétrico, conforme o sistema que você escolher.'],
                    ['question' => 'O projeto resolve problema de pouca pressão no chuveiro?', 'answer' => 'Na obra nova, a pressão é garantida no cálculo. Em reformas, avaliamos a instalação e indicamos a solução, como mudança de reservatório ou pressurizador.'],
                    ['question' => 'Vocês dimensionam a caixa d’água?', 'answer' => 'Sim. O reservatório é dimensionado pelo consumo previsto da edificação, e a estrutura que vai sustentá-lo pode ser calculada no projeto estrutural.'],
                    ['question' => 'Fazem projeto de fossa e sumidouro?', 'answer' => 'Onde não há rede de esgoto, o tratamento individual pode ser incluído no projeto. Informe na solicitação de orçamento.'],
                    ['question' => 'Quanto custa um projeto hidrossanitário?', 'answer' => 'O valor depende da área, do número de banheiros e pontos de água e do tipo de edificação. Envie a planta e receba uma proposta sem compromisso.'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function technology(): array
    {
        return [
            [
                'area'            => ServiceArea::Technology,
                'name'            => 'Desenvolvimento de Sites',
                'slug'            => 'desenvolvimento-de-sites',
                'icon'            => 'globe-alt',
                'headline'        => 'Um site rápido, bonito e feito para aparecer no Google e transformar visitas em clientes.',
                'summary'         => 'Criação de sites profissionais sob medida: rápidos, seguros, adaptados ao celular e otimizados para o Google, com painel para você atualizar o conteúdo.',
                'seo_title'       => 'Desenvolvimento de Sites Profissionais e Otimizados para SEO',
                'seo_description' => 'Criação de sites profissionais sob medida: rápidos, seguros, adaptados ao celular e otimizados para o Google, com painel para editar. Peça seu orçamento.',
                'body'            => <<<'HTML'
<h2>Seu site trabalhando por você 24 horas</h2>
<p>O site é o primeiro contato de muitos clientes com a sua empresa. Um site profissional <strong>passa credibilidade, aparece nas buscas do Google e leva o visitante direto para o WhatsApp ou o orçamento</strong>. Um site lento ou desatualizado faz o contrário: manda o cliente para o concorrente.</p>
<h2>O que um site sob medida entrega</h2>
<ul>
<li><strong>Velocidade:</strong> páginas leves que abrem rápido, inclusive no 4G, o que também ajuda no Google.</li>
<li><strong>SEO técnico desde o início:</strong> URLs amigáveis, sitemap, dados estruturados e meta tags para cada página.</li>
<li><strong>Feito para o celular:</strong> layout pensado primeiro para a tela pequena, onde está a maior parte dos acessos.</li>
<li><strong>Painel administrativo:</strong> você mesmo atualiza textos, fotos, produtos e serviços, sem depender de ninguém.</li>
<li><strong>Segurança:</strong> HTTPS, proteção contra spam nos formulários e código atualizado.</li>
<li><strong>Integrações:</strong> WhatsApp, formulários, Google Analytics, Search Console e pagamentos online.</li>
</ul>
<h2>Tipos de site</h2>
<p><strong>Site institucional</strong> para empresas e profissionais, <strong>landing page</strong> para campanhas, <strong>catálogo</strong> de produtos e serviços, <strong>loja virtual</strong> e <strong>portais com área de membros</strong>, assinaturas e pagamentos.</p>
<h2>Tecnologia profissional</h2>
<p>Os sites são desenvolvidos com <strong>Laravel e Tailwind CSS</strong>, tecnologias usadas por empresas no mundo todo, e o código é seu. Nada de ficar preso a uma plataforma pronta e às limitações dela.</p>
HTML,
                'deliverables'    => [
                    'Layout exclusivo, adaptado a celular, tablet e computador',
                    'Painel administrativo para editar o conteúdo',
                    'SEO técnico: sitemap, dados estruturados e URLs amigáveis',
                    'Formulários de contato e botão de WhatsApp',
                    'Integração com Google Analytics e Search Console',
                    'Publicação, domínio e certificado de segurança (HTTPS)',
                    'Treinamento para usar o painel',
                    'Suporte após a entrega',
                ],
                'faq'             => [
                    ['question' => 'Quanto tempo leva para o site ficar pronto?', 'answer' => 'Depende do tamanho do site e das funcionalidades. Um site institucional fica pronto bem mais rápido que um portal com pagamentos. O prazo exato vem na proposta.'],
                    ['question' => 'Vou conseguir atualizar o site sozinho?', 'answer' => 'Sim. Todo site vem com um painel administrativo simples para você editar textos, fotos e conteúdos, e você recebe um treinamento para usá-lo.'],
                    ['question' => 'Meu site vai aparecer no Google?', 'answer' => 'O site é entregue tecnicamente otimizado e cadastrado no Google. A posição nas buscas depende também do conteúdo e da concorrência do seu mercado, e orientamos como melhorar com o tempo.'],
                    ['question' => 'Preciso pagar hospedagem e domínio?', 'answer' => 'Sim, domínio e hospedagem ficam no seu nome e são pagos diretamente ao fornecedor. Ajudamos a escolher e configuramos tudo.'],
                    ['question' => 'Vocês fazem loja virtual?', 'answer' => 'Sim. Lojas virtuais com catálogo, carrinho e pagamento online por cartão e Pix, com painel para gerenciar os pedidos.'],
                ],
            ],
            [
                'area'            => ServiceArea::Technology,
                'name'            => 'Desenvolvimento de Aplicativos',
                'slug'            => 'desenvolvimento-de-aplicativos',
                'icon'            => 'device-phone-mobile',
                'headline'        => 'Seu aplicativo para Android e iPhone, com um só projeto e publicado nas lojas.',
                'summary'         => 'Criação de aplicativos sob medida para Android e iOS com Flutter, integrados ao seu sistema, com painel de gestão e publicação na Google Play e na App Store.',
                'seo_title'       => 'Desenvolvimento de Aplicativos Android e iOS sob Medida',
                'seo_description' => 'Criação de aplicativos para Android e iPhone com Flutter: um só código, publicação nas lojas, integração com seu sistema e painel de gestão. Peça um orçamento.',
                'body'            => <<<'HTML'
<h2>Um aplicativo feito para o seu negócio</h2>
<p>Um aplicativo coloca a sua empresa <strong>na tela inicial do celular do cliente</strong>: pedidos, agendamentos, área do cliente, acompanhamento de serviços e notificações que trazem o usuário de volta. Tudo pensado para o seu processo, não um modelo genérico.</p>
<h2>Por que desenvolver com Flutter</h2>
<ul>
<li><strong>Android e iPhone de uma vez:</strong> um só código gera os dois aplicativos, com menos custo e prazo do que fazer dois projetos separados.</li>
<li><strong>Desempenho de app nativo:</strong> animações fluidas e interface rápida, com a cara da sua marca.</li>
<li><strong>Manutenção simplificada:</strong> uma correção ou novidade chega às duas plataformas ao mesmo tempo.</li>
</ul>
<h2>O que o seu app pode ter</h2>
<ul>
<li>Cadastro e login de usuários, inclusive com conta Google</li>
<li>Notificações push</li>
<li>Pagamentos e assinaturas</li>
<li>Mapas, câmera, fotos e funcionamento offline</li>
<li>Integração com o seu sistema, site ou ERP por API</li>
<li>Painel web para você gerenciar usuários e conteúdos</li>
</ul>
<h2>Do protótipo às lojas</h2>
<p>O projeto começa pelo <strong>protótipo das telas</strong>, para você ver e aprovar o app antes da programação, e termina com a <strong>publicação na Google Play e na App Store</strong>, em contas no nome da sua empresa.</p>
HTML,
                'deliverables'    => [
                    'Protótipo das telas para aprovação',
                    'Aplicativo para Android e iOS',
                    'API e banco de dados (back-end)',
                    'Painel administrativo web',
                    'Notificações push',
                    'Publicação na Google Play e na App Store',
                    'Código-fonte do projeto',
                    'Suporte após a publicação',
                ],
                'faq'             => [
                    ['question' => 'O aplicativo funciona no Android e no iPhone?', 'answer' => 'Sim. Com Flutter, um único projeto gera os aplicativos para Android e iOS, com a mesma aparência e as mesmas funções.'],
                    ['question' => 'Quanto custa desenvolver um aplicativo?', 'answer' => 'O valor depende das telas e funcionalidades, como login, pagamentos e integrações. Conte a sua ideia e receba uma proposta com escopo e prazo definidos.'],
                    ['question' => 'Vocês publicam o app nas lojas?', 'answer' => 'Sim, cuidamos da publicação na Google Play e na App Store. As contas de desenvolvedor ficam no nome da sua empresa, e as taxas das lojas são pagas diretamente a elas.'],
                    ['question' => 'O app pode se integrar com o sistema que eu já uso?', 'answer' => 'Sim, desde que o sistema ofereça uma forma de integração, como uma API. Também podemos desenvolver o sistema completo junto com o app.'],
                    ['question' => 'O aplicativo funciona sem internet?', 'answer' => 'Pode funcionar. Dados podem ficar salvos no celular e sincronizar quando a conexão voltar. Isso é definido no escopo do projeto.'],
                ],
            ],
            [
                'area'            => ServiceArea::Technology,
                'name'            => 'Desenvolvimento de Software',
                'slug'            => 'desenvolvimento-de-software',
                'icon'            => 'code-bracket',
                'headline'        => 'Troque planilhas e retrabalho por um sistema feito para o jeito que a sua empresa trabalha.',
                'summary'         => 'Desenvolvimento de software e sistemas web sob medida para automatizar processos, integrar setores e organizar a gestão, com painéis, relatórios, APIs e pagamentos.',
                'seo_title'       => 'Desenvolvimento de Software sob Medida e Sistemas Web',
                'seo_description' => 'Sistemas web sob medida para automatizar processos, integrar setores e organizar a gestão da sua empresa: painéis, relatórios, APIs e pagamentos online.',
                'body'            => <<<'HTML'
<h2>Quando a planilha já não dá conta</h2>
<p>Informação espalhada em planilhas, retrabalho, erros de digitação e decisões tomadas no escuro são sinais de que a empresa precisa de um sistema. Um <strong>software sob medida</strong> segue o seu processo, em vez de obrigar a sua equipe a se adaptar a um programa pronto.</p>
<h2>O que um sistema sob medida resolve</h2>
<ul>
<li><strong>Automação:</strong> tarefas repetitivas, cálculos e e-mails acontecem sozinhos.</li>
<li><strong>Informação em um só lugar:</strong> setores integrados, sem retrabalho e sem versões diferentes da mesma planilha.</li>
<li><strong>Decisão com dados:</strong> painéis e relatórios com os indicadores que importam para o seu negócio.</li>
<li><strong>Acesso de qualquer lugar:</strong> sistema web que funciona no computador, no tablet e no celular.</li>
<li><strong>Controle de acesso:</strong> cada usuário vê e faz apenas o que o perfil dele permite.</li>
</ul>
<h2>Exemplos de sistemas</h2>
<p>Gestão de <strong>orçamentos, pedidos e clientes</strong>; <strong>controle de obras</strong>, medições e cronogramas; estoque e produção; área do cliente com <strong>assinaturas e pagamentos online</strong>; portais com API para aplicativos; e dashboards de indicadores.</p>
<h2>Tecnologia e segurança</h2>
<p>Os sistemas são desenvolvidos com <strong>Laravel, Filament e MySQL</strong>, com Python para análise de dados quando necessário. O desenvolvimento segue boas práticas de segurança e de proteção de dados, em linha com a LGPD, e o código-fonte é seu.</p>
HTML,
                'deliverables'    => [
                    'Levantamento dos processos e requisitos',
                    'Protótipo das telas para aprovação',
                    'Sistema web com controle de acesso por perfil',
                    'Painéis e relatórios gerenciais',
                    'Integrações com pagamentos, e-mail, WhatsApp e APIs',
                    'Implantação e treinamento da equipe',
                    'Código-fonte e documentação',
                    'Suporte e evolução contínua',
                ],
                'faq'             => [
                    ['question' => 'Por que um sistema sob medida e não um pronto?', 'answer' => 'O sistema pronto obriga a empresa a mudar o processo para caber no programa. O sob medida faz exatamente o que você precisa, sem pagar por funções que não usa, e pode crescer junto com o negócio.'],
                    ['question' => 'O código do sistema é meu?', 'answer' => 'Sim. O código-fonte é entregue para você, e o sistema pode ficar no servidor da sua empresa ou em uma hospedagem no seu nome.'],
                    ['question' => 'Dá para começar pequeno e ir aumentando?', 'answer' => 'Sim, e é o mais recomendado. Começamos pelo que traz mais resultado e o sistema evolui em etapas, com entregas que já podem ser usadas.'],
                    ['question' => 'O sistema funciona no celular?', 'answer' => 'Sim. Os sistemas web são responsivos e funcionam no navegador do celular. Se precisar, também desenvolvemos um aplicativo integrado ao sistema.'],
                    ['question' => 'Quanto custa um software sob medida?', 'answer' => 'Depende do tamanho do processo e das integrações. Depois de entender o que você precisa, enviamos uma proposta com escopo, etapas e prazos definidos.'],
                ],
            ],
        ];
    }
}
