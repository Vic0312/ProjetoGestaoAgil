<?php
require_once __DIR__ . '/DadosController.php';

final class ExportController
{
    public static function report(array $user): string
    {
        // A mesma fonte e os mesmos períodos usados na visualização administrativa.
        $d = pageData('relatoriosAdmin.php', $user);
        $now = mindlyTime();
        $total = count($d['mes']);
        $done = $d['statusMes']['concluida'] ?? 0;
        $cancelled = $d['statusMes']['cancelada'] ?? 0;
        $period = $now->format('01/m/Y') . ' a ' . $now->format('t/m/Y');
        $rows = [
            ['Seção', 'Indicador', 'Valor', 'Período / referência', 'Gerado em'],
            ['Contas', 'Usuários cadastrados', count($d['usuarios']), 'Total atual'],
            ['Contas', 'Pacientes cadastrados', $d['totaisPerfis']['paciente'] ?? 0, 'Total atual'],
            ['Contas', 'Psicólogos cadastrados', $d['totaisPerfis']['psicologo'] ?? 0, 'Total atual'],
            ['Contas', 'Administradores cadastrados', $d['totaisPerfis']['admin'] ?? 0, 'Total atual'],
            ['Contas', 'Psicólogos ativos e aprovados', $d['psicologosAtivos'], 'Total atual'],
            ['Contas', 'Novos usuários', $d['novosUsuarios'], $period],
            ['Atendimentos', 'Consultas agendadas no período', $total, $period],
            ['Atendimentos', 'Consultas concluídas', $done, $period],
            ['Atendimentos', 'Consultas canceladas', $cancelled, $period],
            ['Indicadores', 'Taxa de conclusão (%)', self::decimal($total ? 100 * $done / $total : 0), $period],
            ['Indicadores', 'Taxa de cancelamento (%)', self::decimal($total ? 100 * $cancelled / $total : 0), $period],
            ['Indicadores', 'Média diária de atendimentos', self::decimal($total / (int) $now->format('t')), $period],
            ['Indicadores', 'Pacientes atendidos', count(array_unique(array_column(completed($d['mes']), 'paciente_id'))), $period],
            ['Indicadores', 'Agendamentos criados no mês', count(array_filter($d['consultas'], fn($c) => mindlyTime($c['criado_em'])->format('Y-m') === $now->format('Y-m'))), $period],
            ['Indicadores', 'Consultas por psicólogo cadastrado', self::decimal($total / max(1, $d['totaisPerfis']['psicologo'] ?? 0)), $period],
        ];
        foreach (['aguardando_pagamento', 'confirmada', 'em_andamento', 'nao_compareceu'] as $status) {
            $labels = ['aguardando_pagamento' => 'Aguardando pagamento', 'confirmada' => 'Confirmada', 'em_andamento' => 'Em andamento', 'nao_compareceu' => 'Não compareceu'];
            $rows[] = ['Status das consultas', $labels[$status], $d['statusMes'][$status] ?? 0, $period];
        }
        foreach ($d['grafico'] as $date => $count)
            $rows[] = ['Evolução diária (últimos 7 dias)', 'Atendimentos por data agendada', $count, $date];
        $stream = fopen('php://temp', 'w+b');
        if (!$stream) throw new RuntimeException('Export stream unavailable');
        try {
            fwrite($stream, "\xEF\xBB\xBF");
            foreach ($rows as $index => $row) {
                if ($index > 0) $row[] = $now->format('d/m/Y H:i:s') . ' (America/Sao_Paulo)';
                fputcsv($stream, $row, ';', '"', '', "\r\n");
            }
            rewind($stream);
            return stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }

    private static function decimal(float $number): string
    {
        return number_format($number, 1, ',', '');
    }

    public static function record(array $user): string
    {
        // sharedHistory usa somente o paciente autenticado; não consulta registros privados.
        $history = (new MindlyData($user))->sharedHistory();
        usort($history, fn($a, $b) => [$a['inicio_em'], (int) $a['id']] <=> [$b['inicio_em'], (int) $b['id']]);
        require_once __DIR__ . '/../vendor/tcpdf/tcpdf.php';
        $pdf = new class('P', 'mm', 'A4', true, 'UTF-8', false) extends TCPDF {
            public function Footer()
            {
                $this->SetY(-14);
                $this->SetFont('dejavusans', '', 8);
                $this->Cell(0, 5, 'Mindly | Confidencial | Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');
            }
        };
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetFooterMargin(14);
        $pdf->SetCreator('Mindly');
        $pdf->SetTitle('Mindly - Prontuário compartilhado');
        $pdf->SetMargins(18, 18, 18);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();
        $pdf->SetTextColor(40, 82, 60);
        $pdf->SetFont('dejavusans', 'B', 17);
        $pdf->MultiCell(0, 10, 'Mindly | Prontuário compartilhado');
        $pdf->SetTextColor(35, 45, 40);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->MultiCell(0, 6, 'Paciente: ' . $user['nome']);
        $pdf->MultiCell(0, 6, 'Gerado em: ' . mindlyTime()->format('d/m/Y H:i') . ' (horário de Brasília)');
        $pdf->MultiCell(0, 6, 'Documento pessoal e confidencial. Contém apenas informações disponibilizadas à sua conta.');
        $pdf->Ln(4);
        if (!$history) $pdf->MultiCell(0, 6, 'Nenhum registro disponível até o momento.');
        foreach ($history as $row) {
            // Write/MultiCell tratam tudo como texto, nunca HTML, arquivos ou URLs.
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->MultiCell(0, 7, mindlyTime($row['inicio_em'])->format('d/m/Y H:i') . ' | Consulta #' . $row['id']);
            $pdf->SetFont('dejavusans', '', 10);
            $pdf->MultiCell(0, 6, 'Profissional: ' . $row['psicologo_nome']);
            $pdf->MultiCell(0, 6, 'Status: Concluída');
            $pdf->MultiCell(0, 6, $row['texto'] ?? 'Nenhum resumo compartilhado para este atendimento.');
            if ($row['compartilhado_em'])
                $pdf->MultiCell(0, 6, 'Compartilhado em: ' . mindlyTime($row['compartilhado_em'])->format('d/m/Y H:i'));
            $pdf->Ln(5);
        }
        return $pdf->Output('', 'S');
    }
}
