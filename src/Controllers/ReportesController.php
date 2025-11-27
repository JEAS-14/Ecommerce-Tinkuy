<?php

require_once BASE_PATH . '/src/Models/Reporte.php';

/**
 * Controlador de Reportes Administrativos
 * Gestiona generación y exportación de reportes (Excel/PDF)
 */
class ReportesController {
    private $conn;
    private $reporte;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->reporte = new Reporte($conn);
    }
    
    /**
     * Mostrar vista principal de reportes
     */
    public function index() {
        // Fechas por defecto: último mes
        $fecha_fin = date('Y-m-d');
        $fecha_inicio = date('Y-m-d', strtotime('-30 days'));
        
        // Verificar si es admin
        if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: ?page=login');
            exit;
        }
        
        $nombre_admin = $_SESSION['usuario'] ?? 'Admin';
        $base_url = '/Ecommerce-Tinkuy/public/index.php';
        
        // Si no hay solicitud de reporte, mostrar solo el formulario
        $reporte_data = null;
        $tipo_reporte = null;
        
        // Cargar vista
        require BASE_PATH . '/src/Views/admin/reportes/index.php';
    }
    
    /**
     * Generar reporte (vista o exportación)
     */
    public function generar() {
        // Verificar admin
        if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
            header('Location: ?page=login');
            exit;
        }
        
        // Obtener parámetros
        $tipo_reporte = $_POST['tipo_reporte'] ?? $_GET['tipo_reporte'] ?? 'ventas';
        $fecha_inicio = $_POST['fecha_inicio'] ?? $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fecha_fin = $_POST['fecha_fin'] ?? $_GET['fecha_fin'] ?? date('Y-m-d');
        $formato = $_POST['formato'] ?? $_GET['formato'] ?? 'vista';
        
        // Validar fechas
        if (strtotime($fecha_inicio) > strtotime($fecha_fin)) {
            $_SESSION['mensaje_error'] = 'La fecha de inicio no puede ser mayor a la fecha fin.';
            header('Location: ?page=admin_reportes');
            exit;
        }
        
        // Generar reporte según tipo
        switch ($tipo_reporte) {
            case 'ventas':
                $reporte_data = $this->reporte->generarReporteVentas($fecha_inicio, $fecha_fin);
                break;
            case 'productos':
                $reporte_data = $this->reporte->generarReporteProductos($fecha_inicio, $fecha_fin);
                break;
            case 'vendedores':
                $reporte_data = $this->reporte->generarReporteVendedores($fecha_inicio, $fecha_fin);
                break;
            default:
                $_SESSION['mensaje_error'] = 'Tipo de reporte inválido.';
                header('Location: ?page=admin_reportes');
                exit;
        }
        
        // Exportar o mostrar
        if ($formato === 'excel') {
            $this->exportarExcel($tipo_reporte, $reporte_data, $fecha_inicio, $fecha_fin);
        } elseif ($formato === 'pdf') {
            $this->exportarPDF($tipo_reporte, $reporte_data, $fecha_inicio, $fecha_fin);
        } else {
            // Mostrar en vista
            $nombre_admin = $_SESSION['usuario'] ?? 'Admin';
            $base_url = '/Ecommerce-Tinkuy/public/index.php';
            require BASE_PATH . '/src/Views/admin/reportes/index.php';
        }
    }
    
    /**
     * Exportar reporte a Excel (CSV mejorado)
     */
    private function exportarExcel($tipo, $data, $fecha_inicio, $fecha_fin) {
        $filename = "reporte_{$tipo}_" . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8 (compatibilidad Excel)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezado del reporte
        fputcsv($output, ['REPORTE DE ' . strtoupper($tipo)]);
        fputcsv($output, ['Período:', $fecha_inicio . ' a ' . $fecha_fin]);
        fputcsv($output, ['Generado:', date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        // Estadísticas generales
        fputcsv($output, ['=== RESUMEN GENERAL ===']);
        foreach ($data['estadisticas'] as $key => $value) {
            if (!is_array($value)) {
                $label = ucfirst(str_replace('_', ' ', $key));
                fputcsv($output, [$label, $value]);
            }
        }
        fputcsv($output, []);
        
        // Datos detallados
        fputcsv($output, ['=== DATOS DETALLADOS ===']);
        
        if (!empty($data['datos'])) {
            // Encabezados de columnas
            $headers = array_keys($data['datos'][0]);
            $headers_formatted = array_map(function($h) {
                return ucfirst(str_replace('_', ' ', $h));
            }, $headers);
            fputcsv($output, $headers_formatted);
            
            // Filas de datos
            foreach ($data['datos'] as $row) {
                fputcsv($output, $row);
            }
        } else {
            fputcsv($output, ['No hay datos para este período']);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Exportar reporte a PDF (HTML simple convertido)
     */
    private function exportarPDF($tipo, $data, $fecha_inicio, $fecha_fin) {
        // Generar HTML para PDF
        $html = $this->generarHTMLParaPDF($tipo, $data, $fecha_inicio, $fecha_fin);
        
        // Devolver HTML con auto-print para guardar como PDF
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
    
    /**
     * Generar HTML formateado para PDF
     */
    private function generarHTMLParaPDF($tipo, $data, $fecha_inicio, $fecha_fin) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Reporte <?= ucfirst($tipo) ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #dc3545; border-bottom: 3px solid #dc3545; padding-bottom: 10px; }
                h2 { color: #333; margin-top: 30px; background: #f8f9fa; padding: 10px; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 11px; }
                th { background: #dc3545; color: white; padding: 10px; text-align: left; }
                td { border: 1px solid #ddd; padding: 8px; }
                tr:nth-child(even) { background: #f8f9fa; }
                .stats { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .stats p { margin: 5px 0; }
                .header-info { background: #fff3cd; padding: 10px; margin-bottom: 20px; }
                .no-print { margin: 20px 0; }
                @media print {
                    .no-print { display: none; }
                }
                .btn-print {
                    background: #dc3545;
                    color: white;
                    padding: 12px 24px;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 16px;
                    margin: 10px 5px;
                }
                .btn-print:hover { background: #c82333; }
            </style>
        </head>
        <body>
            <div class="no-print">
                <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Guardar como PDF</button>
                <button class="btn-print" onclick="window.close()">❌ Cerrar</button>
                <p><small><strong>Tip:</strong> Usa Ctrl+P → Guardar como PDF en tu navegador</small></p>
            </div>

            <h1>🏪 REPORTE DE <?= strtoupper($tipo) ?> - TINKUY</h1>
            
            <div class="header-info">
                <p><strong>Período:</strong> <?= $fecha_inicio ?> hasta <?= $fecha_fin ?></p>
                <p><strong>Generado:</strong> <?= date('Y-m-d H:i:s') ?></p>
            </div>
            
            <div class="stats">
                <h2>📊 Resumen General</h2>
                <?php foreach ($data['estadisticas'] as $key => $value): ?>
                    <?php if (!is_array($value)): ?>
                        <p><strong><?= ucfirst(str_replace('_', ' ', $key)) ?>:</strong> <?= is_numeric($value) ? number_format($value, 2) : htmlspecialchars($value) ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <h2>📋 Datos Detallados</h2>
            <?php if (!empty($data['datos'])): ?>
                <table>
                    <thead>
                        <tr>
                            <?php foreach (array_keys($data['datos'][0]) as $header): ?>
                                <th><?= ucfirst(str_replace('_', ' ', $header)) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['datos'] as $row): ?>
                            <tr>
                                <?php foreach ($row as $cell): ?>
                                    <td><?= htmlspecialchars($cell ?? '') ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay datos disponibles para este período.</p>
            <?php endif; ?>
            
            <p style="margin-top: 40px; text-align: center; color: #6c757d; font-size: 12px;">
                Ecommerce Tinkuy - Sistema de Reportes Administrativos
            </p>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
