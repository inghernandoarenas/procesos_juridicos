<?php
// backend/libs/SyncStatus.php
//
// Estado simple del "traer anexos en background" (TYBA/SAMAI), para que el
// frontend pueda consultar por polling si todavía está trayendo documentos
// y avisarle al usuario — en vez de que abra Anexos, no vea nada y piense
// que algo falló, sin saber que sigue corriendo atrás.
//
// No es una cola de verdad: es un archivo json por proceso que el propio
// script en background (SincronizarTybaController / Sincronizarsamaicontroller)
// va actualizando en sus puntos clave. Se consulta con SincronizarEstadoController.php.

class SyncStatus {

    private static function dir(): string {
        $dir = __DIR__ . '/../../logs/sync_status';
        if (!file_exists($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir;
    }

    private static function archivo(int $proceso_id): string {
        return self::dir() . '/' . $proceso_id . '.json';
    }

    /**
     * @param string $fuente  'tyba' | 'samai'
     * @param string $estado  'trayendo_actuaciones' | 'trayendo_anexos' | 'completado' | 'error'
     */
    public static function set(int $proceso_id, string $fuente, string $estado, string $mensaje, array $extra = []): void {
        $data = array_merge([
            'proceso_id'  => $proceso_id,
            'fuente'      => $fuente,
            'estado'      => $estado,
            'mensaje'     => $mensaje,
            'actualizado' => date('Y-m-d H:i:s'),
        ], $extra);
        @file_put_contents(self::archivo($proceso_id), json_encode($data));
    }

    public static function get(int $proceso_id): ?array {
        $archivo = self::archivo($proceso_id);
        if (!file_exists($archivo)) {
            return null;
        }
        $json = @file_get_contents($archivo);
        $data = json_decode((string) $json, true);
        return is_array($data) ? $data : null;
    }
}
?>