<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SystemMessageModel;

/**
 * Controller: SystemMessages (admin)
 * CRUD para mensagens do sistema exibidas em telas como "sem carteira".
 */
class SystemMessagesController extends BaseController
{
    protected SystemMessageModel $model;

    public function __construct()
    {
        $this->model = new SystemMessageModel();
    }

    /**
     * Lista todas as mensagens do sistema.
     */
    public function index()
    {
        $mensagens = $this->model->findAll();

        return view('admin/mensagens/index', [
            'mensagens' => $mensagens,
        ]);
    }

    /**
     * Formulário de edição de uma mensagem por slug.
     */
    public function edit(string $slug)
    {
        $mensagem = $this->model->getBySlug($slug);

        if (!$mensagem) {
            return redirect()->to('/admin/mensagens')->with('error', 'Mensagem não encontrada.');
        }

        return view('admin/mensagens/edit', [
            'mensagem' => $mensagem,
        ]);
    }

    /**
     * Salva alterações em uma mensagem.
     */
    public function update(string $slug)
    {
        $mensagem = $this->model->getBySlug($slug);

        if (!$mensagem) {
            return redirect()->to('/admin/mensagens')->with('error', 'Mensagem não encontrada.');
        }

        $titulo   = $this->request->getPost('titulo');
        $conteudo = $this->request->getPost('conteudo');
        $ativo    = $this->request->getPost('ativo') ? true : false;

        $this->model->update($mensagem['id'], [
            'titulo'   => $titulo,
            'conteudo' => $conteudo,
            'ativo'    => $ativo,
        ]);

        return redirect()->to('/admin/mensagens')->with('success', 'Mensagem atualizada com sucesso.');
    }
}
