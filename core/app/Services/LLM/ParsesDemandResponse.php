<?php

namespace App\Services\LLM;

trait ParsesDemandResponse
{
    protected function promptFor(string $messages): string
    {
        return <<<PROMPT
        Você é um assistente que analisa conversas de WhatsApp entre uma empresa e um cliente
        e decide se elas contêm uma demanda/solicitação acionável (algo que precisa virar uma
        tarefa, como um pedido, um problema a resolver ou uma pergunta pendente de resposta).

        Responda APENAS com um JSON válido, sem nenhum texto antes ou depois, no formato exato:
        {"is_demand": bool, "title": string, "summary": string, "due_date": string|null}

        Regras:
        - "is_demand": true somente se houver uma ação clara pendente.
        - "title": um título curto (até 80 caracteres) para a demanda.
        - "summary": um resumo objetivo do que precisa ser feito.
        - "due_date": data no formato YYYY-MM-DD se mencionada na conversa, caso contrário null.

        Conversa:
        ---
        {$messages}
        ---
        PROMPT;
    }

    /**
     * @return array{is_demand: bool, title: string, summary: string, due_date: ?string}
     */
    protected function parseJsonResponse(string $raw): array
    {
        $json = $this->extractJson($raw);

        $decoded = json_decode($json, true);

        if (! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            return $this->fallback();
        }

        return [
            'is_demand' => (bool) ($decoded['is_demand'] ?? false),
            'title' => (string) ($decoded['title'] ?? ''),
            'summary' => (string) ($decoded['summary'] ?? ''),
            'due_date' => empty($decoded['due_date']) ? null : (string) $decoded['due_date'],
        ];
    }

    /**
     * @return array{is_demand: bool, title: string, summary: string, due_date: ?string}
     */
    protected function fallback(): array
    {
        return [
            'is_demand' => false,
            'title' => '',
            'summary' => '',
            'due_date' => null,
        ];
    }

    private function extractJson(string $raw): string
    {
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');

        if ($start === false || $end === false || $end < $start) {
            return $raw;
        }

        return substr($raw, $start, $end - $start + 1);
    }
}
