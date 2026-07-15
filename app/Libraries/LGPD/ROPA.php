<?php

declare(strict_types=1);

namespace App\Libraries\LGPD;

use JsonSerializable;

/**
 * Classe ROPA (Registro de Operações de Tratamento de Dados Pessoais)
 * * Implementação baseada no art. 37 da Lei nº 13.709/2018 (LGPD).
 * Estrutura projetada para padronizar o registro das evidências de tratamento
 * de dados pessoais em todos os projetos da organização.
 */
class ROPA implements JsonSerializable
{
    public function __construct(
        private string $identificacaoControlador,
        private string $processoTratamento,
        private array $categoriasTitulares,
        private array $categoriasDadosPessoais,
        private string $finalidadeTratamento,
        private string $baseLegal,
        private ?array $compartilhamentoDados = null,
        private ?string $transferenciaInternacional = null,
        private string $prazoRetencaoDescarte,
        private array $medidasSeguranca,
        private string $atendimentoDireitosTitulares
    ) {}

    // --- Getters (Acesso à Informação) ---

    public function getIdentificacaoControlador(): string
    {
        return $this->identificacaoControlador;
    }

    public function getProcessoTratamento(): string
    {
        return $this->processoTratamento;
    }

    public function getCategoriasTitulares(): array
    {
        return $this->categoriasTitulares;
    }

    public function getCategoriasDadosPessoais(): array
    {
        return $this->categoriasDadosPessoais;
    }

    public function getFinalidadeTratamento(): string
    {
        return $this->finalidadeTratamento;
    }

    public function getBaseLegal(): string
    {
        return $this->baseLegal;
    }

    public function getCompartilhamentoDados(): ?array
    {
        return $this->compartilhamentoDados;
    }

    public function getTransferenciaInternacional(): ?string
    {
        return $this->transferenciaInternacional;
    }

    public function getPrazoRetencaoDescarte(): string
    {
        return $this->prazoRetencaoDescarte;
    }

    public function getMedidasSeguranca(): array
    {
        return $this->medidasSeguranca;
    }

    public function getAtendimentoDireitosTitulares(): string
    {
        return $this->atendimentoDireitosTitulares;
    }

    // --- Setters (Atualização do Registro de Tratamento) ---

    public function setProcessoTratamento(string $processoTratamento): self
    {
        $this->processoTratamento = $processoTratamento;
        return $this;
    }

    public function setBaseLegal(string $baseLegal): self
    {
        $this->baseLegal = $baseLegal;
        return $this;
    }

    public function setMedidasSeguranca(array $medidasSeguranca): self
    {
        $this->medidasSeguranca = $medidasSeguranca;
        return $this;
    }

    /**
     * Serializa o objeto para JSON.
     */
    public function jsonSerialize(): array
    {
        return [
            'identificacao_controlador'        => $this->identificacaoControlador,
            'processo_tratamento'              => $this->processoTratamento,
            'categorias_titulares'             => $this->categoriasTitulares,
            'categorias_dados_pessoais'        => $this->categoriasDadosPessoais,
            'finalidade_tratamento'            => $this->finalidadeTratamento,
            'base_legal'                       => $this->baseLegal,
            'compartilhamento_dados'           => $this->compartilhamentoDados,
            'transferencia_internacional'      => $this->transferenciaInternacional,
            'prazo_retencao_descarte'          => $this->prazoRetencaoDescarte,
            'medidas_seguranca'                => $this->medidasSeguranca,
            'atendimento_direitos_titulares'   => $this->atendimentoDireitosTitulares,
        ];
    }
}
