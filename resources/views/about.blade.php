<x-guest-layout title="Sobre">
    <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-extrabold mb-6">Sobre o VigiaBR</h1>

        <div class="prose prose-slate max-w-none">
            <p>
                O VigiaBR é uma plataforma de transparência que facilita o acesso às informações sobre
                gastos dos deputados federais brasileiros.
            </p>

            <h2>Fonte dos Dados</h2>
            <p>
                Todos os dados são obtidos através da
                <a href="https://dadosabertos.camara.leg.br/" target="_blank" class="text-emerald-600 hover:underline">
                    API de Dados Abertos da Câmara dos Deputados
                </a>,
                garantindo informações oficiais e atualizadas.
            </p>

            <h2>O que você pode fazer</h2>
            <ul>
                <li>Buscar deputados por nome, estado ou partido</li>
                <li>Visualizar despesas detalhadas de cada parlamentar</li>
                {{-- <li>Consultar rankings de gastos</li> --}}
                <li>Filtrar despesas por período, tipo e fornecedor</li>
            </ul>

            <h2>Projeto Aberto</h2>
            <p>
                Este é um projeto open source. Contribuições são bem-vindas!
            </p>
        </div>
    </section>
</x-guest-layout>
