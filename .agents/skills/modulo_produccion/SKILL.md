---
name: Lógica de Producción LeonPlast
description: Se activa al trabajar con Ordenes de Produccion, Procesos, Mermas, Ensamblados o Inyectados.
---

# Lógica de Producción LeonPlast

Escribe aquí las reglas de negocio, tablas clave y flujos específicos de este módulo.

## Reglas Actuales:
1. Las Mermas (merma_clip, merma_cascara) se guardan enviando "null" en codigo_formula_produccion para evitar errores de Foreign Key.
2. Clip Recuperado se procesa como "recuperado_clip" para diferenciarlo en BD.
3. **Proceso de Encartonado:**
   - Consume 4 colores de ganchos ensamblados (PEP) + 1 tira de cartón (AUX) en el almacén `PS5E`.
   - La entrada de materiales a `PS5E` requiere que el cartón se transfiera previamente desde `ALM02` hacia `PS5E`, y los ganchos desde ensamblado (`PS5C`/`PS3A`) hacia `PS5E`.
   - El producto resultante (`EN07-Z...`) se controla con unidad de medida **`SET`** (la cantidad registrada en `inventario`, `kardex` y `produccion_ingresos_proceso` representa la cantidad de SETs producidos, mientras que los pesos en KG se usan para valorizar el costo unitario de cada SET).
   - El producto encartonado resultante permanece en `PS5E` hasta su posterior transferencia según necesidad.
4. **Proceso de Embolsado:**
   - **Almacén Operativo:** `PS5F` ("ALMACEN P5F - EMBOLSADO LEON"). Código de proceso = `5` en `proceso_produccion` y `21: EMBOLSADO` en `producto_proceso`.
   - **Flujo de Alimentación hacia `PS5F`:**
     - Encartonados (`EN07-Z...` en `SET`) o Ganchos ensamblados (`EN07-...` en `KG`/`UNI`): se transfieren previamente desde `PS5E` o ensamblado hacia `PS5F`.
     - Bolsas de Empaque (`01.20.007.xxx`, tipo `EMB`): se transfieren desde `ALM02` (Insumos) hacia `PS5F`.
   - **Centros de Trabajo:** `CON-001` (Embolsadora Automática), `SEB-001` / `SEB-002` (Selladora de Bolsa), `SELL-001` (Selladora 01).
   - **Producto Resultante:** Se controla en unidades **`BOL`** (Bolsas) o **`PAQ`** (Paquetes), e.g. `GA07-Z016.A` (PDT) o bolsas intermedias, permaneciendo en `PS5F` hasta su transferencia a `ALM01` (Producto Terminado).
   - **Manejo de Mermas en Embolsado:**
     - `merma_empaque`: Bolsas rotas o defectuosas durante el sellado/embolsado (se dan de baja en `PS5F` con motivo MERMA EMPAQUE).
     - `merma_cascara`: Ganchos o cáscaras rotas/dañadas durante el proceso (se transfieren a `ALM-REC` o se dan de baja para molienda).
     - `merma_carton`: Tiras de cartón rotas o dobladas.
     - `merma_clip` / `recuperado_clip`: Clips desprendidos o dañados.
