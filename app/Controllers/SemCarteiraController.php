<?php

namespace App\Controllers;

use App\Models\SystemMessageModel;

/**
 * Controller: SemCarteira
 * Exibe tela informativa para funcionários que logam mas não possuem carteira vinculada.
 */
class SemCarteiraController extends BaseController
{
    /**
     * Exibe a mensagem do sistema para funcionários sem carteira.
     */
    public function index()
    {
        $messageModel = new SystemMessageModel();
        $message      = $messageModel->getBySlug('sem-carteira');

        return view('sem_carteira/index', [
            'titulo'   => $message['titulo'] ?? 'Informações para Entrantes',
            'conteudo' => $message['conteudo'] ?? $this->mensagemPadrao(),
        ]);
    }

    /**
     * Mensagem padrão caso nenhuma esteja cadastrada no banco.
     */
    private function mensagemPadrao(): string
    {
        return '<div class="text-center">
            <h4>Bem-vindo ao SPIV</h4>
            <p class="lead">Você acessou o sistema, mas ainda não possui uma carteira de clientes vinculada.</p>
            <p>Para obter uma carteira, entre em contato com seu coordenador ou com a administração do sistema.</p>
            <hr>
            <p class="text-muted"><small>SPIV — Sistema de Prospecção e Inteligência de Vendas</small></p>
        </div>';
    }
}
